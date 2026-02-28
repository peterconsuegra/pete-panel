<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Option;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection; 
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Standard\Validator;
use App\Standard\License;

class PeteService
{

    private const OPTIONS_CACHE_KEY = 'options.map.v1';
    private const SIDEBAR_OPTIONS_CACHE_KEY = 'sidebar.map.v1';
    private const LAST_VERSION_CACHE_KEY = 'last.version.map.v1';
    private const PLGUINS_CACHE_KEY = 'plugins.map.v1';
    private const OPTION_TTL_MIN = 60;

    private $options;

    public function __construct()
    {
        $this->options = $this->getOptions();
        global $options;
        $options = $this->options;
    }
    
    public function validatePete($oemail,$oapi_key,$odomain){
		
 		$dashboard_url = env("PETE_DASHBOARD_URL");
		
 		$data = array(
 			"oemail" => $oemail,
 			"oapi_key" => $oapi_key,
 			"odomain" => $odomain
 		);
		
 		$options = array(
		 
 		  'http' => array(
 		    'method'  => 'GET',
 			'timeout' => 3000,
 			'content' => json_encode( $data ),
 		    'header'=>  "Content-Type: application/json\r\n" .
 		                "Accept: application/json\r\n"
 		    )
 		);
		
 		$context  = stream_context_create( $options );
 		$result = file_get_contents("$dashboard_url/validate_site_json", false, $context );
 		$decode_result = json_decode( $result );
		
 		if(isset($decode_result->message)){
 			return ["message" => $decode_result->message];
 		}else{
			
 			if(isset($decode_result->created_at_string)){
				
 				Option::delete_meta_value('created_at_string');
				
 				$option = new Option();
 				$option->option_name = "created_at_string";
 	    	    $option->option_value = $decode_result->created_at_string;
 				$option->visible = false;
 				$option->save();
 			}
			
 			if(isset($decode_result->token)){
				
 				Option::delete_meta_value('token');
				
 				$option = new Option();
 				$option->option_name = "token";
 	    	    $option->option_value = $decode_result->token;
 				$option->visible = false;
 				$option->save();
 			}
			
 			if(isset($odomain)){
				
 				Option::delete_meta_value('validation_domain');
			
 				$option = new Option();
 				$option->option_name = "validation_domain";
 	    	    $option->option_value = $odomain;
 				$option->visible = false;
 				$option->save();
 			}
			
 			if(isset($decode_result->user_email)){
				
 				Option::delete_meta_value('validation_user_email');
				
 				$option = new Option();
 				$option->option_name = "validation_user_email";
 	    	    $option->option_value = $decode_result->user_email;
 				$option->visible = false;
 				$option->save();
 			}
 			if(isset($decode_result->api_key)){
				
 				Option::delete_meta_value('validation_api_key');
				
 				$option = new Option();
 				$option->option_name = "validation_api_key";
 	    	    $option->option_value = $decode_result->api_key;
 				$option->visible = false;
 				$option->save();
				
 				Option::delete_meta_value('validation_event');
					
 				$option = new Option();
 				$option->option_name = "validation_event";
 				$option->option_value = "validation_event";
 				$option->option_date = date('Y-m-d H:i:s',strtotime('+1 year'));
 				$option->visible = false;
 				$option->save();
 			}
		
 			return ["validation" => 'ok'];
		
 		}
 	}

    public function checkPeteLicense(){

        $pete_options = app(\App\Services\PeteOption::class);

        $validation_api_key = $pete_options->get_meta_value('validation_api_key');
 		$validation_user_email = $pete_options->get_meta_value('validation_user_email');
 		$token = $pete_options->get_meta_value('token');
 		$created_at_string = $pete_options->get_meta_value('created_at_string');	

 		$license = new License($validation_user_email, $created_at_string,"WordpressPete",$token,"wordpresspete.com");
 		$validator = new Validator();
 		if($validator->validate($license, $validation_api_key)) {
 			$validate = "YES";
 		} else {
 			$validate = "NO";
 		} 
		
 		return $validate;
 	}
    

    public function getOptions(): Collection
    {
        return Option::query()
                    ->select(['option_name','option_value','option_date','version','created_at','updated_at'])
                    ->get()
                    ->keyBy('option_name')
                    ->toBase(); 
       
    }

    public function getSidebarOptions(): Collection
    {
        return Option::query()
                ->where('category', 'sidebar')
                ->orderBy('created_at')
                ->get()
                ->toBase();
    }

