<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Log;
use App\Services\OMysql;
use DB;
use App\Models\Backup;
use App\User;
use App\Services\PeteOption;
use Crypt;
use Thamaraiselvam\MysqlImport\Import;
use Carbon\Carbon;
use App\Services\ApacheReloader;
use App\Services\CertbotIssuer;
use App\Services\OServer;
use App\Services\SslCertificate;

class Site extends Model {

	protected $casts = [
		'ssl_expires_at' => 'datetime',
	];

	protected static function booted(): void
    {
        static::created(function (Site $site) {
            OServer::refresh_opcache();
        });

        static::deleted(function (Site $site) {
            OServer::refresh_opcache();
        });
    }
	
	public function user()
	{
	    return $this->belongsTo('App\User');
	}

	public static function randomIdentifier(string $prefix = '', int $randomLen = 12, int $maxLen = 64): string
	{
		// sanitize prefix to [a-z0-9_]
		$prefix = strtolower(preg_replace('/[^a-z0-9_]/', '_', $prefix ?: ''));
		if ($prefix === '' || !preg_match('/^[a-z]/', $prefix)) {
			$prefix = 'p_' . ltrim($prefix, '_');
		}

		// hex is [0-9a-f]
		$hex = bin2hex(random_bytes(max(1, $randomLen)));
		$id  = $prefix . $hex;

		// ensure starts with letter
		if (!preg_match('/^[a-z]/', $id)) {
			$id = 'a' . ltrim($id, '_');
		}

		return substr($id, 0, max(1, $maxLen));
	}

	
	public static function print_mod_sec_report_html(){
		
		$base_path = base_path();
		$log_path_origin = "/var/log/apache2/modsec_audit.log";
		$log_path_dest = "$base_path/storage/logs/modsec_audit.log";
		
		if(file_exists($log_path_dest))
		    unlink($log_path_dest);
	
		chdir("$base_path/scripts/");
		$output = "";
		$command = "sudo ./get_mod_sec_report.sh -l {$log_path_dest} -o {$log_path_origin} -b {$base_path}";
		$output .= shell_exec($command);
	
	  	if(env('PETE_DEBUG') == "active"){
			Log::info('get_mod_security_log debug');
			Log::info($command);
			Log::info('output:');
			Log::info($output);
	  	}
		
	}
	
	

	public function generate_ssl($user_email){

		$base_path = base_path();
		$peteOptions = new PeteOption();
		$os = $peteOptions->get_meta_value('os');

		$apache_reload_secret = env('APACHE_RELOAD_SECRET');
		$base_path = base_path();
		
		$app_root = $peteOptions->get_meta_value('app_root');
		$project_path = $app_root . '/' . $this->name;

		chdir("$base_path/scripts/");
		$output = "";
		$email   = escapeshellarg((string) $user_email);
		$domain  = escapeshellarg((string) $this->url);
		$path    = escapeshellarg((string) $project_path);
		$command = "./create_ssl.sh -e {$email} -d {$domain} -s " . escapeshellarg((string) $apache_reload_secret) . " -p {$path}";
		$output .= shell_exec($command);

		// The secret stays out of the log.
		Log::info('generating SSL');
		Log::info("./create_ssl.sh -e {$email} -d {$domain} -s <secret> -p {$path}");
		Log::info('output:');
		Log::info($output);

		// When the new certificate expires: sites:renew-ssl (daily) renews it 30 days before.
		$this->recordSslExpiry();

		return $output;
	}

	/**
	 * Reads the certificate the site serves and saves when it expires (null when none is served).
	 */
	public function recordSslExpiry(): ?Carbon
	{
		$expiresAt = SslCertificate::expiresAt((string) $this->url);
		if ($expiresAt) {
			$this->ssl_expires_at = $expiresAt;
			$this->save();
			Log::info('SSL certificate expiry recorded.', ['site_id' => $this->id, 'domain' => $this->url, 'expires_at' => $expiresAt->toIso8601String()]);
		}
		return $expiresAt;
	}
	
