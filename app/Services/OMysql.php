<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OMysql
{
    /* ──────────────────────────────
     * Low-level helpers
     * ────────────────────────────── */

    /** Connect as root using env settings. */
    private static function connectRoot(): \mysqli
    {
        $host = env('DB_HOST', 'localhost');
        $user = 'root';
        $pass = env('PETE_ROOT_PASS', '');

        $conn = @new \mysqli($host, $user, $pass);
        if ($conn->connect_error) {
            throw new \Exception("MySQL connection failed: " . $conn->connect_error);
        }
        return $conn;
    }

    /** Backtick-quote an identifier: db/table/column */
    private static function ident(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /** Single-quote a string value. */
    private static function s(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    /* ──────────────────────────────
     * Existence checks
     * ────────────────────────────── */

    /** True if schema exists. */
    public static function databaseExists(string $dbName): bool
    {
        $conn = self::connectRoot();
        $dbNameEsc = $conn->real_escape_string($dbName);
        $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbNameEsc}' LIMIT 1";
        $res = $conn->query($sql);
        $exists = $res && $res->num_rows > 0;
        if ($res) { $res->free(); }
        $conn->close();
        return $exists;
    }

    /** True if user@host exists (falls back to SHOW GRANTS probe if mysql.user is restricted). */
    public static function userExists(string $username, string $host = '%'): bool
    {
        $conn = self::connectRoot();
        try {
            $u = $conn->real_escape_string($username);
            $h = $conn->real_escape_string($host);
            $res = $conn->query("SELECT 1 FROM mysql.user WHERE user='{$u}' AND host='{$h}' LIMIT 1");
            $exists = $res && $res->num_rows > 0;
            if ($res) { $res->free(); }
            $conn->close();
            return $exists;
        } catch (\Throwable $e) {
            // fallback if mysql.user is not readable
            try {
                $probe = "SHOW GRANTS FOR " . self::s($username) . "@" . self::s($host);
                $ok = $conn->query($probe);
                $exists = $ok !== false;
                if ($ok instanceof \mysqli_result) $ok->free();
                $conn->close();
                return $exists;
            } catch (\Throwable $e2) {
                Log::notice('userExists fallback indicates non-existent user', [
                    'user' => $username, 'host' => $host, 'error' => $e2->getMessage()
                ]);
                $conn->close();
                return false;
            }
        }
    }

    /* ──────────────────────────────
     * Idempotent create / grant / delete
     * ────────────────────────────── */

    /** Create database if missing (utf8mb4). */
    public static function create_database(string $db_name): void
    {
        $conn = self::connectRoot();
        $db = $conn->real_escape_string($db_name);

        $sql = "CREATE DATABASE IF NOT EXISTS " . self::ident($db) .
               " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (!$conn->query($sql)) {
            $err = $conn->error;
            $conn->close();
            throw new \Exception("CREATE DATABASE failed: {$err}");
        }
        $conn->close();
        Log::info("Database ensured: {$db_name}");
    }

    /** Create/alter user and grant on the given database. */
    public static function create_user_and_grant(string $newUser, string $newPassword, string $database, string $userHost = '%'): void
    {
        $conn = self::connectRoot();

        $u = $conn->real_escape_string($newUser);
        $p = $conn->real_escape_string($newPassword);
        $d = $conn->real_escape_string($database);
        $h = $conn->real_escape_string($userHost);

        // CREATE or ensure password with ALTER
        $sqlCreate = "CREATE USER IF NOT EXISTS '{$u}'@'{$h}' IDENTIFIED BY '{$p}'";
        if (!$conn->query($sqlCreate)) {
            $err = $conn->error;
            $conn->close();
            throw new \Exception("CREATE USER failed: {$err}");
        }

        $sqlAlter  = "ALTER USER '{$u}'@'{$h}' IDENTIFIED BY '{$p}'";
        if (!$conn->query($sqlAlter)) {
            $err = $conn->error;
            $conn->close();
            throw new \Exception("ALTER USER failed: {$err}");
        }

        $sqlGrant  = "GRANT ALL PRIVILEGES ON " . self::ident($d) . ".* TO '{$u}'@'{$h}' WITH GRANT OPTION";
        if (!$conn->query($sqlGrant)) {
            $err = $conn->error;
            $conn->close();
            throw new \Exception("GRANT failed: {$err}");
        }

        if (!$conn->query("FLUSH PRIVILEGES")) {
            $err = $conn->error;
            $conn->close();
            throw new \Exception("FLUSH PRIVILEGES failed: {$err}");
        }

        $conn->close();
        Log::info("User ensured & granted: {$newUser}@{$userHost} on {$database}");
    }

    /** Drop DB if exists (kept signature for callers). */
    public static function delete_database($host, string $db_name, $db_root_pass = null): void
    {
        $conn = self::connectRoot();
        $d = $conn->real_escape_string($db_name);
        $sql = "DROP DATABASE IF EXISTS " . self::ident($d);
        if (!$conn->query($sql)) {
            $err = $conn->error;
            $conn->close();
            throw new \Exception("DROP DATABASE failed: {$err}");
        }
        $conn->close();
        Log::info("Database dropped (if existed): {$db_name}");
    }

    /* ──────────────────────────────
     * Legacy helpers you already use
     * ────────────────────────────── */

    public static function import_database($dsn, $db_root_pass, $file)
    {
        $user = env('PETE_DB_USER', 'root');
        $db = new \PDO($dsn, $user, $db_root_pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING]);
        $sql = file_get_contents($file);
        $db->exec($sql);
    }

    public static function untar_file($file_route, $project_name)
    {
        $base_path = base_path();
        $crud_path = "$base_path/operations/crud/$project_name";
        $phar = new \PharData($file_route);
        $phar->extractTo($crud_path, null, true);

        $array = [
            "filem"  => "$base_path/operations/crud/$project_name/massive_file/filem",
            "config" => "$base_path/operations/crud/$project_name/massive_file/config.txt",
            "query"  => "$base_path/operations/crud/$project_name/massive_file/query.sql",
        ];

        $myfile = fopen($array["config"], "r") or die("Unable to open file!");
        Log::info("file lines");
        $cont = 0;
        while (!feof($myfile)) {
            $aux = fgets($myfile);
            preg_match("/'.*?'/", $aux, $matches);
            Log::info($matches);
            if (isset($matches[0])) {
                $key = preg_replace('/[^A-Za-z0-9\-\.\_]/', '', $matches[0]);
                $array["config_$cont"] = $key;
            }
            $cont++;
        }
        fclose($myfile);
        return $array;
    }
}
