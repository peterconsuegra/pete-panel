@extends('layout')

@section('content')
@php
    $template = $template ?? ($pete_options->get_meta_value('domain_template') ?? null);
@endphp

<div class="container-fluid" id="backupsApp" v-cloak>

    {{-- hero ------------------------------------------------------------- --}}
    <div class="row align-items-center mb-5 g-4">
        <div class="col-md-6 text-center text-md-start">
            <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:200px">
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center">
            <h2 class="mb-1">WordPress Backups</h2>
            <p class="text-muted mb-0">
                Create, download, and restore project snapshots in seconds.
            </p>
        </div>
    </div>

    {{-- flash messages (version updater placeholder kept by layout) ------ --}}
    <div id="update_area_info"></div>

    {{-- loading styles (overlay + skeleton) ------------------------------- --}}
    <style>
      .table-loading-overlay{
        position:absolute; inset:0;
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        background:linear-gradient(180deg, rgba(255,255,255,.85), rgba(255,255,255,.95));
        z-index: 2;
        border-radius:.5rem;
      }
      .skel-row{
        height: 18px;
        border-radius: 6px;
        background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 37%, #e9ecef 63%);
        background-size: 400% 100%;
        animation: skel 1.2s ease-in-out infinite;
      }
      @keyframes skel{
        0%{ background-position: 100% 0 }
        100%{ background-position: 0 0 }
      }
    </style>

    {{-- backups table ---------------------------------------------------- --}}
    <div class="row">
        <div class="col-12">
            <div class="panel position-relative">
              <div id="my_saved_backups"></div>
                <div class="panel-heading d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-0 fs-5">Saved Backups</h3>
                        <small class="text-muted">Exported in Pete format—portable and production-ready.</small>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        {{-- Per-page selector (same options as controller) --}}
                        <form method="GET" action="{{ route('backups.index') }}" class="d-inline-block">
                            <label for="per_page" class="form-label me-2 mb-0 small text-muted">Rows per page:</label>
                            <select name="per_page" id="per_page" class="form-select form-select-sm d-inline-block w-auto"
                                    onchange="this.form.submit()">
                                @foreach([5,10,20,50] as $size)
                                    <option value="{{ $size }}" {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <small id="backupsTotalCount" class="text-muted" v-if="total">@{{ total }} total</small>
                    </div>
                </div>

                {{-- Loading overlay over the table area --}}
                <div v-if="loadingRows" class="table-loading-overlay text-center">
                  <div class="spinner-border mb-3" role="status" aria-hidden="true"></div>
                  <div class="text-muted">Loading backups…</div>
                </div>

                <div class="table-responsive">
                    @if($backups->count())
                        <table class="table table-hover table-striped align-middle mb-0">
                          <thead class="table-light">
                            <tr>
                              <th style="width:70px">ID</th>
                              <th>Label</th>
                              <th>URL</th>
                              <th>File</th>
                              <th>Created</th>
                              <th class="text-end" width="400">Actions</th>
                            </tr>
                          </thead>

                          {{-- Skeleton rows while loading --}}
                          <tbody v-if="loadingRows">
                            <tr v-for="n in 6" :key="'skel-'+n">
                              <td><div class="skel-row" style="width:40%"></div></td> <!-- ID -->
                              <td><div class="skel-row" style="width:40%"></div></td>
                              <td><div class="skel-row" style="width:70%"></div></td>
                              <td><div class="skel-row" style="width:85%"></div></td>
                              <td><div class="skel-row" style="width:60%"></div></td>
                              <td class="text-end">
                                <div class="d-inline-block" style="width:360px; max-width:100%">
                                  <div class="skel-row" style="width:100%"></div>
                                </div>
                              </td>
                            </tr>
                          </tbody>

                          {{-- Real rows after load --}}
                          <tbody v-else-if="rows.length">
                            <tr v-for="b in rows" :key="b.id">
                              <td class="text-muted">@{{ b.id }}</td>
                              <td class="fw-semibold">@{{ b.label }}</td>

                              <td>
                                <a :href="`http://${b.url}`" target="_blank" rel="noopener">
                                  @{{ b.url }}
                                </a>
                              </td>

                              <td class="text-muted">
                                <code class="d-inline-block text-truncate" style="max-width:420px;">
                                  @{{ b.file_path }}
                                </code>
                              </td>

                              <td>
                                <span :title="b.created_at || ''">@{{ formatLocal(b.created_at) }}</span>
                              </td>

                              <td class="text-end" width="400">
                                <div class="btn-group btn-group-sm" role="group">
                                  <!-- Restore -->
                                  <button
                                    :id="`restore_${b.label}`"
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    :data-backup-id="b.id"
                                    @click="openRestoreModal($event)"
                                    title="Restore this backup">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                  </button>

                                  <!-- Download -->
                                  <a :href="`/backups/${b.id}/download`" class="btn btn-outline-secondary" title="Download backup">
                                    <i class="bi bi-download"></i> Download
                                  </a>

                                  <!-- Delete (reactive) -->
                                  <button
                                    :id="`delete_${b.label}`"
                                    type="button"
                                    class="btn btn-danger"
                                    :disabled="deletingId === b.id"
                                    @click="confirmDeleteBackup({ id: b.id, label: b.label })"
                                    title="Delete backup">
                                    <span v-if="deletingId === b.id" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="bi bi-trash me-1"></i>
                                    Delete
                                  </button>
                                </div>
                              </td>
                            </tr>
                          </tbody>

                          {{-- Empty state --}}
                          <tbody v-else>
                            <tr>
                              <td colspan="5" class="text-center p-5">
                                <p class="lead mb-0">No backups found — create one from the “My WordPress Sites” page.</p>
                              </td>
                            </tr>
                          </tbody>
                        </table>


                        <div class="panel-footer" v-if="total > 0">
                            <nav aria-label="Backups pagination" class="mt-3">
                                {{ $backups->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </nav>
                        </div>

                    @else
                        <div class="p-5 text-center">
                            <p class="lead mb-0">No backups found — create one from the “My WordPress Sites” page.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Restore Modal -------------------------------------------------------- --}}
    <teleport to="body">
    <div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
        <form class="modal-content" @submit.prevent="submitRestore">
            <div class="modal-header">
            <h5 class="modal-title" id="restoreModalLabel">Restore Backup</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
            <!-- hidden backup id -->
            <input type="hidden" v-model="restore.backup_id">

            <div v-if="errorMessage" id="restore_error_box" class="alert alert-danger py-2 mb-3">
                @{{ errorMessage }}
            </div>

            <div class="mb-3">
                <label for="restore_domain" class="form-label">Destination URL</label>

                @if($template && $template !== 'none')
                <div class="input-group">
                    <input id="restore_domain"
                        type="text"
                        class="form-control"
                        v-model.trim="restore.backup_domain"
                        :class="{'is-invalid': !!errors.backup_domain}"
                        placeholder="subdomain"
                        required>
                    <span class="input-group-text">.{{ $template }}</span>
                    <div class="invalid-feedback" v-if="errors.backup_domain">@{{ errors.backup_domain[0] }}</div>
                </div>
                <div class="form-text">Enter only the subdomain; Pete appends the template automatically.</div>
                @else
                <input id="restore_domain"
                        type="text"
                        class="form-control"
                        v-model.trim="restore.backup_domain"
                        :class="{'is-invalid': !!errors.backup_domain}"
                        placeholder="e.g. example.com"
                        required>
                <div class="invalid-feedback" v-if="errors.backup_domain">@{{ errors.backup_domain[0] }}</div>
                @endif
            </div>
            </div>

            <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="restore_backup_button" type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-arrow-counterclockwise me-1"></i>
                Restore
            </button>
            </div>
        </form>
        </div>
    </div>
    </teleport>

</div>

@endsection

@push('scripts')
<script>
const { createApp, nextTick } = Vue;

createApp({
  data() {
    return {
      // table data (start empty to let the loader show)
      rows: [],
      total: 0,
      loadingRows: true,
      deletingId: null,

      // restore modal state
      restore: { backup_id: '', backup_domain: '' },
      errors: {},
      errorMessage: '',
      loading: false,
      restoreModal: null,

      // bootstrap payload from server (used to paint after "loading")
      _bootstrapRows: @json($backupsPayload ?? []),
      _bootstrapTotal: {{ (int) $backups->total() }},
    };
  },

  async mounted() {
    await nextTick();
    const el = document.getElementById('restoreModal');
    if (el) this.restoreModal = bootstrap.Modal.getOrCreateInstance(el);

    // Simulate async load (shows skeleton & overlay, then paints rows)
    this.loadRows();
  },

  methods: {

    formatLocal(iso) {
    if (!iso) return '—';
    // Robust parse; fall back gracefully if Date fails
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '—';
    // Example: Oct 30, 2025, 06:12
    return new Intl.DateTimeFormat(undefined, {
      year: 'numeric', month: 'short', day: '2-digit',
      hour: '2-digit', minute: '2-digit',
    }).format(d);
  },

    async loadRows() {
      this.loadingRows = true;
      try {
        // If you later add a JSON endpoint, replace this with a real fetch.
        // For now, wait a tick so the loader/skeleton visibly renders.
        await new Promise(r => setTimeout(r, 180)); // subtle delay for UX

        this.rows  = (this._bootstrapRows || []).map(b => ({
          id: Number(b.id),
          label: String(b.label ?? ''),
          url: String(b.url ?? ''),
          file_path: String(b.file_path ?? ''),
          download: String(b.download ?? ''),
          created_at: b.created_at || null    
        }));
        this.total = Number(this._bootstrapTotal) || this.rows.length || 0;

        // Update the “N total” badge if present
        const totalEl = document.getElementById('backupsTotalCount');
        if (totalEl) totalEl.textContent = `${this.total} total`;
      } finally {
        this.loadingRows = false;
      }
    },

    // If you add an endpoint later, have this call it and set loadingRows=true while it refreshes.
    async refreshRows() {
      this.loadingRows = true;
      try {
        // Best-effort refresh with current bootstrap data for now.
        this.rows  = (this._bootstrapRows || []).map(b => ({
          id: Number(b.id),
          label: String(b.label ?? ''),
          url: String(b.url ?? ''),
          file_path: String(b.file_path ?? ''),
          download: String(b.download ?? ''),
          created_at: b.created_at || null   
        }));
        this.total = this.rows.length;

        const totalEl = document.getElementById('backupsTotalCount');
        if (totalEl) totalEl.textContent = `${this.total} total`;
      } finally {
        this.loadingRows = false;
      }
    },

    openRestoreModal(evt) {
      this.restore = { backup_id: '', backup_domain: '' };
      this.errors = {};
      this.errorMessage = '';
      this.loading = false;

      const btn = evt?.currentTarget;
      this.restore.backup_id = btn?.dataset?.backupId || '';

      this.restoreModal?.show();
      nextTick(() => document.getElementById('restore_domain')?.focus());
    },

    // Fully reactive delete
    async confirmDeleteBackup({ id, label }) {
      if (!confirm(`Delete backup "${label}"? This action cannot be undone.`)) return;

      this.deletingId = id;
      try {
        const res = await fetch(`/backups/${id}`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new URLSearchParams({ _method: 'DELETE' }),
        });

        if (!res.ok) throw new Error('Delete failed');

        // Remove from reactive array & sync totals
        this.rows = this.rows.filter(b => b.id !== id);
        this.total = Math.max(0, this.total - 1);

        // Keep the little “N total” badge current
        const totalEl = document.getElementById('backupsTotalCount');
        if (totalEl) totalEl.textContent = `${this.total} total`;

        window.toast?.('Backup deleted.', 'success');
      } catch (e) {
        window.toast?.('Delete failed. Please try again.', 'error');
      } finally {
        this.deletingId = null;
      }
    },

    // ⬇️ Updated submitRestore with terminal toast + live polling (uses run_import.php)
    async submitRestore() {
      // Reset UI state
      this.errors = {};
      this.errorMessage = '';

      if (!this.restore.backup_id) {
        this.errorMessage = 'Missing backup id.';
        window.toast?.(this.errorMessage, 'error');
        return;
      }
      if (!this.restore.backup_domain) {
        this.errors = { backup_domain: ['Please enter a destination.'] };
        return;
      }

      // Sanitize: accept user-typed "http(s)://" but send host-like to server
      this.restore.backup_domain = this.restore.backup_domain.replace(/^https?:\/\//i, '');

      // Endpoints & helpers
      const RESTORE_URL = @json(route('backups.restore', [], false));
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const sleep = (ms) => new Promise(r => setTimeout(r, ms));
      const safeParse = (t) => { try { return t ? JSON.parse(t) : {}; } catch { return {}; } };

      // Open terminal toast
      const destLabel = this.restore.backup_domain;
      const termId = window.toastTerminal(
        `▶ Starting restore to ${destLabel}\n`,
        { title: `Restore • ${destLabel}`, theme: 'light', delay: 0, autoScroll: true }
      );
      const tAppend = (line='') => window.toastTerminalAppend?.(termId, line);
      const setStatus = (kind /* success|error|warning|'' */) => {
        const statusEl = document.querySelector(`[data-toast-id="${termId}"] .terminal-status`);
        if (!statusEl) return;
        statusEl.classList.remove('terminal-status--success','terminal-status--error','terminal-status--warning');
        if (kind) statusEl.classList.add(`terminal-status--${kind}`);
        statusEl.textContent = (kind || '').toUpperCase();
      };

      this.loading = true;

      try {
        // Enqueue async restore (controller will launch bootstrap/run_import.php)
        const res = await fetch(RESTORE_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf
          },
          body: JSON.stringify({
            backup_id: this.restore.backup_id,
            backup_domain: this.restore.backup_domain
          })
        });

        const raw = await res.text();
        const data = safeParse(raw);

        // Handle enqueue errors
        if (!res.ok) {
          if (res.status === 419) { this.errorMessage = 'Page expired. Refresh and try again.'; }
          else if (res.status === 422 && data.errors) { this.errors = data.errors; this.errorMessage = data.message || 'Validation failed.'; }
          else if (res.status === 401 || res.status === 403) { this.errorMessage = data.message || 'You are not allowed to restore this backup.'; }
          else { this.errorMessage = data.message || 'Restore enqueue failed. Please try again.'; }

          tAppend(`✖ ${this.errorMessage}`);
          setStatus('error');
          window.toast?.(this.errorMessage, 'error');
          return;
        }

        // Enqueued OK → begin polling
        const jobId = data.job_id;
        const statusUrl = data.status_url;
        tAppend(`▶ Restore enqueued (job ${jobId})`);
        this.restoreModal?.hide();

        let lastProgress = null;
        let lastMessage  = null;
        let heartbeat    = 0;

        while (true) {
          await sleep(700);

          const r = await fetch(statusUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With':'XMLHttpRequest' }
          });

          if (!r.ok) {
            heartbeat++;
            if (heartbeat % 5 === 0) tAppend('…');  // keep-alive dot each ~3.5s
            continue;
          }

          const st = await r.json().catch(() => ({}));
          const p  = (typeof st.progress === 'number') ? st.progress : null;
          const m  = (st.message || '').trim();

          // Append only on change
          if (p !== lastProgress) {
            tAppend(`Progress: ${p ?? 0}%`);
            lastProgress = p;
            heartbeat = 0;
          }
          if (m && m !== lastMessage) {
            tAppend(m);
            lastMessage = m;
            heartbeat = 0;
          }
          if (!m && p == null) {
            heartbeat++;
            if (heartbeat % 5 === 0) tAppend('…');
          }

          // Terminal states from run_import.php
          if (st.status === 'succeeded') {
            tAppend('✔ Restore completed.');
            if (st.url) tAppend(`URL: ${st.url}`);
            if (st.site_id) tAppend(`Site ID: ${st.site_id}`);

            setStatus('success');
            window.toast?.('Restore completed.', 'success');

            // Optional: jump straight to logs if site_id present
            if (st.site_id) {
              window.location.href = `/sites/logs/${encodeURIComponent(st.site_id)}`;
            } else {
              // fallback: go back to sites list
              window.location.href = '/sites?success=true';
            }
            return;
          }

          if (st.status === 'failed') {
            const msg = st.message || 'Restore failed.';
            tAppend(`✖ ${msg}`);
            setStatus('error');
            window.toast?.(msg, 'error');
            return;
          }
        }
      } catch (e) {
        this.errorMessage = 'Network error. Please try again.';
        tAppend('✖ Network error while enqueuing/polling restore.');
        setStatus('error');
        window.toast?.(this.errorMessage, 'error');
      } finally {
        this.loading = false;
      }
    }
  }
}).mount('#backupsApp');
</script>
@endpush
