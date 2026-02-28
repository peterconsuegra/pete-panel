@extends('layout')

@section('content')
<div class="container-fluid" id="phpmyadminPanel" v-cloak>

    {{-- Status / settings panel ------------------------------------------------ --}}
    <div class="row g-4">
        <div class="col-12">
            <div class="panel">
                <div class="panel-heading d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fs-5">
                        <i class="bi bi-database-gear me-2"></i> phpMyAdmin
                    </h3>
                    <span class="badge {{ $phpmyadminStatus === 'on' ? 'text-bg-warning' : 'text-bg-secondary' }}">
                        {{ $phpmyadminStatus === 'on' ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>

                <div class="p-3">

                    @if($phpmyadminStatus === 'on')
                        <div class="alert alert-warning d-flex align-items-start" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                <strong>Security tip:</strong> enable only when needed and disable immediately after use. It isn’t recommended for continuous production use.
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="phpmyadmin_status" class="form-label">phpMyAdmin status</label>
                            <select class="form-select" id="phpmyadmin_status" name="phpmyadmin_status"
                                    v-model="status" :disabled="isSubmitting">
                                <option value="on">on</option>
                                <option value="off">off</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="phpmyadmin_status_submit" class="btn btn-primary w-100"
                                @click="changeStatus" :disabled="isSubmitting">
                                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span v-text="isSubmitting ? 'Updating...' : 'Update'"></span>
                            </button>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <!-- Use serviceStatus (backend-confirmed) instead of status -->
                            <a v-if="serviceStatus==='on'" target="_blank" href="/phpmyadmin" class="btn btn-outline-secondary">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open phpMyAdmin
                            </a>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="dbUser" value="root" readonly>
                                <label for="dbUser">User</label>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="dbPass" value="{{ $rootPass }}" readonly>
                                <label for="dbPass">Password</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary" @click="copy('dbUser')" :disabled="isSubmitting">
                                Copy user
                            </button>
                            <button type="button" class="btn btn-outline-secondary" @click="copy('dbPass')" :disabled="isSubmitting">
                                Copy password
                            </button>
                        </div>
                    </div>

                </div>

                <div class="panel-footer small text-muted">
                    For security, disable phpMyAdmin when you’re done.
                </div>
            </div>
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
        status: @json($phpmyadminStatus),         // bound to dropdown
        serviceStatus: @json($phpmyadminStatus),  // backend-confirmed state
        isSubmitting: false,
        routes: {
          toggle: @json(route('admin.phpmyadmin.toggle')),
        },
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      };
    },

    methods: {
      async changeStatus() {
        if (this.isSubmitting) return;
        const next = this.status;

        if (!confirm(`Are you sure you want to set phpMyAdmin to: ${next}?`)) {
          // revert dropdown to real state if cancelled
          this.status = this.serviceStatus;
          return;
        }

        this.isSubmitting = true;
        toast('Updating phpMyAdmin status...', 'warning');

        try {
          const res = await fetch(this.routes.toggle, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf,
            },
            body: JSON.stringify({ phpmyadmin_status_sw: next }),
          });

          if (!res.ok) throw new Error(`Request failed: ${res.status}`);

          // update confirmed state
          this.serviceStatus = next;
          toast('phpMyAdmin status updated.', 'success');
        } catch (err) {
          console.error(err);
          toast('Failed to update phpMyAdmin status. Please try again.', 'error');
          // rollback dropdown to last confirmed state
          this.status = this.serviceStatus;
        } finally {
          this.isSubmitting = false;
        }
      },

      async copy(inputId) {
        const el = document.getElementById(inputId);
        const val = el ? el.value : '';
        try {
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(val);
          } else {
            const ta = document.createElement('textarea');
            ta.value = val;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
          }
          toast('Copied to clipboard', 'success');
        } catch (e) {
          console.error(e);
          toast('Could not copy value', 'warning');
        }
      },
    },
  }).mount('#phpmyadminPanel');
})();
</script>
@endpush
