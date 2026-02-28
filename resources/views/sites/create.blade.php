@extends('layout')

@push('head')
<style>
  /* Spinner via pseudo-element; never touches the button label */
  #create_button[data-loading="1"]{ position:relative; }
  #create_button[data-loading="1"]::after{
    content:"";
    display:inline-block;
    width:.9rem;height:.9rem;margin-left:.5rem;
    border:.15rem solid currentColor;border-right-color:transparent;
    border-radius:50%;
    vertical-align:-2px;
    animation:pete-spin .6s linear infinite;
  }

.btn-pete:disabled,
.btn-pete[disabled] {
  opacity: 1 !important;                /* keep full opacity */
  cursor: not-allowed;                  /* show "busy" cursor */
  color: #fff !important;               /* keep white text */
  background-image: linear-gradient(135deg, var(--pete-blue) 0%, var(--pete-green) 100%) !important;
  box-shadow: 0 3px 8px rgba(0,0,0,.2) !important;
}

/* Optional: add a subtle "loading" look */
.btn-pete[data-loading="1"] {
  filter: brightness(0.95);             /* darken slightly */
}

  @keyframes pete-spin { to { transform: rotate(360deg); } }
  /* Make sure disabled styles never hide label */
  #create_button .btn-text{ visibility:visible !important; opacity:1 !important; }

  /* Pete button disabled state */
.btn-pete:disabled,
.btn-pete[disabled] {
  opacity: 1 !important;  
  cursor: not-allowed;  
  color: #fff !important;  
  background-image: linear-gradient(135deg, var(--pete-blue) 0%, var(--pete-green) 100%) !important;
  box-shadow: 0 3px 8px rgba(0,0,0,.2) !important;
  pointer-events: none; /* block hover/focus/active */
}

/* Explicitly neutralize hover, focus, active */
.btn-pete:disabled:hover,
.btn-pete:disabled:focus,
.btn-pete:disabled:active,
.btn-pete[disabled]:hover,
.btn-pete[disabled]:focus,
.btn-pete[disabled]:active {
  background-image: linear-gradient(135deg, var(--pete-blue) 0%, var(--pete-green) 100%) !important;
  color: #fff !important;
  box-shadow: 0 3px 8px rgba(0,0,0,.2) !important;
  transform: none !important; /* no "lift" effect */
  text-decoration: none !important;
}

</style>
@endpush

@section('content')
@php
    $template = $template ?? (isset($pete_options) ? ($pete_options->get_meta_value('domain_template') ?? null) : null);
@endphp