	public static function get_server_data($user){
		
		$pete_options = new PeteOption();
		$cloud = $pete_options->get_meta_value('cloud');	
		
		if($user->admin != true){
			$disk_total_size = "5 GB";
			$disk_used = "1 GB";
			$disk_free = "4 GB";
		}else{
			
			if($cloud == "linode"){
				$output = shell_exec("df -h | grep /dev/sda");
				$array = explode("  ", $output);
				$disk_total_size = $array[4];
				$disk_used = $array[5];
				$disk_free = $array[6];
			}else if ($cloud == "docker"){
				$output = shell_exec("df -h | grep overlay");
				$array = explode("  ", $output);
				$disk_total_size = $array[5];
				$disk_used = $array[6];
				$disk_free = $array[7];
			}else{
				$output = shell_exec("df -h | grep /dev/root");
				$array = explode("  ", $output);
				$disk_total_size = $array[4];
				$disk_used = $array[5];
				$disk_free = $array[6];
			}
			
		}
		
		$apache_v_dialog = shell_exec('apache2 -v | grep version');
		$apache_v_dialog = preg_replace("/Server version:\s+/", "", $apache_v_dialog);
		
		$php_v_dialog = phpversion();
		$php_v_dialog = "PHP: ".substr($php_v_dialog, 0, 6);
		
		$mysql_v_dialog = shell_exec('mysql -V');
		
		$os_v_dialog = shell_exec('lsb_release -a | grep Description');
		$os_v_dialog = preg_replace("/Description:\s+/", "", $os_v_dialog);
		
		$time = Carbon::now();
		$server_time = $time->format('Y/m/d H:i:s');
		
		return ["disk_total_size" => $disk_total_size, "disk_used" => $disk_used, "disk_free" => $disk_free, "apache_v_dialog" => $apache_v_dialog, "php_v_dialog" => $php_v_dialog, "mysql_v_dialog" => $mysql_v_dialog, "os_v_dialog" => $os_v_dialog, "server_time" => $server_time];
	}
	
	public function can_this_site_be_create(){
		$url_check = Site::where("url",$this->url)->first();
		$name_check = Site::where("name",$this->name)->first();
		if(!isset($this->url) || $this->url == "" || $this->url == "null" || $this->url == "undefined" || $this->url == "0") {
		 	return ['error' => true, 'message'=>'Please select a domain.'];
		}else if(isset($url_check)){
		  return ['error' => true, 'message'=>'Domain is already used.', ];	
		}else if(isset($name_check)){
		  	return ['error' => true, 'message'=>'Project name already used.'];
		}else{
			return ['error' => false];
		}
	}
	
	public static function can_this_url_be_set($url){
		$url_check = Site::where("url",$url)->first();
		if(!isset($url) || $url == "" || $url == "null" || $url == "undefined" || $url == "0") {
		 	return ['error' => true, 'message'=>'Please select a domain.'];
		}else if(isset($url_check)){
		  return ['error' => true, 'message'=>'Domain is already used.'];	
		}else{
			return ['error' => false];
		}
	}
	
	public static function can_this_backup_be_create($backup_label){
		if(!preg_match('/^[a-zA-Z0-9-_]+$/',$backup_label)){
			return response()->json(['error' => 'true', 'message'=>'Invalid Title.']);
		}else{
			return ['error' => false];
		}
	}
	
	public function can_this_site_be_restore(){
		$checkurl = str_replace("_odeleted_$this->id","",$this->url);			
		$cheksite = Site::where("url",$checkurl)->first();
		if(isset($cheksite)){
			return ['error' => 'true', 'message'=>'URL is being used.'];
		}
	}
	
	public function get_first_password(){
		if(isset($this->first_password)){
			$wp_pass = Crypt::decrypt($this->first_password);	
		}else{
			$wp_pass = "undefined";
		}
		return $wp_pass;
	}
	
	public function set_project_name($url){
		$this->name = preg_replace("/\s+/", "", $url);
		$this->name = str_replace(".","",$this->name);
	}
	
	public function set_url($url){
		
		$pete_options = new PeteOption();
		$this->url = preg_replace("/\s+/", "", $url);
		$this->url = str_replace("http://","",$url);
		$this->url = str_replace("www.","",$url);
		
		if($pete_options->get_meta_value('domain_template')){
			$this->url = $this->url . "." . $pete_options->get_meta_value('domain_template');
		}
	}
	
	
	
