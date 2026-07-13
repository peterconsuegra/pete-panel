@extends('layout')

@section('content')
<div class="container-fluid">
    {{-- Page hero --}}
    <div class="row align-items-center mb-5 g-4">
        <div class="col-md-6 text-center text-md-start">
            <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:160px">
        </div>
        <div class="col-md-6">
            <div class="text-center text-md-start">
                <h1 class="h3 mb-2">Pete Migration Plugin</h1>
                <p class="text-muted mb-0">
                    A WordPress plugin that converts <strong>any</strong> WordPress site into the
                    Pete Panel format, so you can import it into this panel in one click.
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-heading d-flex justify-content-between align-items-center">
                    <h2 class="fs-5 mb-0">Site Migration &amp; Backup Export</h2>
                    <small class="text-muted">v1.0.0 &middot; GPLv3</small>
                </div>

                <div class="p-4">
                    {{-- Download CTA --}}
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                        <a href="{{ route('base.migration.plugin.download') }}" class="btn btn-pete">
                            <i class="bi bi-download me-1"></i> Download latest plugin (.zip)
                        </a>
                        <span class="text-muted">Install it on the site you want to migrate, not on this panel.</span>
                    </div>
                    <p class="text-muted mb-4">
                        <i class="bi bi-arrow-repeat me-1"></i>Served live from the Pete dashboard, so you always get the
                        newest release — independent of this panel's version.
                    </p>

                    {{-- How it works --}}
                    <h3 class="fs-6 fw-bold">How it works</h3>
                    <ol class="mb-4">
                        <li class="mb-2">Install and activate the plugin on the <strong>source</strong> WordPress site (Plugins &rarr; Add New &rarr; Upload Plugin).</li>
                        <li class="mb-2">In that site's WP Admin, open <strong>Pete Converter</strong> and click <strong>Start export</strong>. The export of all files and the database runs in the background via WP-Cron; if the host blocks WP-Cron, the UI offers a force-run fallback that executes it directly.</li>
                        <li class="mb-2">When it finishes, a secure <strong>admin-only download link</strong> appears. Sensible exclusions are applied automatically (secrets, cache and other heavy folders), and the archive is deleted from the source server after a successful download.</li>
                        <li class="mb-2">Download the generated ZIP to your computer.</li>
                        <li>Come back to this panel and upload it in <a href="/wordpress-importer">Import</a> — Pete Panel recreates the site (files + database) under the URL you choose.</li>
                    </ol>

                    {{-- Archive format --}}
                    <h3 class="fs-6 fw-bold">The Pete Panel archive format</h3>
                    <p class="text-muted">The plugin packages the site exactly the way this panel's importer expects. The ZIP root contains (no wrapper folder):</p>
                    <pre class="bg-light border rounded p-3 mb-3" style="line-height:1.7;"><code>site-export.zip
├── filem/        &larr; every file of the WordPress installation
├── query.sql     &larr; full database dump
└── config.txt    &larr; site metadata used during import</code></pre>
                    <p class="text-muted mb-0">
                        During import, Pete Panel extracts the archive into a <code>massive_file/</code> working
                        directory, restores <code>filem/</code> as the site's document root, loads
                        <code>query.sql</code> into a fresh database, and rewrites URLs and paths using
                        <code>config.txt</code>. Because the structure is generated automatically, any WordPress
                        site — regardless of host, theme, or page builder — becomes a valid Pete Panel import.
                    </p>
                </div>

                <div class="panel-footer">
                    <small class="text-muted">
                        Already have an export? Go straight to <a href="/wordpress-importer">Import</a>.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
