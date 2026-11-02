<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Deletes the archives the "Import WordPress" upload leaves in storage/app/wordpress-imports.
 * The import runner (bootstrap/run_import.php) deletes its own archive when it ends; this
 * catches the rest: uploads never imported, runners that were killed, older leftovers. The
 * scheduler runs it hourly (app/Console/Kernel.php). A file is kept while an import runs
 * with it (a run_import.php process whose --template is that file) or while it is younger
 * than --hours, so an upload waiting for its import to be submitted is never touched.
 * Chunks of uploads in progress live in storage/app/chunks, which the chunk-upload package
 * clears itself.
 */
class CleanWordPressImports extends Command
{
    protected $signature = 'wordpress-imports:clean
        {--dry-run : Only list what would be deleted}
        {--hours=6 : Keep files modified within the last N hours}';

    protected $description = 'Delete uploaded WordPress import archives that no import is using.';

    public function handle(): int
    {
        $dir = storage_path('app/wordpress-imports');
        if (! is_dir($dir)) {
            $this->info('Nothing to clean: ' . $dir . ' does not exist.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $cutoff = time() - max(0, (int) $this->option('hours')) * 3600;
        $inUse  = self::archivesInUse();

        $deleted = 0;
        $bytes   = 0;
        foreach (new \FilesystemIterator($dir) as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $path = $file->getRealPath();
            if (isset($inUse[$path])) {
                $this->line("keep (import running) {$file->getFilename()}");
                continue;
            }
            if ($file->getMTime() > $cutoff) {
                $this->line("keep (recent) {$file->getFilename()}");
                continue;
            }

            $size = $file->getSize();
            if ($dryRun || @unlink($path)) {
                $deleted++;
                $bytes += $size;
                $this->line(($dryRun ? 'would delete ' : 'deleted ') . $file->getFilename() . ' (' . self::human($size) . ')');
            } else {
                $this->warn("could not delete {$file->getFilename()}");
            }
        }

        $this->info(sprintf('%s %d file(s), %s.', $dryRun ? 'Would delete' : 'Deleted', $deleted, self::human($bytes)));
        return self::SUCCESS;
    }

    /**
     * Real paths of the archives that running import runners were started with, read from
     * /proc so it works without ps. Keys are paths, for isset().
     */
    public static function archivesInUse(): array
    {
        $paths = [];
        foreach (glob('/proc/[0-9]*/cmdline') ?: [] as $cmdline) {
            $args = explode("\0", (string) @file_get_contents($cmdline));
            if (! preg_grep('/run_import\.php$/', $args)) {
                continue;
            }
            foreach ($args as $arg) {
                if (str_starts_with($arg, '--template=') && ($real = realpath(substr($arg, 11)))) {
                    $paths[$real] = true;
                }
            }
        }
        return $paths;
    }

    private static function human(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        return sprintf('%.1f %s', $n, $units[$i]);
    }
}
