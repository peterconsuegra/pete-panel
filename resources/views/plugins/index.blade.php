@extends('layout')

@section('content')

@if(!empty($error))
  <div class="alert alert-danger">
    <strong>Oops!</strong> We couldn't load the plugin catalog.
    @if(!empty($error_message))
      <div class="small text-muted mt-1">{{ $error_message }}</div>
    @endif
  </div>
  
@else

@if(session('error_message'))
  <div class="alert alert-danger">
    <strong>Error:</strong> {{ session('error_message') }}
  </div>
@endif


@php
    
@endphp

<div class="container-fluid">

    {{-- hero ------------------------------------------------------------- --}}
    <div class="row align-items-center mb-5 g-4">
        <div class="col-md-6 text-center text-md-start">
            <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:200px">
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center">
            <h2 class="mb-1">WordPress Pete Plugins</h2>
            <p class="text-muted mb-0">Essential tools to integrate, migrate, and protect your WordPress environments.</p>
        </div>
    </div>

    {{-- error state ------------------------------------------------------ --}}
    @if(!empty($error))
        <div class="alert alert-danger">
            <strong>Oops!</strong> An error occurred. Please check your internet connection or try again later.
        </div>
    @else

    {{-- P ete plugins ---------------------------------------------------- --}}
    <div class="panel mb-5">
        <div class="panel-heading">
            <h3 class="mb-0 fs-5">Pete Plugins</h3>
            <small class="text-muted">Install first-party tools maintained for WordPress Pete.</small>
        </div>

        <div class="p-3 p-md-4">
            <div class="row g-4">

               

                @foreach($pete_plugins as $plugin)
                    @php
                        // Canonical inputs
                        $pkg         = trim((string) $plugin->package_folder);        // e.g. peteconsuegra/wordpress-plus-laravel-plugin
                        $latestStr   = trim((string) $plugin->version);               // e.g. "1.50"
                        $latestStr   = $latestStr === '' ? '0' : $latestStr;

                        // Normalize option_name (dashboard may send "wordpress+laravel", installer saved "wordpress_plus_laravel")
                        $optKeyRaw   = trim((string) $plugin->option_name);           // e.g. "wordpress+laravel"
                        $optKey      = strtr($optKeyRaw, ['+' => '_', ' ' => '_']);   // -> "wordpress_laravel" or "wordpress_plus_laravel"
                        // Be tolerant with both expected variants
                        $optAltKey   = str_contains($optKey, '_plus_')
                                        ? str_replace('_plus_', '+', $optKey)
                                        : str_replace('+', '_plus_', $optKey);

                        // Read version from Composer if available; fall back to the stored option version
                        $isInstalled = false;
                        $composerVer = '';
                        try {
                            if (class_exists(\Composer\InstalledVersions::class) && \Composer\InstalledVersions::isInstalled($pkg)) {
                                $isInstalled = true;
                                $composerVer = (string) (\Composer\InstalledVersions::getPrettyVersion($pkg) ?? '');
                            }
                        } catch (\Throwable $e) {
                            // Composer metadata not available; keep isInstalled detection below
                        }

                        // Fallback isInstalled if Composer class isn’t present
                        if (!$isInstalled) {
                            $isInstalled = is_dir(base_path('vendor/' . $pkg));
                        }

                        // Pull option version using the normalized key(s)
                        $optVer = (string) ($pete_options->get_meta_version($optKey) ?? '');
                        if ($optVer === '') {
                            $optVer = (string) ($pete_options->get_meta_version($optAltKey) ?? '');
                        }

                        // Choose the best known installed version string
                        $installedStr = $composerVer !== '' ? $composerVer : ($optVer !== '' ? $optVer : '0');

                        // Decide if update is needed using strings (not floats)
                        $hasUpdate = $isInstalled && version_compare($installedStr, $latestStr, '<');

                        $cardTitleId = 'plugin_' . preg_replace('/\s+/', '_', strtolower($plugin->title));
                    @endphp

                    <div class="col-12 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <img src="{{ $dashboard_url }}/plugins_images/{{ $plugin->image }}" class="card-img-top" alt="{{ $plugin->title }}">

                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0" id="{{ $cardTitleId }}">{{ $plugin->title }}</h5>
                                    @if($isInstalled)
                                        <span class="badge text-bg-success">Installed</span>
                                    @else
                                        <span class="badge text-bg-secondary">Available</span>
                                    @endif
                                </div>

                                @if($isInstalled)
                                    <div class="small text-muted mb-2">
                                        Installed version: <code>{{ $installedStr }}</code>
                                    </div>
                                @endif

                                <p class="card-text flex-grow-1">{{ $plugin->description }}</p>

                                @if($hasUpdate)
                                    <div class="alert alert-warning py-2 small mb-3">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        New version available: <strong>{{ $latestStr }}</strong>
                                    </div>
                                @endif

                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @if(!$isInstalled)
                                        {{-- Install --}}
                                        <form method="POST" action="{{ route('plugins.install') }}">
                                            @csrf
                                            <input type="hidden" name="pluginTitle" value="{{ $plugin->title }}">
                                            <input type="hidden" name="packageName" value="{{ $plugin->package_folder }}">
                                            <input type="hidden" name="pluginVersion" value="{{ $latestStr }}">
                                            <input type="hidden" name="pluginRoute" value="{{ $plugin->redirect_after_install }}">
                                            <input type="hidden" name="optionName" value="{{ $optKey }}">
                                            <button type="submit" class="btn btn-pete btn-sm" id="install_{{ str_replace(' ', '', $plugin->title) }}">
                                                <i class="bi bi-plug me-1"></i> Install
                                            </button>
                                        </form>
                                    @else
                                        {{-- Uninstall --}}
                                        <form class="d-inline" action="{{ route('plugins.uninstall') }}" method="POST" onsubmit="return confirm('Uninstall {{ $plugin->title }}?');">
                                            @csrf
                                            <input type="hidden" name="packageName" value="{{ $plugin->package_folder }}">
                                            <input type="hidden" name="optionName"  value="{{ $optKey }}">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" id="uninstall_{{ str_replace(' ', '', $plugin->title) }}">
                                                <i class="bi bi-trash me-1"></i> Uninstall
                                            </button>
                                        </form>

                                        {{-- Update when needed --}}
                                        @if($hasUpdate)
                                            <form method="POST" action="{{ route('plugins.update') }}">
                                                @csrf
                                                <input type="hidden" name="pluginTitle"   value="{{ $plugin->title }}">
                                                <input type="hidden" name="packageName"   value="{{ $plugin->package_folder }}">
                                                <input type="hidden" name="pluginVersion" value="{{ $latestStr }}">
                                                <input type="hidden" name="pluginRoute"   value="{{ $plugin->redirect_after_install }}">
                                                <input type="hidden" name="optionName"    value="{{ $optKey }}">
                                                <input type="hidden" name="optionVersion" value="{{ $latestStr }}">
                                                <button type="submit" class="btn btn-warning btn-sm" id="update_{{ str_replace(' ', '', $plugin->title) }}">
                                                    <i class="bi bi-arrow-repeat me-1"></i> Update
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if(empty($pete_plugins))
                    <div class="col-12">
                        <div class="p-5 text-center text-muted">No Pete plugins available.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- WordPress plugins ------------------------------------------------ --}}
    <div class="panel">
        <div class="panel-heading">
            <h3 class="mb-0 fs-5">WordPress Plugins</h3>
            <small class="text-muted">Extend WordPress. Export smarter. Sync seamlessly.</small>
        </div>

        <div class="p-3 p-md-4">
            <div class="row g-4">
                @foreach($wp_plugins as $plugin)
                    <div class="col-12 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <img src="{{ $dashboard_url }}/wp_plugins_images/{{ $plugin->image }}"
                                 class="card-img-top"
                                 alt="{{ $plugin->title }}">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $plugin->title }}</h5>
                                <p class="card-text flex-grow-1">{{ $plugin->description }}</p>
                                <div>
                                    <a href="{{ $plugin->url }}"
                                       class="btn btn-outline-secondary btn-sm"
                                       target="_blank" rel="noopener">
                                        <i class="bi bi-box-arrow-down me-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if(empty($wp_plugins))
                    <div class="col-12">
                        <div class="p-5 text-center text-muted">No WordPress plugins listed.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @endif {{-- /error --}}
</div>

@push('scripts')
<script>
(function(){
    // Optional: disable clicked submit buttons to prevent double submits
    document.querySelectorAll('form').forEach(function(f){
        f.addEventListener('submit', function(e){
            const btn = f.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + btn.innerText.replace(/^\s*/, ' ');
            }
            if (typeof activate_loader === 'function') activate_loader();
        });
    });
})();
</script>
@endpush

@endif

@endsection
