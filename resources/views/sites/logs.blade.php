@extends('layout')

@push('head')

@push('head')
<style>
/* macOS Light Terminal — simple (no buttons) */
.mac-term {
  border: 1px solid rgba(0,0,0,.08);
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 12px 30px rgba(0,0,0,.08);
  max-width: 1024px;
}

/* slim, neutral titlebar without controls */
.mac-term__titlebar{
  display:flex; align-items:center;
  padding:.5rem .75rem;
  background: linear-gradient(180deg, #f5f5f7, #ededf0);
  border-bottom:1px solid rgba(0,0,0,.06);
}

.mac-term__title{
  font-size:.85rem; color:#4a4a4a; user-select:none;
  font-family: ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono",monospace;
}

.mac-term__body{ padding:.75rem; background:#fff; }

.mac-term__output{
  margin:0;
  max-height:50vh;
  overflow:auto;
  white-space:pre-wrap;
  word-break:break-word;
  line-height:1.4;
  font-size:.86rem;
  color:#1c1c1c;
  font-family: ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono",monospace;
}

/* Optional: mac-like slim scrollbars */
.mac-term__output::-webkit-scrollbar{ width:10px; height:10px; }
.mac-term__output::-webkit-scrollbar-thumb{
  background: rgba(0,0,0,.18);
  border-radius: 8px; border: 2px solid #fff;
}
.mac-term__output::-webkit-scrollbar-track{ background: transparent; }
</style>
@endpush


<style>
  /* Bounded, scrollable container for big logs */
  .log-box{
    max-width: 1024px !important;
    max-height: 50vh !important;   /* adjust if you prefer a fixed height */
    overflow-y: auto !important;
    overflow-x: auto;
    background: #0b1020;           /* terminal-like background */
    color: #e6e6e6;
    border-radius: .375rem;
    border: 1px solid rgba(0,0,0,.08);
    display: block;
  }

  .panel{
    max-width: 1024px !important;
  }

  /* Preserve newlines; wrap long tokens; monospace for readability */
  .terminal-output{
    margin: 0;
    padding: 1rem;
    white-space: pre-wrap;      /* keep newlines, allow wrapping */
    word-break: break-word;     /* break long “words” if needed */
    overflow-wrap: anywhere;    /* last-resort breaks for giant tokens */
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    line-height: 1.45;
    font-size: .875rem;         /* small but readable */
  }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- updater placeholder (kept for consistency) --}}
  <div id="update_area_info"></div>

  {{-- hero ------------------------------------------------------------- --}}
  <div class="row align-items-center mb-4 g-3">
    <div class="col-md-7">
      <div id="site_logs"><h2 class="mb-1">Site Logs</h2></div>
      <p class="text-muted mb-0">
        for
        <strong>
          <a href="http://{{ $site->url }}" target="_blank" rel="noopener">{{ $site->url }}</a>
        </strong>
      </p>
    </div>
    <div class="col-md-5 d-flex gap-2 justify-content-md-end">
      <a href="{{ url('/sites') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Sites
      </a>
      <a href="http://{{ $site->url }}" target="_blank" rel="noopener" class="btn btn-pete">
        <i class="bi bi-box-arrow-up-right"></i> Open Site
      </a>
    </div>
  </div>

  {{-- details ---------------------------------------------------------- --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-5">Log Files</h3>
        </div>
        <div class="p-3 small">
          <div class="row gy-2">
            <div class="col-md-4">
              <div class="text-uppercase text-muted">WordPress debug.log</div>
              <div class="text-truncate" title="{{ $wordpress_debug_file ?? '' }}">
                {{ $wordpress_debug_file ?? '—' }}
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-uppercase text-muted">Apache error.log</div>
              <div class="text-truncate" title="{{ $web_server_error_file }}">
                {{ $web_server_error_file }}
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-uppercase text-muted">Apache access.log</div>
              <div class="text-truncate" title="{{ $web_server_access_file }}">
                {{ $web_server_access_file }}
              </div>
            </div>
          </div>
        </div>
        <div class="panel-footer small text-muted">
          Paths shown for reference; contents rendered below.
        </div>
      </div>
    </div>
  </div>

  @php
    // Strip ANSI color codes from terminal output (keeps the view clean)
    $terminalOutput = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $site->output ?? '');
  @endphp

  {{-- logs ------------------------------------------------------------- --}}
  <div class="row g-4">

    {{-- Terminal output ------------------------------------------------- --}}
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-6">
            <i class="bi bi-terminal me-1"></i> Terminal output
          </h3>
        </div>

        <div class="p-2">
          <div class="mac-term mac-term--simple">
            <div class="mac-term__titlebar">
              <span class="mac-term__title">bash — {{ $site->url }}</span>
            </div>
            <div class="mac-term__body" data-autoscroll="end">
              <pre class="mac-term__output">{{ $terminalOutput }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- WordPress debug.log -------------------------------------------- --}}
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-6">
            <i class="bi bi-bug me-1"></i> WordPress debug.log
          </h3>
          <small class="text-muted">{{ $wordpress_debug_file ?? '' }}</small>
        </div>
        <pre class="mb-0 small p-3 bg-light" style="max-height:420px; overflow:auto">{{ $wordpress_debug_file_content }}</pre>
      </div>
    </div>

    {{-- Apache error.log ------------------------------------------------ --}}
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-6">
            <i class="bi bi-exclamation-triangle me-1"></i> Apache error.log
          </h3>
          <small class="text-muted">{{ $web_server_error_file }}</small>
        </div>
        <pre class="mb-0 small p-3 bg-light" style="max-height:420px; overflow:auto">{{ $web_server_error_file_content }}</pre>
      </div>
    </div>

    {{-- Apache access.log ---------------------------------------------- --}}
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-6">
            <i class="bi bi-list-check me-1"></i> Apache access.log
          </h3>
          <small class="text-muted">{{ $web_server_access_file }}</small>
        </div>
        <pre class="mb-0 small p-3 bg-light" style="max-height:420px; overflow:auto">{{ $web_server_access_file_content }}</pre>
      </div>
    </div>

  </div>

</div>
@endsection

@push('scripts')

<script>
  // Auto-scroll any container marked with data-autoscroll="end"
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-autoscroll="end"]').forEach(function (el) {
      el.scrollTop = el.scrollHeight;
    });
  });
</script>
@endpush