	public function is_the_name_forbidden(){
		$forbidden_names = array("filemanager", "ssl", "localhost", "pete", "pete2","html", "Pete", "Pete2");
		if (in_array($this->name, $forbidden_names)) {
			return true;
		}else{
			return false;
		}
	}
	public function is_the_url_forbidden(){
		$forbidden_names = array("pete.wordpresspete.com","dashboard.wordpresspete.com","www.wordpresspete.com","pete.petetesting.com");
		if (in_array($this->url, $forbidden_names)) {
			return true;
		}else{
			return false;
		}
	}
	
	public function save(array $options = [])
	   {
	      // before save code 
		  $this->name = strtolower($this->name);
		  $this->url = strtolower($this->url);
	      parent::save($options);
	      // after save code
	   }
		
	public static function reload_server() {
		
		$output = "";
		$pete_options = new PeteOption();	
		$os = $pete_options->get_meta_value('os');
		$debug = env('PETE_DEBUG');
		
		if($os == "docker"){
			if (ApacheReloader::graceful()) {
   				 // success
				 Log::info("reload success");
			} else {
    			// handle failure
				Log::info("reload failure");
			}
		}else{
			$output = shell_exec("sudo /etc/init.d/apache2 reload");
		}

		if($debug == "active")
			Log::info("Reload server: $output");
		
		return $output;
	}
	
	public static function restart_server() {
		
		$output = "";
		$pete_options = new PeteOption();	
		$os_distribution = $pete_options->get_meta_value('os_distribution');
		$base_path = base_path();
		$debug = env('PETE_DEBUG');
		
  	  	$base_path = base_path();
    	chdir("$base_path/scripts/");
		
		$output = shell_exec("./restart_server.sh");
	  
		if($debug == "active")
			Log::info("Reload server: $output");
		
		return $output;
	}
	
	public function create_config_file(){
		
		$pete_options = new PeteOption();
    	$server_conf = $pete_options->get_meta_value('server_conf');
		$os_distribution = $pete_options->get_meta_value('os_distribution');
		$server = $pete_options->get_meta_value('server');
		$logs_route = $pete_options->get_meta_value('logs_route');
		$app_root = $pete_options->get_meta_value('app_root');
		$system_ssl = $pete_options->get_meta_value('system_ssl');
		$os = $pete_options->get_meta_value('os');
		
		if($pete_options->get_meta_value('system_ssl') != ""){
			$system_ssl = $pete_options->get_meta_value('system_ssl');
		}else{
			$system_ssl = "false";
		}
		
		if(!isset($this->ssl) || $this->ssl == false)
			$ssl = "false";
		else
			$ssl = "true";
		
		if(!isset($this->published) || $this->published == false)
			$published = "false";
		else
			$published = "true";
		
  	 	$base_path = base_path();
    	chdir("$base_path/scripts/");
		
		$command="./create_config_file.sh -l {$logs_route} -n {$this->name} -u {$this->url} -a {$server_conf} -o {$this->app_name} -d {$os_distribution} -e {$server} -r {$app_root} -p {$base_path} -c {$os}";	
		$output = shell_exec($command);
		
  	  	if(env('PETE_DEBUG') == "active"){
  			Log::info('enter in create_config_file.sh refined4');
			Log::info($command);
			Log::info('output:');
			Log::info($output);
  	  	}
	
	}
	
	public function normalize_name(){
		if (strpos($this->name, '_') !== false) {
			$aux = $this->name;
			$pos = strpos($aux, "_");
			$str1 = substr($aux,0,$pos+1);
			$this->name = str_replace($str1,"",$aux);
		}
	}
	
