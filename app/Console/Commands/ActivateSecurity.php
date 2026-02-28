<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Option;
use App\Services\OServer;
use Log;

class ActivateSecurity extends Command
{
    protected $signature = 'activate:security';
    protected $description = 'Activate Mod Security if off';

    public function handle()
    {
        $securityStatus = Option::get_meta_value('security_status');
        $domain_template =  get_meta_value('domain_template');
        
        if($domain_template !="petelocal.net"){
            if(($securityStatus == "off") || ($securityStatus != "") || ($securityStatus == null)){
                $status = Option::create_or_update_meta('security_status', "on");
                OServer::toggle_security($status);
                OServer::reload_server();
            }
        }
        
    }
}