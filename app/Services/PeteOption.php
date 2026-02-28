<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

use App\Services\PeteService;

class PeteOption
{
	 private $options;

    public function __construct(bool $forceRefresh = false)
    {
        // Resolve the service PeteService singleton pattern, and pull the cached map
        /** @var PeteService $pete */
        $pete = app(PeteService::class);

        $this->options = $pete->getOptions($forceRefresh);

        // Backward compatibility for legacy code using global $options
        global $options;
        $options = $this->options;
    }

    public function get_meta_version($meta_key)
    {
        $row = $this->options->get($meta_key);
        return $row->version ?? '';
    }

    public function get_meta_value($meta_key)
    {
        $row = $this->options->get($meta_key);
        return $row->option_value ?? '';
    }

    public function get_meta_date($meta_key)
    {
        $row = $this->options->get($meta_key);
        return $row->option_date ?? '';
    }

}