	public function create_backup($label){
		$pete_options = new PeteOption();
		$app_root     = $pete_options->get_meta_value('app_root');
		$host         = env('DB_HOST') ?? 'localhost';
		$db_root_pass = env('PETE_ROOT_PASS');
		$debug        = env('PETE_DEBUG');
		$base_path    = base_path();

		// 🔒 per-source file lock to serialize the critical section
		$locksDir = storage_path('app/locks');
		@mkdir($locksDir, 0775, true);
		$lockFp = fopen($locksDir . "/backup_{$this->id}.lock", 'c');
		$locked = $lockFp ? flock($lockFp, LOCK_EX) : false;

		try {
			//SET BACKUP
			$backup = new Backup();
			$backup->site_id       = $this->id;
			$backup->theme         = $this->theme;
			$backup->first_password= $this->first_password;
			$backup->wp_user       = $this->wp_user;
			$backup->name          = $this->name;
			$backup->url           = $this->url;
			$backup->schedulling   = $label; // ← now unique (e.g. oclone_<uuid>)
			$backup->file_name     = $this->name . '_' . $label . ".tar.gz"; // unique filename
			$backup->manual        = true;
			$backup->user_id       = $this->user_id;
			$backup->barserver_id  = $this->barserver_id;

			// ⚠️ legacy path is shared — keep it but don’t collide on filenames
			$backup_route = "$base_path/operations/backups/{$this->name}";
			shell_exec("mkdir -p $backup_route/massive_file");

			// dump goes to a shared dir, but each job writes a different archive name
			$dump = new \Ifsnop\Mysqldump\Mysqldump(
				"mysql:host=$host;dbname=$this->db_name", "root", $db_root_pass
			);
			$dump->start("$backup_route/massive_file/query.sql");

			chdir("$base_path/scripts/");
			#$command = "./backup.sh -n {$this->name} -r {$app_root} -q {$base_path} -u {$this->url} -d {$this->id} -f {$backup->file_name} -k {$debug}";
			#$output  = shell_exec($command);

			$command = sprintf(
			"./backup.sh -n %s -r %s -q %s -u %s -d %s -f %s -k %s",
			escapeshellarg($this->name),
			escapeshellarg($app_root),
			escapeshellarg($base_path),
			escapeshellarg($this->url),
			escapeshellarg((string)$this->id),
			escapeshellarg($backup->file_name),
			escapeshellarg((string)$debug)
			);
			$output = shell_exec($command);
			$backup->output = $output;
			
			if($debug == "active"){
				\Log::info("#######BACKUP WORDPRESS#######");
				\Log::info("query.sql route: $backup_route/massive_file/query.sql");
				\Log::info($command);
				\Log::info($output);
			}

			$this->save();
			$backup->save();
			return $backup;

		} finally {
			if ($locked) { flock($lockFp, LOCK_UN); }
			if ($lockFp) { fclose($lockFp); }
		}
	}
	
	public function delete_wordpress() {
			
		$pete_options = new PeteOption();
	    $app_root = $pete_options->get_meta_value('app_root');
	    $server_conf = $pete_options->get_meta_value('server_conf');
		$os_distribution = $pete_options->get_meta_value('os_distribution');
		$base_path = base_path();
		
		chdir("$base_path/scripts/");	
		$command = "./delete.sh -n {$this->name} -r {$app_root} -q {$base_path} -a {$server_conf} -s {$this->id} -p {$os_distribution}";
		$output = shell_exec($command);
  	  	
		$host = env('DB_HOST') ?? 'localhost';
		if($host != "localhost")
			$host=$host.":3306";
		$db_user_pass = env('PETE_ROOT_PASS');

		OMysql::delete_database($host,$this->db_name,$db_user_pass);
		
	  	if(env('PETE_DEBUG') == "active"){
			Log::info("######DELETE LOGIC DEBUG########");
			Log::info("COMMAND:");
  			Log::info($command);
	  		Log::info("OUTPUT:");
			Log::info($output);
  	  	}

		$this->delete();
	}
	
	public function update_wp_config_url($old_url,$new_url){
		
		$pete_options = new PeteOption();
		$app_root = $pete_options->get_meta_value('app_root');
		
		$filePath = "$app_root/$this->name/wp-config.php";

		// Read the file content
		$fileContent = file_get_contents($filePath);

		// Replace the target string
		$updatedContent = str_replace("https://$old_url", "http://$new_url", $fileContent);
		$updatedContent = str_replace("http://$old_url", "http://$new_url", $fileContent);

		// Write the updated content back to the file
		file_put_contents($filePath, $updatedContent);
		
	}
	
