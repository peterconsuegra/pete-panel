@extends('layout')

@section('content')
<div class="container-fluid" id="serverStatusPanel" v-cloak>

    {{-- Header / intro (driven by backend-confirmed state) ------------------- --}}
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning mt-2" v-if="serviceStatus === 'on'">
                <strong>Heads up:</strong>
                Enable this only when you need it, then turn it off again. It’s not recommended for continuous production use.
            </div>
        </div>
    </div>

    {{-- Panel ---------------------------------------------------------------- --}}
    <div class="row">
        <div class="col-12">
            <div class="panel">
                <div class="panel-heading d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fs-5">
                        <i class="bi bi-speedometer2 me-2"></i> Apache Module: <code>mod_status</code>
                    </h3>
                    <span class="badge" :class="serviceStatus === 'on' ? 'text-bg-warning' : 'text-bg-secondary'">
                        <span v-text="serviceStatus === 'on' ? 'Enabled' : 'Disabled'"></span>
                    </span>
                </div>

                <div class="p-3">
                    <p class="text-muted mb-4">
                        The Status module shows current server statistics in a simple HTML page so you can quickly gauge performance.
                    </p>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <label for="server_status" class="form-label">Status</label>
                            <select class="form-select" name="server_status" id="server_status"
                                    v-model="status" :disabled="isSubmitting">
                                <option value="on">on</option>
                                <option value="off">off</option>
                            </select>
                        </div>

                        <div class="col-auto">
                            <button class="btn btn-primary" id="server_status_submit" type="button"
                                    @click="changeStatus" :disabled="isSubmitting">
                                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <span v-text="isSubmitting ? 'Updating...' : 'Update'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="panel-footer" v-if="serviceStatus === 'on'">
                    <div class="small text-muted mb-2">Refreshing every 5 seconds</div>
                    <iframe height="1200" width="100%" class="border-0 rounded" src="/server-status?refresh=5"></iframe>
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
        // Dropdown (user intent) vs backend-confirmed state
        status: @json($serverStatus),         // 'on' | 'off' (bound to <select>)
        serviceStatus: @json($serverStatus),  // 'on' | 'off' (drives UI: banner, badge, iframe)
        isSubmitting: false,
        routes: {
          toggle: @json(route('admin.server.toggle')),
        },
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      };
    },

    methods: {
      async changeStatus() {
        if (this.isSubmitting) return;
        const next = this.status;

        if (!confirm(`Are you sure you want to set mod_status to: ${next}?`)) {
          // Revert the select to the actual confirmed state on cancel
          this.status = this.serviceStatus;
          return;
        }

        this.isSubmitting = true;
        toast('Updating server status...', 'warning');

        try {
          const res = await fetch(this.routes.toggle, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf,
            },
            body: JSON.stringify({ server_status_sw: next }),
          });

          if (!res.ok) throw new Error(`Request failed: ${res.status}`);

          // Success: reflect the confirmed state in the UI
          this.serviceStatus = next;
          toast('Server status updated.', 'success');
        } catch (err) {
          console.error(err);
          toast('Failed to update server status. Please try again.', 'error');
          // Roll back the dropdown to the last confirmed state
          this.status = this.serviceStatus;
        } finally {
          this.isSubmitting = false;
        }
      },
    },
  }).mount('#serverStatusPanel');
})();
</script>
@endpush
