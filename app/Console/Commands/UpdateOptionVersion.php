<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Option;

class UpdateOptionVersion extends Command
{
    /**
     * Usage:
     *  php artisan updateoption:version --option_name=site_url --set_version=1.2.3
     *  php artisan updateoption:version --option_value=my_key --set_version=2025-08-21
     *  php artisan updateoption:version --id=42 --set_version=9
     */
    protected $signature = 'updateoption:version
        {--id= : Update by id (highest priority)}
        {--option_name= : Filter by option_name (spaces will be stripped)}
        {--option_value= : Filter by option_value (spaces will be stripped)}
        {--set_version= : New value for option_version (required)}';

    protected $description = 'Update only the option_version attribute of an Option record.';

    public function handle(): int
    {
        // Validate target version
        $newVersion = $this->option('set_version');
        if ($newVersion === null) {
            $this->error('Missing --set_version= argument.');
            return self::FAILURE;
        }
        if (is_string($newVersion)) {
            $newVersion = trim($newVersion);
        }
        if ($newVersion === '') {
            $this->error('The --set_version= value cannot be empty.');
            return self::FAILURE;
        }

        // Build finder (id > name/value)
        $id = $this->option('id');
        $nameFilter = $this->option('option_name');
        $valFilter  = $this->option('option_value');

        if (is_string($nameFilter)) {
            $nameFilter = preg_replace('/\s+/', '', $nameFilter);
        }
        if (is_string($valFilter)) {
            $valFilter = preg_replace('/\s+/', '', $valFilter);
        }

        if (!$id && !$nameFilter && !$valFilter) {
            $this->error('Provide a target: --id= or --option_name= or --option_value=');
            return self::FAILURE;
        }

        // Locate the record (mirror your removeoption semantics: first match)
        if ($id) {
            $option = Option::find($id);
        } else {
            $query = Option::query();
            if ($nameFilter) {
                $query->where('option_name', $nameFilter);
            }
            if ($valFilter) {
                $query->where('option_value', $valFilter);
            }
            $option = $query->first();
        }

        if (!$option) {
            $this->error('No matching Option found.');
            return self::FAILURE;
        }

        // Update only option_version
        $before = $option->version ?? null;
        $option->version = $newVersion;
        $option->save();

        $this->info('Option updated successfully.');
        $this->line('  id:             ' . $option->id);
        $this->line('  option_name:    ' . $option->option_name);
        $this->line('  option_value:   ' . $option->option_value);
        $this->line('  option_version: ' . var_export($before, true) . '  =>  ' . $option->version);

        return self::SUCCESS;
    }
}