	public function create_wordpress()
	{
		$pete_options     = new PeteOption();
		$app_root         = $pete_options->get_meta_value('app_root');
		$mysql_bin        = $pete_options->get_meta_value('mysql_bin');
		$server_conf      = $pete_options->get_meta_value('server_conf');
		$os               = $pete_options->get_meta_value('os');
		$os_version       = $pete_options->get_meta_value('os_version');
		$server           = $pete_options->get_meta_value('server');
		$server_version   = $pete_options->get_meta_value('server_version');
		$logs_route       = $pete_options->get_meta_value('logs_route');
		$os_distribution  = $pete_options->get_meta_value('os_distribution');
		$base_path        = base_path();
		$host             = env('DB_HOST') ?? 'localhost';
		$debug            = env('PETE_DEBUG');
		$domain_template  = $pete_options->get_meta_value('domain_template');

		// Limits
		$DB_NAME_MAX = 64;
		$DB_USER_MAX = 32;
		$reservedDBs = ['mysql','sys','performance_schema','information_schema'];

		$attempts = 0;
		$maxAttempts = 6;

		$db_name = '';
		$db_user = '';
		$db_user_pass = '';

		do {
			$attempts++;

			$db_name = self::randomIdentifier('db_', 16, $DB_NAME_MAX);
			$db_user = self::randomIdentifier('u_', 16, $DB_USER_MAX);

			if (in_array($db_name, $reservedDBs, true)) continue;

			if (OMysql::databaseExists($db_name) || OMysql::userExists($db_user)) {
				\Log::info('DB/User exists already, retrying', compact('db_name','db_user'));
				continue;
			}

			try {
				$db_user_pass = bin2hex(random_bytes(8)); // safe ascii
				OMysql::create_database($db_name);
				OMysql::create_user_and_grant($db_user, $db_user_pass, $db_name);
				break; // success
			} catch (\Throwable $e) {
				\Log::warning('DB/User creation failed; retrying', [
					'attempt' => $attempts, 'db_name' => $db_name, 'db_user' => $db_user, 'error' => $e->getMessage()
				]);
				usleep(80_000);
			}
		} while ($attempts < $maxAttempts);

		if ($attempts >= $maxAttempts) {
			throw new \RuntimeException('Could not allocate a valid database/user after several attempts.');
		}

		$cookie_domain = $domain_template !== "" ? $domain_template : "none";

		chdir("$base_path/scripts/");
		$command = "./create.sh -n {$this->name} -u {$this->url} -r {$app_root} -q {$base_path} -m {$logs_route} -s {$os_distribution} -a {$server_conf} -x {$db_name} -y {$db_user} -z {$db_user_pass} -t {$server} -h {$host} -v {$cookie_domain} -k {$debug} ";
		$output  = shell_exec($command);

		if ($debug == "active") {
			Log::info("#######NEW WORDPRESS#######");
			Log::info($command);
			Log::info("#######OUTPUT#######");
			Log::info($output);
			$this->output = "#######NEW WORDPRESS#######\n" . (string) $this->output . $output;
		}

		$this->db_name     = $db_name;
		$this->db_user     = $db_user;
		$this->db_password = Crypt::encryptString($db_user_pass);
		$this->action_name = "New";
		$this->app_name    = "WordPress";
		$this->save();
		$this->create_config_file();
	}
	
		
	
	public function create_admin_user($db_user,$db_user_pass,$db_name,$os_distribution,$user_email,$wp_user,$wp_pass){
		
		if($os_distribution=="docker") {	
			$host = 'mysql';
			$db_user = "root";
			$db_user_pass = env('PETE_ROOT_PASS');
		}else{
			$host = 'localhost';
		}
		
		//CREATE ADMIN USER WITH FIRST PASSWORD
		$conn=mysqli_connect($host,$db_user,$db_user_pass,$db_name);
		// Check connection
		if (mysqli_connect_errno())
		  {
		  Log::info("Failed to connect to MySQL: " . mysqli_connect_error());
		  }
		  $now = Carbon::now();
		  $now_string = $now->format('Y/m/d H:i:s');
		  
		  $sql1 = "INSERT INTO `wp_users` (`user_login`, `user_pass`, `user_nicename`, `user_email`,`user_status`,`user_registered`,`display_name`)
 VALUES ('$wp_user', MD5('$wp_pass'), '$wp_user', '$user_email', '0','$now_string','$wp_user')";
		  
		  if ($conn->query($sql1) === TRUE) {
		      Log::info("New record created successfully");
		  } else {
		      Log::info("Error: " . $conn->error);
		  }
		  
		  $sql2 = "INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) 
 VALUES (NULL, (Select max(id) FROM wp_users), 
 'wp_capabilities', 'a:1:{s:13:".'"administrator"'.';s:1:"1";}'."'".")";
 