<div id="createApp" class="container-fluid" v-cloak>

    {{-- hero ------------------------------------------------------------- --}}
    <div class="row align-items-center mb-5 g-4">
        <div class="col-md-6 text-center text-md-start">
            <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:200px">
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center">
            <h2 class="mb-1">Create a new WordPress site</h2>
            <p class="text-muted mb-0">Spin up a fresh instance in seconds.</p>
        </div>
    </div>

    {{-- form card -------------------------------------------------------- --}}
    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="panel position-relative">
                <div class="panel-heading d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fs-5">Site details</h3>
                    <a href="{{ route('sites.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to sites
                    </a>
                </div>

                <div class="p-3 p-md-4">
                    {{-- blade fallback flashes, Vue handles live errors below --}}
                    @if(session('status'))
                        <div class="alert alert-success mb-4">{{ session('status') }}</div>
                    @endif

                    {{-- Vue validation / server errors --}}
                    <div v-if="Object.keys(errors).length" class="alert alert-danger mb-4">
                        <strong>There were some problems with your input:</strong>
                        <ul class="mb-0">
                            <li v-for="(msgs, field) in errors" :key="field">
                                <span v-for="(m, i) in [].concat(msgs)" :key="i">@{{ m }}</span>
                            </li>
                        </ul>
                    </div>

                    <form id="SiteForm" @submit.prevent="submit" autocomplete="off" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="url-field" class="form-label">URL</label>

                            @if($template && $template !== 'none')
                                <div class="input-group">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="url-field"
                                        name="url"
                                        placeholder="subdomain"
                                        v-model.trim="urlInput"
                                        required
                                        aria-describedby="urlHelp"
                                    >
                                    <span class="input-group-text">.{{ $template }}</span>
                                </div>
                                <div id="urlHelp" class="form-text">
                                    Enter only the subdomain; Pete appends <code>.{{ $template }}</code> automatically.
                                </div>
                            @else
                                <input
                                    type="text"
                                    class="form-control"
                                    id="url-field"
                                    name="url"
                                    placeholder="e.g. mysite.com"
                                    v-model.trim="urlInput"
                                    required
                                    aria-describedby="urlHelp"
                                >
                                <div id="urlHelp" class="form-text">
                                    Use a valid host/domain (don’t include protocol).
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            <button type="submit"
                                    id="create_button"
                                    class="btn btn-pete d-inline-flex align-items-center gap-2"
                                    :disabled="sending || !urlInput"
                                    :data-loading="sending ? 1 : null">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                <span class="btn-text">Create</span>
                            </button>

                            <a href="{{ route('sites.index') }}" class="btn btn-outline-secondary">Cancel</a>

                            <span id="creating_hint"
                                  class="ms-2 text-muted small"
                                  aria-live="polite"
                                  v-show="sending">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Provisioning…
                            </span>
                        </div>
                    </form>
                </div>

                {{-- Provisioning steps (skeleton) -------------------------------- --}}
                <div id="provision_skeleton" class="border-top px-3 px-md-4 py-3" v-show="sending">
                    <style>
                        .skel-row{
                            height:14px;border-radius:6px;
                            background:linear-gradient(90deg,#e9ecef 25%,#f8f9fa 37%,#e9ecef 63%);
                            background-size:400% 100%;
                            animation:skel 1.2s ease-in-out infinite;
                        }
                        @keyframes skel{ 0%{background-position:100% 0} 100%{background-position:0 0} }
                        .step-dot{ width:.75rem;height:.75rem;border-radius:50%;background-color:#ced4da;display:inline-block; }
                        .step.active .step-dot{ background-color:#0d6efd; }
                        .step-label{ font-size:.9rem }
                    </style>

                    <div class="d-flex align-items-center gap-3 mb-2">
                        <strong class="me-1">Setting things up…</strong>
                        <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                    </div>

                    <div class="vstack gap-2">
                        <div class="d-flex align-items-center gap-2 step active">
                            <span class="step-dot"></span>
                            <div class="step-label">Creating project files</div>
                        </div>
                        <div class="skel-row" style="width:55%"></div>

                        <div class="d-flex align-items-center gap-2 step active">
                            <span class="step-dot"></span>
                            <div class="step-label">Installing WordPress</div>
                        </div>
                        <div class="skel-row" style="width:70%"></div>

                        <div class="d-flex align-items-center gap-2 step">
                            <span class="step-dot"></span>
                            <div class="step-label">Configuring database</div>
                        </div>
                        <div class="skel-row" style="width:62%"></div>

                        <div class="d-flex align-items-center gap-2 step">
                            <span class="step-dot"></span>
                            <div class="step-label">Reloading web server</div>
                        </div>
                        <div class="skel-row" style="width:48%"></div>
                    </div>
                </div>

                <div class="panel-footer small text-muted">
                    WordPress Pete will provision WordPress and reload the web server automatically.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  'use strict';
  const { createApp, ref, reactive, onMounted } = Vue;

  createApp({
    setup(){
      const sending = ref(false);
      const urlInput = ref('');
      const errors = reactive({});

      function normalizeHost(val){
        return (val || '').trim().replace(/^https?:\/\//i, '').replace(/\/+$/,'');
      }

      async function submit(){
        // clear errors
        for (const k of Object.keys(errors)) delete errors[k];

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const value = normalizeHost(urlInput.value);
        if (!value){ errors.url = ['Please enter a URL/subdomain.']; return; }

        sending.value = true;
        try{
          const res = await fetch(@json(route('sites.store')), {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ url: value })
          });

          if (res.status === 422){
            const data = await res.json().catch(()=>({}));
            Object.assign(errors, data.errors || { form: [data.message || 'Validation failed.'] });
            sending.value = false;
            return;
          }

          if (!res.ok){
            // Could be a 302 that fetch followed (still ok), or a genuine error.
            // Treat 200-299 as success; otherwise show a toast and stop.
            window.toast && window.toast('Failed to create WordPress site. Please try again.', 'error');
            sending.value = false;
            return;
          }

          // Success (controller will have started provisioning); go to /sites.
          window.location.assign('/sites');
        }catch(err){
          console.error(err);
          window.toast && window.toast('Unexpected error. Please try again.', 'error');
          sending.value = false;
        }
      }

      onMounted(() => {
        // focus input shortly after mount
        setTimeout(() => {
          try { document.getElementById('url-field')?.focus(); } catch(_) {}
        }, 80);
      });

      return { sending, urlInput, errors, submit };
    }
  }).mount('#createApp');
})();
</script>
@endpush
