@extends('layout')

@section('content')
<div class="container-fluid" id="securityPanel" v-cloak>

    {{-- banner like sites/index (driven by backend-confirmed state) --}}
    <div class="alert alert-warning mt-3" v-if="serviceStatus === 'off'">
        <strong>Warning:</strong> Keep ModSecurity enabled in production for best protection.
    </div>

    {{-- WAF panel ----------------------------------------------------------- --}}
    <div class="panel my-4">
        <div class="panel-heading d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fs-5">Web Application Firewall (ModSecurity)</h3>

            
            <span class="badge"
                  :class="serviceStatus === 'on' ? 'text-bg-success' : 'text-bg-secondary'">
                <span v-text="serviceStatus.toUpperCase()"></span>
            </span>
        </div>

        {{-- 🔒 Inline access denied message (shown after a 403) --}}
        <div class="alert alert-danger m-3" role="alert" v-if="accessDeniedMsg">
            <i class="bi bi-shield-exclamation me-2"></i>
            <span v-text="accessDeniedMsg"></span>
        </div>

        <div class="p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-12 col-lg-12">
                  <p><b>WordPress hardening:</b> We recommend installing <a href="https://www.wordfence.com/">Wordfence Plugin</a> on your WordPress sites. It adds an extra layer of protection, and Pete Panel uses the Wordfence admin session variable to detect when an administrator is logged in and keep ModSecurity rules relaxed for that user, reducing false positives while they work.</p>

                    <label for="security_status" class="form-label">Protection status</label>
                    <select id="security_status" name="security_status" class="form-select"
                            v-model="status" :disabled="isSubmitting || controlsLocked">
                        <option value="on">on</option>
                        <option value="off">off</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button id="security_submit" type="button" class="btn btn-primary"
                            @click="changeStatus" :disabled="isSubmitting || controlsLocked">
                        <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <i v-else class="bi bi-shield-check me-1"></i>
                        <span v-text="isSubmitting ? 'Updating...' : 'Update'"></span>
                    </button>
                </div>
            </div>

            <hr class="my-4">

            <h4 class="h6 text-uppercase text-muted mb-3">Pete Security Tips</h4>
            <ul class="mb-0">
                <li><strong>Enable in production:</strong> keep this module on for live sites.</li>
                <li><strong>Quick test:</strong> hit a URL like <code>?abc=../../</code> to confirm rules are firing.</li>
                <li><strong>Inspect details:</strong> open any event to see which rules triggered.</li>
                <li><strong>Whitelist as needed:</strong> Add your custom exceptions to apache/modsecurity/whitelist.conf in your Pete Panel Docker project. These rules are copied into /etc/modsecurity/rules/whitelist.conf inside the container to help avoid false positives. After updating the file, run docker compose build --no-cache apache to rebuild the container.</li>
            </ul>
        </div>
    </div>

    {{-- Report panel (driven by backend-confirmed state) -------------------- --}}
    <div class="panel" v-if="serviceStatus === 'on'">
        <div class="panel-heading">
            <h3 class="mb-0 fs-6">Recent Security Events</h3>
        </div>
        <div class="p-0">
            <iframe
                src="{{ route('base.security.report') }}"
                class="w-100 border-0"
                style="min-height: 1200px"
                title="Security Report"
            ></iframe>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(() => {
  const { createApp } = Vue;

  createApp({
    data() {
      return {
        // Dropdown (user intent) vs backend-confirmed actual state
        status: @json($securityStatus),        // 'on' | 'off' - bound to <select>
        serviceStatus: @json($securityStatus), // 'on' | 'off' - drives UI
        isSubmitting: false,

        // 🔒 UI lock & inline message after 403
        controlsLocked: false,
        accessDeniedMsg: '',

        routes: {
          toggle: @json(route('admin.security.toggle')),
        },
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      };
    },

    methods: {
      async changeStatus() {
        if (this.isSubmitting || this.controlsLocked) return;
        const next = this.status;

        if (!confirm(`Are you sure you want to set security to: ${next}?`)) {
          // revert dropdown to actual state if cancelled
          this.status = this.serviceStatus;
          return;
        }

        this.isSubmitting = true;
        toast('Updating security status...', 'warning');

        try {
          const res = await fetch(this.routes.toggle, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf,
            },
            body: JSON.stringify({ security_status_sw: next }),
          });

          // Handle explicit access denied from AdminController middleware
          if (res.status === 403) {
            let payload = {};
            try { payload = await res.json(); } catch (_) {}
            const msg = payload?.message
              || 'You are not authorized to perform this action.';
            this.accessDeniedMsg = msg;
            this.controlsLocked = true;           // lock the controls after denial
            this.status = this.serviceStatus;     // rollback to last confirmed state
            toast(msg, 'error');
            return; // stop here
          }

          // CSRF / expired session edge (Laravel usually 419)
          if (res.status === 419) {
            this.status = this.serviceStatus;
            const msg = 'Your session has expired. Please refresh and try again.';
            toast(msg, 'error');
            this.accessDeniedMsg = msg;
            this.controlsLocked = true;
            return;
          }

          if (!res.ok) {
            // Other failures → surface a generic error and rollback
            this.status = this.serviceStatus;
            toast(`Failed to update security status. (HTTP ${res.status})`, 'error');
            return;
          }

          // Confirmed: reflect in UI without reload
          this.serviceStatus = next;
          toast('Security status updated.', 'success');

        } catch (err) {
          console.error(err);
          this.status = this.serviceStatus; // rollback
          toast('Network error. Please check your connection and try again.', 'error');
        } finally {
          this.isSubmitting = false;
        }
      },
    },
  }).mount('#securityPanel');
})();
</script>
@endpush