 		if ($conn->query($sql2) === TRUE) {
     	   Log::info("New record created successfully");
 	  	}else {
     	   Log::info("Error: " . $conn->error);
 	  	}
		
		$sql3 = "INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) 
 VALUES (NULL, (Select max(id) FROM wp_users), 'wp_user_level', '10')";
		
 		if ($conn->query($sql3) === TRUE) {
     	   Log::info("New record created successfully");
 	  	}else {
     	   Log::info("Error: " . $conn->error);
 	  	}

		$conn->close();
	}
	
	
	public function import_wordpress($args)
	{
		// Derive project name from the final URL
		$this->set_project_name($this->url);

		$pete_options    = new \App\Services\PeteOption();
		$app_root        = $pete_options->get_meta_value('app_root');
		$server          = $pete_options->get_meta_value('server');
		$logs_route      = $pete_options->get_meta_value('logs_route');
		$os_distribution = $pete_options->get_meta_value('os_distribution');
		$debugEnv        = env('PETE_DEBUG', ''); // <-- single source of truth now
		$host            = env('DB_HOST') ?? 'localhost';
		$base_path       = base_path();
		$domain_template = $pete_options->get_meta_value('domain_template');
		$jobId           = (string)($args['job_id'] ?? '');

		// small helper to append to import job log if available
		$jobLog = function (string $line) use ($jobId) {
			if (!$jobId) return;
			$statusDir = storage_path('app/import-jobs');
			$logFile   = "{$statusDir}/{$jobId}.log";
			@mkdir($statusDir, 0775, true);
			$ts = now()->format('Y-m-d H:i:s');
			@file_put_contents($logFile, "[{$ts}] {$line}\n", FILE_APPEND);
		};

		$jobLog("IMPORT start site={$this->name} url={$this->url}");

		/* ────────────────────────────────────────────────
		* Safe DB & user allocation
		* ──────────────────────────────────────────────── */
		$DB_NAME_MAX = 64;
		$DB_USER_MAX = 32;
		$reservedDBs = ['mysql', 'sys', 'performance_schema', 'information_schema'];

		$attempts    = 0;
		$maxAttempts = 6;

		$db_name      = '';
		$db_user      = '';
		$db_user_pass = '';

		do {
			$attempts++;

			$db_name = self::randomIdentifier('db_', 16, $DB_NAME_MAX);
			$db_user = self::randomIdentifier('u_', 16, $DB_USER_MAX);

			if (in_array($db_name, $reservedDBs, true)) {
				continue;
			}

			if (\App\Services\OMysql::databaseExists($db_name) || \App\Services\OMysql::userExists($db_user)) {
				\Log::info('DB/User exists already, retrying', ['db_name' => $db_name, 'db_user' => $db_user]);
				continue;
			}

			try {
				$db_user_pass = bin2hex(random_bytes(8));
				\App\Services\OMysql::create_database($db_name);
				\App\Services\OMysql::create_user_and_grant($db_user, $db_user_pass, $db_name);
				break;
			} catch (\Throwable $e) {
				\Log::warning('DB/User creation failed; retrying', [
					'attempt' => $attempts,
					'db_name' => $db_name,
					'db_user' => $db_user,
					'error'   => $e->getMessage(),
				]);
				usleep(80_000);
			}
		} while ($attempts < $maxAttempts);

		if ($attempts >= $maxAttempts) {
			throw new \RuntimeException('Could not allocate a valid database/user after several attempts.');
		}

		$cookie_domain = $domain_template != "" ? $domain_template : "none";

		/* ────────────────────────────────────────────────
		* Determine template source
		* ──────────────────────────────────────────────── */
		if (($args['template'] ?? '') == "none") {
			if ($this->big_file_route != "") {
				$file_route = $this->big_file_route;
			} else {
				$file_route = "$base_path/public/uploads/$this->zip_file_url";
			}
			$type = "none";
		} else if (strpos((string)$args['template'], 'http') !== false) {
			$type = "url";
			$file_route = (string)$args['template'];
		} else {
			$type = "file";
			$file_route = (string)$args['template'];
		}

		$jobLog("Archive type={$type} route={$file_route}");

		/* ────────────────────────────────────────────────
		* Ensure CRUD folder, untar
		* ──────────────────────────────────────────────── */
		$folder = "$base_path/operations/crud/$this->name";
		if (!file_exists($folder)) mkdir($folder, 0775, true);

		chdir("$base_path/scripts/");

		// ✅ NEW: pass PETE_DEBUG via environment (scripts read PETE_DEBUG=active)
		$command_untar = sprintf(
			"PETE_DEBUG=%s ./untar_file.sh -n %s -r %s -q %s -o %s -s %s 2>&1",
			escapeshellarg($debugEnv),
			escapeshellarg($this->name),
			escapeshellarg($app_root),
			escapeshellarg($base_path),
			escapeshellarg($file_route),
			escapeshellarg($type)
		);

		$jobLog("UNTAR cmd={$command_untar}");
		$output_command_untar = shell_exec($command_untar) ?? '';
		$jobLog("UNTAR output=" . trim($output_command_untar));

		// Validate untar created massive_file/query.sql
		$query_file = "$base_path/operations/crud/$this->name/massive_file/query.sql";

		if (!is_file($query_file)) {
			throw new \RuntimeException("Untar succeeded but SQL dump not found: {$query_file}");
		}
		if (filesize($query_file) < 50) {
			throw new \RuntimeException("SQL dump looks empty or too small: {$query_file}");
		}

		/* ────────────────────────────────────────────────
		* Import SQL into new DB
		* ──────────────────────────────────────────────── */
		$jobLog("SQL_IMPORT into db={$db_name} user={$db_user} host={$host}");

		try {
			new \Thamaraiselvam\MysqlImport\Import($query_file, $db_user, $db_user_pass, $db_name, $host);
		} catch (\Throwable $e) {
			throw new \RuntimeException("SQL import failed: " . $e->getMessage());
		}

		/* ────────────────────────────────────────────────
		* Run import script (wp-config rewrites, perms, vhost, etc.)
		* ──────────────────────────────────────────────── */
		// ✅ NEW: pass PETE_DEBUG via environment (no -k)
		$command_import = sprintf(
			"PETE_DEBUG=%s ./import.sh -n %s -u %s -r %s -q %s -m %s -x %s -y %s -l %s -t %s -z %s -h %s -v %s 2>&1",
			escapeshellarg($debugEnv),
			escapeshellarg($this->name),
			escapeshellarg($this->url),
			escapeshellarg($app_root),
			escapeshellarg($base_path),
			escapeshellarg($logs_route),
			escapeshellarg($db_name),
			escapeshellarg($db_user),
			escapeshellarg($db_user_pass),
			escapeshellarg($server),
			escapeshellarg($os_distribution),
			escapeshellarg($host),
			escapeshellarg($cookie_domain)
		);

		$jobLog("IMPORT_SH cmd={$command_import}");
		$command_import_output = shell_exec($command_import) ?? '';
		$jobLog("IMPORT_SH output=" . trim($command_import_output));

		// Persist output for UI logs (you already use this)
		$this->output = $command_import_output;

		if ($debugEnv === "active") {
			\Log::info("######UNTAR########");
			\Log::info("COMMAND:");
			\Log::info($command_untar);
			\Log::info("OUTPUT:");
			\Log::info($output_command_untar);

			\Log::info("######IMPORT########");
			\Log::info("COMMAND:");
			\Log::info($command_import);
			\Log::info("OUTPUT:");
			\Log::info($command_import_output);
		}

		/* ────────────────────────────────────────────────
		* Persist fields and write vhost config
		* ──────────────────────────────────────────────── */
		$this->db_name     = $db_name;
		$this->db_user     = $db_user;
		$this->action_name = "Import";
		$this->app_name    = "WordPress";
		$this->db_password = \Crypt::encryptString($db_user_pass);
		$this->save();

		$this->create_config_file();

		$jobLog("IMPORT done site_id={$this->id}");
	}
	
}
