@extends('layout')

@section('content')
<div class="container-fluid" id="licenseApp" v-cloak>
    {{-- Page hero --}}
    <div class="row align-items-center mb-5 g-4">
        <div class="col-md-6 text-center text-md-start">
            <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:160px">
        </div>
        <div class="col-md-6">
            <div class="text-center text-md-start">
                <h1 class="h3 mb-2">Register your License</h1>
                <p class="text-muted mb-0">
                    Unlock premium plugins and updates. Need a license?
                    <a href="https://wordpresspete.com/pricing/" target="_blank" rel="noopener">See pricing</a>.
                </p>
            </div>
        </div>
    </div>

    {{-- Card --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-heading d-flex justify-content-between align-items-center">
                    <h2 class="fs-5 mb-0">License Details</h2>
                    @isset($pete_options)
                        <small class="text-muted">
                            Pete {{ $pete_options->get_meta_value('version') }}
                        </small>
                    @endisset
                </div>

                <div class="p-4">
                    {{-- Upgrade / limit messages flashed by guarded routes --}}
                    @if ($errors->any())
                    <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                    @endif

                    {{-- License validated alert --}}
                    @if($validate == "YES")
                    <div class="alert alert-success d-flex align-items-start gap-2" role="alert">
                        <i class="bi bi-check-circle-fill mt-1"></i>
                        <div>
                            Your license is validated against
                            <code>{{ $validation_url ?? env('PETE_DASHBOARD_URL') }}</code>.
                        </div>
                    </div>
                    @endif

                    {{-- Form --}}
                    <form class="needs-validation" novalidate @submit.prevent="submitForm" :class="{'was-validated': wasValidated}">
                        @csrf

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="oemail" class="form-label">Registered Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="oemail"
                                    name="oemail"
                                    placeholder="name@company.com"
                                    v-model.trim="form.oemail"
                                    required
                                >
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                        </div>

                        {{-- API Key --}}
                        <div class="mb-3">
                            <label for="oapi_key" class="form-label">API Key</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input
                                    :type="showApiKey ? 'text' : 'password'"
                                    class="form-control"
                                    id="oapi_key"
                                    name="oapi_key"
                                    placeholder="••••••••••••••••"
                                    v-model.trim="form.oapi_key"
                                    required
                                >
                                <button type="button"
                                        class="btn btn-outline-secondary"
                                        @click="toggleApiKey"
                                        :title="showApiKey ? 'Hide' : 'Show'">
                                    <i :class="showApiKey ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                </button>
                                <div class="invalid-feedback">API Key is required.</div>
                            </div>
                            <div class="form-text">
                                We only use this to validate your license with Pete’s dashboard.
                            </div>
                        </div>

                        {{-- Domain (read-only) --}}
                        @php
                            $currentDomain = str_replace('/register-your-license','', Request::url());
                        @endphp
                        <div class="mb-4">
                            <label class="form-label">Current Domain</label>
                            <input type="text" class="form-control" value="{{ $currentDomain }}" readonly>
                            <div class="form-text">Detected automatically and sent for validation.</div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-pete" :disabled="submitting">
                                <span v-if="!submitting"><i class="bi bi-check2-circle me-1"></i> Submit</span>
                                <span v-else class="d-inline-flex align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Validating…
                                </span>
                            </button>
                            <a href="/sites" class="btn btn-outline-secondary" :class="{disabled: submitting}">Cancel</a>
                        </div>
                    </form>
                </div>

                <div class="panel-footer">
                    <small class="text-muted">
                        Having trouble? Contact support at
                        <a href="https://ozonegroup.freshdesk.com/support/login">Support</a>.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>[v-cloak]{display:none;}</style>
@endpush

@push('scripts')
{{-- Vue 3 (CDN) --}}
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
(() => {
    const { createApp, onMounted, reactive, ref } = Vue;

    createApp({
        setup() {
            const csrf = ref(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
            const submitting = ref(false);
            const wasValidated = ref(false);
            const showApiKey = ref(false);

            const form = reactive({
                oemail: @json($pete_options->get_meta_value('validation_user_email')),
                oapi_key: @json($pete_options->get_meta_value('validation_api_key')),
                odomain: @json($currentDomain),
            });

            const toggleApiKey = () => { showApiKey.value = !showApiKey.value; };

            const isFormValid = () => {
                // Use built-in constraints + simple checks for SPA experience
                const emailOk = !!form.oemail && /\S+@\S+\.\S+/.test(form.oemail);
                const keyOk = !!form.oapi_key;
                return emailOk && keyOk;
            };

            const submitForm = async () => {
                wasValidated.value = true;
                if (!isFormValid()) return;

                try {
                    submitting.value = true;
                    if (typeof window.activate_loader === 'function') activate_loader();

                    const payload = {
                        oemail: form.oemail,
                        oapi_key: form.oapi_key,
                        odomain: form.odomain,
                    };

                    // Prefer axios if your layout already loads it; otherwise fallback to fetch
                    let res, data;
                    if (window.axios) {
                        res = await axios.post('/validate-pete', payload, {
                            headers: { 'X-CSRF-TOKEN': csrf.value }
                        });
                        data = res.data;
                    } else {
                        res = await fetch('/validate-pete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf.value,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        data = await res.json();
                        if (!res.ok) {
                            const err = new Error(data?.message || 'Validation failed.');
                            err.response = { data };
                            throw err;
                        }
                    }

                    if (typeof window.deactivate_loader === 'function') deactivate_loader();

                    if (data && data.message) {
                        window.toast?.(data.message, 'info');
                    } else {
                        window.toast?.('License validated successfully.', 'success');
                        window.location.href = '/sites';
                    }
                } catch (error) {
                    if (typeof window.deactivate_loader === 'function') deactivate_loader();
                    const message =
                        error?.response?.data?.message ||
                        error?.message ||
                        'Validation failed. Please try again.';
                        console.log(message);
                    window.toast?.(message, 'danger');
                } finally {
                    submitting.value = false;
                }
            };

            onMounted(() => {
                // No-op; kept for future hooks if needed
            });

            return {
                form,
                submitting,
                wasValidated,
                showApiKey,
                toggleApiKey,
                submitForm
            };
        }
    }).mount('#licenseApp');
})();
</script>
@endpush
