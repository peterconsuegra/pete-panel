<?php

namespace App\Services;

use Log;
use App\Services\PeteOption;
use App\Services\ApacheReloader;

class OServer
{
	public static function refresh_opcache()
	{
		$base_path    = base_path();
        chdir("$base_path/scripts/");
        $command="sudo ./refresh_opcache.sh";
        $output = shell_exec($command);

		if(env('PETE_DEBUG') == "active"){
			Log::info('refresh_opcache debug');
			Log::info($command);
			Log::info('output:');
			Log::info($output);
	  	}
	}

	public static function readSshPublicKey(): array
    {
        $script = base_path('scripts/get_ssh_pub_key.sh');

        if (!is_file($script)) {
            return ['', "Script not found: {$script}"];
        }

        // You can make the script executable in the image/container, but we also
        // support invoking through 'sh' so it works even if not +x.
        $cmd = 'sh ' . escapeshellarg($script) . ' 2>&1';

        $outputLines = [];
        $code        = 0;
        exec($cmd, $outputLines, $code);

        if ($code !== 0) {
            $msg = trim(implode("\n", $outputLines)) ?: 'Unable to read SSH public key.';
            return ['', $msg];
        }

        $pub = trim(implode("\n", $outputLines));
        if ($pub === '') {
            return ['', 'SSH public key appears empty.'];
        }

        return [$pub, null];
    }

	public static function print_security_report()
	{
		
		$base_path = base_path();
		$log_path_origin = "/var/log/apache2/modsec_audit.log";
		$log_path_dest = "$base_path/storage/logs/modsec_audit.log";
		
		if(file_exists($log_path_dest))
		    unlink($log_path_dest);
	
		chdir("$base_path/scripts/");
		$output = "";
		$command = "sudo ./print_security_report.sh -b {$base_path}";
		$output .= shell_exec($command);
	
	  	if(env('PETE_DEBUG') == "active"){
			Log::info('print_security_report debug');
			Log::info($command);
			Log::info('output:');
			Log::info($output);
	  	}
		
	}

	public static function truncate_security_log(int $lines = 50000, ?int $bytes = null): void
	{
		$base = base_path();
		chdir("$base/scripts/");

		// Build the command: prefer bytes if provided
		$cmd = 'sudo ./truncate_modsec_log.sh';
		if ($bytes !== null) {
			$cmd .= ' -c ' . (int) $bytes;
		} else {
			$cmd .= ' -n ' . (int) $lines;
		}

		$out = shell_exec($cmd);

		if (env('PETE_DEBUG') === 'active') {
			\Log::info("truncate_security_log: {$cmd}");
			\Log::info($out ?? '[no output]');
		}
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

    public static function toggle_security($sw) {
		
		$pete_options = new PeteOption();
		$server = $pete_options->get_meta_value('server');
	    $server_conf = $pete_options->get_meta_value('server_conf');
		$debug = env('PETE_DEBUG');
		
		$base_path = base_path();
		chdir("$base_path/scripts/");

		$apache_reload_secret = env('APACHE_RELOAD_SECRET'); 

		$command = "sudo ./toggle_security.sh -v {$apache_reload_secret} -s {$sw} -k {$debug}";
		$output = shell_exec($command);
		
  	  	if($debug == "active"){
			Log::info("Action: securitystatus sw");
  			Log::info($command);
	  		Log::info("Output:");
			Log::info($output);
  	  	}
		
	}

    public static function toggle_server_status($sw) {
		
		$pete_options = new PeteOption();
		$server = $pete_options->get_meta_value('server');
	    $server_conf = $pete_options->get_meta_value('server_conf');
		$debug = env('PETE_DEBUG');
		
		$base_path = base_path();
		chdir("$base_path/scripts/");
		
		$command = "./toggle_server_status.sh -v {$server} -s {$sw} -b {$base_path} -k {$debug}";
		$output = shell_exec($command);
		
  	  	if($debug == "active"){
			Log::info("Action toggle_server_status");
  			Log::info($command);
	  		Log::info("Output:");
			Log::info($output);
  	  	}
		
	}

    public static function toggle_phpmyadmin($sw) {
		
		$pete_options = new PeteOption();
		$server = $pete_options->get_meta_value('server');
	    $server_conf = $pete_options->get_meta_value('server_conf');
		$debug = env('PETE_DEBUG');
		
		
		$base_path = base_path();
		chdir("$base_path/scripts/");

		$command = "sudo ./toggle_phpmyadmin.sh -v {$server} -s {$sw} -k {$debug}";
		$output = shell_exec($command);
		
  	  	if($debug == "active"){
			Log::info("Action: toggle_phpmyadmin sw");
  			Log::info($command);
	  		Log::info("Output:");
			Log::info($output);
  	  	}
		
	}

}