    public function getLastVersion(): ?float
	{
		try {
			$parentVersion = (string) ($this->options->get('parent_version')->option_value ?? '');
            Log::info("enter in getLastVersion");
            Log::info($parentVersion);
			$dashboardUrl  = rtrim(env('PETE_DASHBOARD_URL', ''), '/');

			if ($dashboardUrl === '') {
				\Log::warning('Pete version_check: PETE_DASHBOARD_URL is empty');
				return null;
			}

			$url = $dashboardUrl . '/update_button_json?parent_version=' . rawurlencode($parentVersion);

			
				$opts = [
					'http' => [
						'method'        => 'GET',
						'timeout'       => 5,
						'ignore_errors' => true,
						'header'        => "Accept: application/json\r\n",
					],
				];

				$context  = stream_context_create($opts);
				$response = @file_get_contents($url, false, $context);

				if ($response === false) {
					\Log::warning("Pete version_check: No response from {$url}");
					return null;
				}

				$response = trim($response);
				if (str_starts_with($response, '(') && str_ends_with($response, ')')) {
					$response = substr($response, 1, -1);
				}

				$data = json_decode($response, true);
				if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
					\Log::error("Pete version_check: JSON decode error - " . json_last_error_msg());
					return null;
				}

				$version = $data['version'] ?? null;
				\Log::info('Fetched Pete version', ['version' => $version]);

				return $version;
                
		} catch (\Throwable $e) {
			\Log::error("Pete version_check exception: " . $e->getMessage());
			return null;
		}
	}

    /** ---- Plugins catalog (cached) ---- */

    public function getPlugins(): \stdClass
    {
        $dashboardUrl = (string) env('PETE_DASHBOARD_URL');
        $url          = rtrim($dashboardUrl, '/').'/plugins_json';

        
            $opts = [
                'http' => [
                    'method'        => 'GET',
                    'timeout'       => 5,
                    'ignore_errors' => true,
                    'header'        => "Content-Type: application/json\r\nAccept: application/json\r\n",
                ],
            ];

            $context = stream_context_create($opts);
            $result  = @file_get_contents($url, false, $context);

            if ($result === false) {
                throw new \RuntimeException("No response from {$url}");
            }

            $data = json_decode($result);
            if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
                throw new \RuntimeException("Invalid JSON from {$url}: ".json_last_error_msg());
            }

            $data->pete_plugins = is_array($data->pete_plugins ?? null) ? $data->pete_plugins : [];
            $data->wp_plugins   = is_array($data->wp_plugins   ?? null) ? $data->wp_plugins   : [];

            Log::info('Fetched plugin catalog', [
                'url'  => $url,
                'pete' => count($data->pete_plugins),
                'wp'   => count($data->wp_plugins),
            ]);

            return $data;
    }

    /** ---- Shell-based flows (kept together; easy to split later) ---- */

    public function runUpdateScript(array $args): void
    {
        $version  = $this->sanitizeParam((string) ($args['latest'] ?? ''));
        $repoRoot = base_path();
        $tmpDir   = '/var/www';

        $commands = [
            'cp composer.json '.$tmpDir.'/composer.json',
            'cp composer.lock '.$tmpDir.'/composer.lock',
            'git config --global --add safe.directory '.$repoRoot,
            'git add --all',
            'git commit -am "upgrading to '.$version.'"',
            'git fetch --tags',
            'git checkout tags/'.$version,
            'mv '.$tmpDir.'/composer.json composer.json',
            'mv '.$tmpDir.'/composer.lock composer.lock',
            'php artisan addoption --option_name=version --option_value='.$version,
            'php artisan cache:clear',
            'composer require pion/laravel-chunk-upload:^1.5',
            'php artisan view:clear',
            'php artisan config:clear',
            'php artisan route:clear',
            'php artisan migrate --force',
            'php artisan opcache:refresh'
        ];

        $this->runSystemScript($commands);
    }

    public function runPluginInstallScript(array $args): void
    {
        $packageName   = $this->sanitizeParam((string) ($args['packageName']   ?? ''));
        $pluginRoute   = $this->sanitizeParam((string) ($args['pluginRoute']   ?? ''));
        $pluginVersion = $this->sanitizeParam((string) ($args['pluginVersion'] ?? ''));
        $optionName    = $this->sanitizeParam((string) ($args['optionName']    ?? ''));

        $commands = [
            "composer require {$packageName} --ignore-platform-reqs",
            'composer dump-autoload --ignore-platform-reqs',
            'php artisan addoption --option_name='.$optionName
                .' --option_value='.$pluginRoute
                .' --option_category=sidebar'
                .' --option_version='.$pluginVersion,
            'php artisan route:cache',
            'php artisan cache:clear',
            'php artisan config:clear',
            'php artisan view:clear',
            'php artisan opcache:refresh'
        ];

        $this->runSystemScript($commands);
    }

    public function runPluginUninstallScript(array $args): void
    {
        $packageName = $this->sanitizeParam((string) ($args['packageName'] ?? ''));
        $optionName  = $this->sanitizeParam((string) ($args['optionName']  ?? ''));

        $commands = [
            "COMPOSER_DISCARD_CHANGES=true composer remove {$packageName} --no-interaction --ignore-platform-reqs",
            "php artisan removeoption --option_name={$optionName}",
            'composer dump-autoload --ignore-platform-reqs',
            'php artisan route:cache',
            'php artisan cache:clear',
            'php artisan config:clear',
            'php artisan view:clear',
            'php artisan opcache:refresh'
        ];

        $this->runSystemScript($commands);
    }

    public function runPluginUpdateScript(array $args): void
    {
        $packageName   = $this->sanitizeParam((string) ($args['packageName']   ?? ''));
        $optionName    = $this->sanitizeParam((string) ($args['optionName']    ?? ''));
        $optionVersion = $this->sanitizeParam((string) ($args['optionVersion'] ?? ''));

        $commands = [
            "composer update {$packageName}",
            'php artisan updateoption:version --option_name='.$optionName.' --set_version='.$optionVersion,
            'composer dump-autoload --ignore-platform-reqs',
            'php artisan route:cache',
            'php artisan cache:clear',
            'php artisan config:clear',
            'php artisan view:clear',
            'php artisan opcache:refresh'
        ];

        $this->runSystemScript($commands);
    }

    private function runSystemScript(array $commands): void
    {
        putenv("COMPOSER_HOME=/usr/local/bin/composer");
        putenv("COMPOSER_CACHE_DIR=~/.composer/cache");

        $basePath = base_path();
        chdir($basePath);

        foreach ($commands as $command) {
            $safe = escapeshellcmd($command);
            if ($safe !== '') {
                Log::info("command: ".$safe);
                $output = shell_exec($safe) ?? '';
                Log::info("output: ".$output);
            }
        }
    }

    private function sanitizeParam(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', '', $value) ?? '';
        $value = preg_replace('/[^A-Za-z0-9.+_\/-]/', '', $value) ?? '';
        return $value;
    }

    public function fail(Request $r, string $msg, array $errors = [], int $status = 422): RedirectResponse|JsonResponse
    {
        return ($r->expectsJson() || $r->ajax())
            ? response()->json(['error' => true, 'message' => $msg, 'errors' => $errors], $status)
            : back()->withInput()->withErrors($msg);
    }

    /**
     * If a domain template like "mydev.net" is present and the user typed only a subdomain
     * (no dot), append it => "sub.mydev.net". Also strip protocol & trailing slashes.
     */
    public function normalizeUrlWithTemplate(string $raw, string $template = ''): string
    {
        $host = trim(preg_replace('#^https?://#i', '', $raw) ?? '');
        $host = rtrim($host, '/');
        if ($template && !str_contains($host, '.')) {
            $host = "{$host}.{$template}";
        }
        return $host;
    }

    public function isTheURLForbidden($url){
		$forbiddenURLs = $this->options->get('forbidden_urls');
        if(isset($forbiddenURLs)){
            $forbiddenURLsArray = explode(",",$forbiddenURLs);
            if (in_array($url, $forbiddenURLsArray)) {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
	}

    public function forgetPluginsCache(): void
    {
        Cache::forget(self::PLGUINS_CACHE_KEY);
        Log::info('PeteService: PluginsCache map cache cleared');
    }

    public function forgetOptionsCache(): void
    {
        Cache::forget(self::OPTIONS_CACHE_KEY);
        Log::info('PeteService: options map cache cleared');
    }

    public function forgetSidebarOptionsCache(): void
    {
        Cache::forget(self::SIDEBAR_OPTIONS_CACHE_KEY);
        Log::info('PeteService: sidebar options map cache cleared');
    }

    public function forgetLastVersionCache(): void
    {
        Cache::forget(self::LAST_VERSION_CACHE_KEY);
        Log::info('PeteService: last_version cache cleared');
    }
}