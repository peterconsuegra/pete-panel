@extends('layout')

@section('content')
<div class="container-fluid" id="sitesApp" v-cloak>

    {{-- 🔔 Flash alerts (error / info) --}}
    @if(session('error_message'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> {{ session('error_message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('message'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- flash / updater placeholder --}}
    <div id="update_area_info"></div>

    {{-- hero ------------------------------------------------------------- --}}
    <div class="row align-items-center mb-5 g-4">
        <div class="col-md-6 text-center text-md-start">
            <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:200px">
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center">
            <a href="{{ route('sites.create') }}" class="btn btn-pete btn-lg w-100" id="create_wordpress_site">
                <i class="bi bi-plus-lg me-1"></i>Create WordPress Site
            </a>
            <p class="text-muted mb-0 mt-2">Speed, Simplicity, and Full Control—Right Here</p>
        </div>
    </div>

    {{-- Loading overlay + skeleton styles --}}
    <style>
      .table-loading-overlay{
        position:absolute; inset:0;
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        background:linear-gradient(180deg, rgba(255,255,255,.85), rgba(255,255,255,.95));
        z-index: 2; border-radius:.5rem;
      }
      .skel-row{
        height: 18px; border-radius: 6px;
        background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 37%, #e9ecef 63%);
        background-size: 400% 100%;
        animation: skel 1.2s ease-in-out infinite;
      }
      @keyframes skel{ 0%{ background-position: 100% 0 } 100%{ background-position: 0 0 } }
    </style>

    {{-- sites table ------------------------------------------------------ --}}
    <div class="row">
        <div class="col-12">
            <div class="panel position-relative">

                {{-- Loading overlay over the table area --}}
                <div v-if="loadingRows" class="table-loading-overlay text-center">
                  <div class="spinner-border mb-3" role="status" aria-hidden="true"></div>
                  <div class="text-muted">Loading sites…</div>
                </div>

                <div class="panel-heading d-flex justify-content-between align-items-center">
                    <h3 id="my_wordpress_sites" class="mb-0 fs-5">My WordPress Sites</h3>

                    <div class="d-flex align-items-center gap-2">
                        {{-- Per-page selector (server-driven pagination UI) --}}
                        <form method="GET" action="{{ route('sites.index') }}" class="d-inline-block">
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

                        <small id="sitesTotalCount" class="text-muted" v-if="total">@{{ total }} total</small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="sites_table" class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="60">ID</th>
                                <th>Project Name</th>
                                <th>URL</th>
                                <th class="text-center" width="70">SSL</th>
                                <th>Created</th>
                                <th class="text-end" width="630">Actions</th>
                            </tr>
                        </thead>

                        <!-- Loading skeleton rows -->
                      <tbody v-if="loadingRows">
                        <tr v-for="n in 6" :key="'skel-'+n">
                          <td><div class="skel-row" style="width:40px"></div></td>
                          <td><div class="skel-row" style="width:160px"></div></td>
                          <td><div class="skel-row" style="width:300px"></div></td>
                          <td class="text-center"><div class="skel-row" style="width:24px; margin:0 auto"></div></td>
                          <td><div class="skel-row" style="width:140px"></div></td>
                          <td class="text-end"><div class="skel-row" style="width:540px; float:right"></div></td>
                        </tr>
                      </tbody>

                      <!-- Real rows -->
                      <tbody v-else-if="rows.length">
                        <tr
                          v-for="site in rows"
                          :key="site.id"
                          :data-row-id="site.id"
                          :class="{ 'row-flash': flashId === site.id }"
                        >
                          <td class="text-muted">@{{ site.id }}</td>
                          <td class="fw-semibold">@{{ site.name }}</td>
                          <td>
                            <a :href="`http://${site.url}`" target="_blank" rel="noopener">
                              @{{ site.url }}
                            </a>
                          </td>
                          <td class="text-center">
                            <i v-if="site.ssl" class="bi bi-shield-check text-success" title="SSL enabled"></i>
                            <i v-else class="bi bi-shield-x text-danger" title="SSL disabled"></i>
                          </td>

                           <td>
                            <span :title="site.created_at || ''">@{{ formatLocal(site.created_at) }}</span>
                          </td>

                          <td class="text-end" width="580">
                            <div class="btn-group btn-group-sm" role="group">
                              <!-- SSL -->
                              <button
                                class="btn btn-sm btn-outline-success"
                                :disabled="isBusy(site.id)"
                                :aria-busy="isBusy(site.id) ? 'true' : 'false'"
                                @click="generateSSL(site.id)"
                                style="min-width: 145px">
                                <span v-if="isBusy(site.id)" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                <i v-else class="bi bi-lock-fill me-2"></i>
                                <span v-if="isBusy(site.id)">Generating…</span>
                                <span v-else>Generate SSL</span>
                              </button>

                              <!-- Clone -->
                              <button type="button"
                                      :id="`clone_${site.name}`"
                                      class="btn btn-outline-secondary"
                                      :data-site-id="site.id"
                                      :data-site-name="site.name"
                                      @click="openCloneModal($event)"
                                      title="Clone site">
                                <i class="bi bi-files"></i> Clone
                              </button>

                              <!-- Backup -->
                              <button
                                :id="`backup_${site.name}`"
                                class="btn btn-outline-secondary"
                                :data-site-id="site.id"
                                :data-site-name="site.name"
                                @click="openBackupModal($event)"
                                title="Create backup">
                                <i class="bi bi-hdd"></i> Export
                              </button>

                              <select
                                class="form-select form-select-sm ms-2"
                                style="min-width: 170px; width: 170px"
                                :value="site.backup_days"
                                @change="onBackupDaysChange(site, $event)"
                                :disabled="isBusy(site.id)"
                                title="How many days of backups to keep for this site"
                              >
                                <option value="0">Backups disabled</option>
                                <option value="1">1 day backup</option>
                                <option value="2">2 days backup</option>
                                <option value="3">3 days backup</option>
                              </select>

                              <!-- Logs -->
                              <a :id="`logs_${site.name}`"
                                :href="`{{ rtrim(route('sites.logs', 0), '/0') }}/${site.id}`"
                                class="btn btn-info" title="View logs">
                                <i class="bi bi-journal-text"></i> Logs
                              </a>

                              <!-- Delete -->
                              <button
                                :id="`delete_${site.name}`"
                                type="button"
                                class="btn btn-danger"
                                :disabled="deletingId === site.id"
                                @click="confirmDelete({ id: site.id, name: site.name })"
                                title="Delete site">
                                <span v-if="deletingId === site.id" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-trash me-1"></i>
                                Delete
                              </button>
                            </div>
                          </td>
                        </tr>
                      </tbody>

                      <!-- Empty state -->
                      <tbody v-else>
                        <tr>
                          <td colspan="6" class="text-center p-5">
                            <p class="lead mb-0">No sites found — create one to get started.</p>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <div class="panel-footer" v-if="!loadingRows">
                        <nav aria-label="Sites pagination" class="mt-3">
                            {{ $sites->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <teleport to="body">
    {{-- Clone Modal ----------------------------------------------------------- --}}
    @php
        $template = $template ?? ($pete_options->get_meta_value('domain_template') ?? null);
    @endphp

    <div class="modal fade" id="cloneModal" tabindex="-1" aria-labelledby="cloneModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form class="modal-content" @submit.prevent="submitClone">
          <div class="modal-header">
            <h5 class="modal-title" id="cloneModalLabel">Clone Site</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div v-if="errorMessage" id="clone_error_box" class="alert alert-danger py-2 mb-3">
              @{{ errorMessage }}
            </div>

            <input type="hidden" v-model="form.site_to_clone_id">

            <div class="mb-3">
              <label for="clone_domain" class="form-label">Destination URL</label>

              @if($template && $template !== 'none')
                <div class="input-group">
                  <input id="clone_domain"
                        type="text"
                        class="form-control"
                        v-model.trim="form.site_url"
                        :class="{'is-invalid': !!errors.site_url}"
                        placeholder="subdomain">
                  <span class="input-group-text">.{{ $template }}</span>
                  <div class="invalid-feedback" v-if="errors.site_url">@{{ errors.site_url[0] }}</div>
                </div>
                <div class="form-text">Enter only the subdomain; Pete appends the template automatically.</div>
              @else
                <input id="clone_domain"
                      type="text"
                      class="form-control"
                      v-model.trim="form.site_url"
                      :class="{'is-invalid': !!errors.site_url}"
                      placeholder="e.g. example.com">
                <div class="invalid-feedback" v-if="errors.site_url">@{{ errors.site_url[0] }}</div>
              @endif
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="clone_site_button" type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-files me-1"></i>
              Clone Site
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- Backup Modal ---------------------------------------------------------- --}}
    <div class="modal fade" id="backupModal" tabindex="-1" aria-labelledby="backupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" @submit.prevent="submitBackup">
            <div class="modal-header">
                <h5 class="modal-title" id="backupModalLabel">Create Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div v-if="backupErrorMessage" class="alert alert-danger py-2 mb-3" id="backup_error_box">
                @{{ backupErrorMessage }}
                </div>

                <input type="hidden" :value="backup.site_id">

                <div class="mb-3">
                <label for="backup_label" class="form-label">Label</label>
                <input
                    id="backup_label"
                    type="text"
                    class="form-control"
                    v-model.trim="backup.backup_label"
                    :class="{'is-invalid': !!backupErrors.backup_label}"
                    placeholder="e.g. before-clone"
                />
                <div class="invalid-feedback" v-if="backupErrors.backup_label">
                    @{{ backupErrors.backup_label[0] }}
                </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="create_backup_button" type="submit" class="btn btn-primary" :disabled="backupLoading">
                <span v-if="backupLoading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-hdd me-1"></i>
                Create Backup
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
window.PETE_TOAST_DELAY = 30000;
const { createApp, nextTick } = Vue;

createApp({
  data() {
    return {
      // clone state
      form: { site_to_clone_id: '', site_url: '' },
      errors: {},
      errorMessage: '',
      loading: false,
      cloneModal: null,

      // backup state
      backup: { site_id: '', backup_label: '' },
      backupErrors: {},
      backupErrorMessage: '',
      backupLoading: false,
      backupModal: null,

      // table state
      rows: [],
      total: 0,
      loadingRows: true,
      busyIds: new Set(),
      deletingId: null,
      flashId: null,

      // bootstrap payload from server
      _bootstrapRows: @json($sitesPayload ?? []),
      _bootstrapTotal: {{ (int) ($sites->total() ?? 0) }},
    };
  },

  async mounted() {
    await nextTick();
    const cloneEl  = document.getElementById('cloneModal');
    if (cloneEl) this.cloneModal = bootstrap.Modal.getOrCreateInstance(cloneEl);
    const backupEl = document.getElementById('backupModal');
    if (backupEl) this.backupModal = bootstrap.Modal.getOrCreateInstance(backupEl);
    this.loadRows();
  },

  methods: {

    formatLocal(iso) {
      if (!iso) return '—';
      const d = new Date(iso);
      if (isNaN(d.getTime())) return '—';
      return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      }).format(d);
    },

    async loadRows() {
      this.loadingRows = true;
      try {
        await new Promise(r => setTimeout(r, 180));
        this.rows = (this._bootstrapRows || []).map(s => ({
          id: Number(s.id),
          name: String(s.name ?? ''),
          url: String(s.url ?? ''),
          ssl: !!s.ssl,
          backup_days: Number(s.backup_days ?? 0),
          created_at: s.created_at || null, 
        }));
        this.total = Number(this._bootstrapTotal) || this.rows.length || 0;
        this.updateTotalBadge();
      } finally {
        this.loadingRows = false;
      }
    },

    async onBackupDaysChange(site, evt) {
    const newVal = Number(evt?.target?.value ?? site.backup_days);
    if (![0,1,2,3].includes(newVal)) {
      window.toast?.('Invalid selection.', 'warning');
      return;
    }

    const prev = site.backup_days;
    site.backup_days = newVal; // optimistic

    try {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const res = await fetch(`/sites/${site.id}/backup-settings`, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ backup_days: newVal }),
      });

      const raw = await res.text();
      let data = {};
      try { data = JSON.parse(raw); } catch {}

      if (!res.ok || data.error) {
        site.backup_days = prev; // rollback
        window.toast?.(data.message || 'Failed to save backup days.', 'error');
        return;
      }

      window.toast?.(data.message || 'Backup days saved.', 'success');
    } catch {
      site.backup_days = prev; // rollback
      window.toast?.('Network error saving backup days.', 'error');
    }
  },

    updateTotalBadge() {
      const totalEl = document.getElementById('sitesTotalCount');
      if (totalEl) totalEl.textContent = `${this.total} total`;
    },

    isBusy(id) { return this.busyIds.has(Number(id)); },

    /* ---------------- SSL ---------------- */
    async generateSSL(id) {
      if (!id) return;
      const site = this.rows.find(r => r.id === Number(id));
      const siteLabel = site ? site.url : `site #${id}`;
      if (!confirm(`Generate a new SSL certificate for ${siteLabel}?`)) return;

      this.busyIds.add(Number(id));
      const GENERATE_SSL = @json(route('sites.generate.ssl', [], false));
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      const termId = window.toastTerminal(
        `▶ Starting SSL generation for ${siteLabel}\n`,
        { title: `Let's Encrypt • ${siteLabel}`, theme: 'light', delay: 0, autoScroll: true }
      );
      const tAppend = (line='') => window.toastTerminalAppend?.(termId, line);

      try {
        tAppend('→ Validating permissions & environment…');

        const res = await fetch(GENERATE_SSL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf
          },
          body: JSON.stringify({ site_id: id })
        });

        const raw = await res.text();
        let data = {};
        try { data = JSON.parse(raw); } catch {}

        if (raw?.trim()) {
          tAppend('\n— Server output —');
          tAppend(String(data.message ?? raw).trim());
          tAppend('— End output —\n');
        }

        if (!res.ok || data.error) {
          tAppend('✖ SSL generation failed.');
          window.toast?.(data.message || 'SSL generation failed.', 'error');
          const status = document.querySelector(`[data-toast-id="${termId}"] .terminal-status`);
          if (status) { status.classList.add('terminal-status--error'); status.textContent = 'ERROR'; }
          return;
        }

        const idx = this.rows.findIndex(r => r.id === Number(id));
        if (idx !== -1) this.rows[idx].ssl = true;

        tAppend('✔ Certificate issued and linked to virtual host.');
        const status = document.querySelector(`[data-toast-id="${termId}"] .terminal-status`);
        if (status) { status.classList.add('terminal-status--success'); status.textContent = 'SUCCESS'; }

        window.toast?.('SSL generated successfully.', 'success');
      } catch (e) {
        tAppend('\n✖ Network error while generating SSL.');
        const status = document.querySelector(`[data-toast-id="${termId}"] .terminal-status`);
        if (status) { status.classList.add('terminal-status--error'); status.textContent = 'ERROR'; }
        window.toast?.('Network error. Please try again.', 'error');
      } finally {
        this.busyIds.delete(Number(id));
      }
    },

    /* ---------------- CLONE ---------------- */
    openCloneModal(evt) {
      this.resetClone();
      const btn = evt.currentTarget;
      this.form.site_to_clone_id = btn.dataset.siteId || '';
      this.cloneModal?.show();
      nextTick(() => document.getElementById('clone_domain')?.focus());
    },

    resetClone() {
      this.form = { site_to_clone_id: this.form.site_to_clone_id || '', site_url: '' };
      this.errors = {};
      this.errorMessage = '';
      this.loading = false;
    },

    async submitClone() {
      // 1) Basic front-end validation
      if (!this.form.site_url) {
        const el = document.getElementById('clone_domain');
        if (el) this.form.site_url = el.value.trim();
      }
      if (!this.form.site_url) {
        this.errors = { site_url: ['Please enter a destination.'] };
        return;
      }

      const CLONE_URL = @json(route('sites.clone', [], false));
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      this.loading = true;
      this.errorMessage = '';
      this.errors = {};

      const sleep = (ms) => new Promise(r => setTimeout(r, ms));

      // open a terminal toast
      const destLabel = this.form.site_url;
      const termId = window.toastTerminal(
        `Starting clone…`,
        { title: `Clone • ${destLabel}`, delay: 0, theme: 'light', autoScroll: true }
      );
      const tAppend = (line = '') => window.toastTerminalAppend?.(termId, line);

      try {
        // 2) Enqueue the job
        const res = await fetch(CLONE_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify(this.form),
        });

        if (res.redirected) {
          this.errorMessage = 'Your session expired. Please log in again.';
          tAppend('✖ Session expired.');
          window.toast?.(this.errorMessage, 'error');
          return;
        }

        const text = await res.text();
        const data = (() => { try { return JSON.parse(text) } catch { return {} } })();

        if (!res.ok) {
          if (res.status === 422 && data.errors) {
            this.errors = data.errors;
            this.errorMessage = data.message || 'Validation failed.';
          } else if (res.status === 401 || res.status === 403) {
            this.errorMessage = data.message || 'You are not allowed to clone this site.';
          } else {
            this.errorMessage = data.message || 'Clone failed. Please try again.';
          }
          tAppend(`✖ ${this.errorMessage}`);
          window.toast?.(this.errorMessage, 'error');
          return;
        }

        // 3) Success enqueue → begin polling
        const jobId = data.job_id;
        const statusUrl = data.status_url;
        tAppend(`▶ Clone enqueued (job ${jobId})`);

        // close the modal now that the job is running
        this.cloneModal?.hide();

        let lastProgress = null;
        let lastMessage = null;
        let heartbeatCount = 0;

        while (true) {
          await sleep(700); // poll every 0.7s

          const r = await fetch(statusUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With':'XMLHttpRequest' }
          });

          if (!r.ok) {
            heartbeatCount++;
            if (heartbeatCount % 5 === 0) tAppend('…'); // heartbeat ~10s
            continue;
          }

          const st = await r.json().catch(() => ({}));

          const p = typeof st.progress === 'number' ? st.progress : null;
          const m = st.message || '';

          // append only on change
          if (p !== lastProgress) {
            tAppend(`Progress: ${p ?? 0}%`);
            lastProgress = p;
            heartbeatCount = 0;
          }
          if (m && m !== lastMessage) {
            tAppend(m);
            lastMessage = m;
            heartbeatCount = 0;
          }
          if (!m && !p) {
            heartbeatCount++;
            if (heartbeatCount % 5 === 0) tAppend('…');
          }

          // terminal states
          if (st.status === 'succeeded') {
            tAppend('✔ Clone completed.');
            const statusEl = document.querySelector(`[data-toast-id="${termId}"] .terminal-status`);
            if (statusEl) {
              statusEl.classList.add('terminal-status--success');
              statusEl.textContent = 'SUCCESS';
            }
            window.toast?.('Clone completed.', 'success');

            // Build and insert the new row at the top
            const nowIso = new Date().toISOString();
            const newRow = {
              id: Number(st.dest_site_id),
              name: String(st.dest_name || ''),
              url: String(st.dest_url || ''),
              ssl: false,
              backup_days: 0,
              created_at: nowIso,
            };

            this.rows.unshift(newRow);
            this.total = (this.total || 0) + 1;
            this.updateTotalBadge?.();

            // 🔦 Flash + scroll
            this.flashId = newRow.id;
            await nextTick();

            // Remove any previous special id (safety)
            const prev = document.querySelector(`tr[id="${newRow.name}"]`);
            if (prev) prev.removeAttribute('id');

            // 🎯 Assign id based on dest_name directly
            const tr = document.querySelector(`tr[data-row-id="${newRow.id}"]`);
            if (tr) {
              const safeId = (st.dest_name || '')
                .replace(/[^a-z0-9]/gi, ''); // keep only alphanumeric
              tr.id = safeId || `site_${newRow.id}`;
              tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // clear highlight after animation
            setTimeout(() => { this.flashId = null; }, 3800);

            return;
          }

          if (st.status === 'failed') {
            const msg = st.message || 'Clone failed.';
            tAppend(`✖ ${msg}`);
            const statusEl = document.querySelector(`[data-toast-id="${termId}"] .terminal-status`);
            if (statusEl) { statusEl.classList.add('terminal-status--error'); statusEl.textContent = 'ERROR'; }
            window.toast?.(msg, 'error');
            return;
          }
        }
      } catch (e) {
        tAppend('✖ Network error while enqueuing/polling clone.');
        window.toast?.('Network error. Please try again.', 'error');
      } finally {
        this.loading = false;
      }
    },
    /* ---------------- BACKUP ---------------- */
    openBackupModal(evt) {
      this.backup = { site_id: evt.currentTarget.dataset.siteId || '', backup_label: '' };
      this.backupErrors = {};
      this.backupErrorMessage = '';
      this.backupLoading = false;
      this.backupModal?.show();
      nextTick(() => document.getElementById('backup_label')?.focus());
    },

    async submitBackup() {
      if (!this.backup.site_id) return;

      // Reset UI state
      this.backupLoading = true;
      this.backupErrorMessage = '';
      this.backupErrors = {};

      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const site = this.rows.find(r => r.id === Number(this.backup.site_id));
      const siteLabel = site ? site.url : `site #${this.backup.site_id}`;
      const label = this.backup.backup_label || '(no label)';

      // Helper
      const sleep = (ms) => new Promise(r => setTimeout(r, ms));

      // Open a terminal toast (sticky, macOS-light)
      const termId = window.toastTerminal(
        `▶ Starting backup for ${siteLabel}\nLabel: ${label}\n`,
        { title: `Backup • ${siteLabel}`, theme: 'light', delay: 0, autoScroll: true }
      );
      const tAppend = (line = '') => window.toastTerminalAppend?.(termId, line);
      const setStatus = (kind /* success|error|warning */) => {
        const statusEl = document.querySelector(`[data-toast-id="${termId}"] .terminal-status`);
        if (statusEl) {
          statusEl.classList.remove('terminal-status--success','terminal-status--error','terminal-status--warning');
          if (kind) statusEl.classList.add(`terminal-status--${kind}`);
          statusEl.textContent = (kind || '').toUpperCase();
        }
      };

      try {
        // Enqueue
        const res = await fetch('/backups', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify(this.backup),
        });

        const raw = await res.text();
        let data = {};
        try { data = JSON.parse(raw); } catch {}

        if (!res.ok) {
          if (res.status === 422 && data.errors) {
            this.backupErrors = data.errors;
            this.backupErrorMessage = data.message || 'Validation failed.';
          } else if (res.status === 401 || res.status === 403) {
            this.backupErrorMessage = data.message || 'You are not allowed to back up this site.';
          } else {
            this.backupErrorMessage = data.message || 'Backup enqueue failed. Please try again.';
          }
          tAppend(`✖ ${this.backupErrorMessage}`);
          setStatus('error');
          window.toast?.(this.backupErrorMessage, 'error');
          return;
        }

        // Enqueued OK → begin polling
        const jobId = data.job_id;
        const statusUrl = data.status_url;
        tAppend(`▶ Backup enqueued (job ${jobId})`);
        this.backupModal?.hide();

        let lastProgress = null;
        let lastMessage  = null;
        let heartbeat    = 0;

        while (true) {
          await sleep(700);

          const r = await fetch(statusUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (!r.ok) {
            heartbeat++;
            if (heartbeat % 5 === 0) tAppend('…');
            continue;
          }

          const st = await r.json().catch(() => ({}));
          const p  = (typeof st.progress === 'number') ? st.progress : null;
          const m  = st.message || '';

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

          if (st.status === 'succeeded') {
            tAppend('✔ Backup completed.');
            if (st.download) {
              // show a plain URL; terminal allows copy via 📋 button
              tAppend(`Download: ${st.download}`);
            }
            setStatus('success');
            window.toast?.('Backup created.', 'success');

            // ⏳ Wait 2 seconds, then redirect
            setTimeout(() => {
              window.location.href = '/backups';
            }, 2000);

            return;
          }

          if (st.status === 'failed') {
            const msg = st.message || 'Backup failed.';
            tAppend(`✖ ${msg}`);
            setStatus('error');
            window.toast?.(msg, 'error');
            return;
          }
        }
      } catch (e) {
        this.backupErrorMessage = 'Network error. Please try again.';
        tAppend('✖ Network error while enqueuing/polling backup.');
        setStatus('error');
        window.toast?.(this.backupErrorMessage, 'error');
      } finally {
        this.backupLoading = false;
      }
    },
    /* ---------------- DELETE ---------------- */
    confirmDelete(site) {
      if (!site?.id) return;
      if (!confirm(`Delete "${site.name}"? This cannot be undone.`)) return;
      this.deleteSite(site.id);
    },

    async deleteSite(id) {
      this.deletingId = id;
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      try {
        const res = await fetch(`/sites/${id}`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
          },
          body: new URLSearchParams({ _method: 'DELETE' }),
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        // remove from table
        this.rows = this.rows.filter(r => r.id !== id);
        this.total = Math.max(0, this.total - 1);
        this.updateTotalBadge();
        window.toast?.('Site deleted.', 'success');
      } catch (e) {
        window.toast?.('Delete failed. Please try again.', 'error');
      } finally {
        this.deletingId = null;
      }
    },

  },
}).mount('#sitesApp');
</script>



@if($currentUser->isAdmin())

    <script>
    /**
     * Lightweight version check (no jQuery).
     * - Compares local Pete version (from Blade) with remote (/admin/version/check).
     * - If remote is newer, shows an update banner pointing to /admin/update.
     */
    (function () {
    // Inject local version from PHP (safe if null)
    const LOCAL_VERSION = {!! json_encode(isset($pete_options) ? (string) $pete_options->get_meta_value('version') : null) !!};
    if (!LOCAL_VERSION) return; // nothing to compare

    const ENDPOINT = '/admin/version/check';   // GET; should return plain text or JSON with version
    const TIMEOUT_MS = 6000;

    // Compare "1.2.3" style versions
    function isOutdated(localV, remoteV) {
        const A = String(localV).split('.').map(n => parseInt(n, 10) || 0);
        const B = String(remoteV).split('.').map(n => parseInt(n, 10) || 0);
        const len = Math.max(A.length, B.length);
        for (let i = 0; i < len; i++) {
        const ai = A[i] || 0, bi = B[i] || 0;
        if (ai < bi) return true;
        if (ai > bi) return false;
        }
        return false;
    }

    function renderBanner(remote) {
        if (!remote) return;
        if (!isOutdated(LOCAL_VERSION, remote)) return;

        const host = document.getElementById('update_area_info');
        if (!host) return;

        host.innerHTML = `
        <div class="alert alert-warning mt-3">
            <strong>WordPress Pete ${remote}</strong> is available!
            <a class="upgrade_link" href="/admin/update">Please update now</a>
        </div>`;
    }

    function fetchWithTimeout(url, opts = {}, ms = TIMEOUT_MS) {
        const ctrl = new AbortController();
        const t = setTimeout(() => ctrl.abort(), ms);
        return fetch(url, { ...opts, signal: ctrl.signal })
        .finally(() => clearTimeout(t));
    }

    function normalizeVersionPayload(text) {
        // Accept either `"1.50.0"` (json) or `1.50.0` (raw text)
        try {
        const parsed = JSON.parse(text);
        if (typeof parsed === 'string') return parsed;
        } catch (_) {}
        return String(text || '').trim();
    }

    function checkPeteVersion() {
        fetchWithTimeout(ENDPOINT, { headers: { 'Accept': 'application/json, text/plain, */*' } })
        .then(async (res) => {
            // tolerate non-200 silently
            if (!res.ok) return null;
            const raw = await res.text();
            return normalizeVersionPayload(raw);
        })
        .then(renderBanner)
        .catch(() => { /* ignore network errors; fail quietly */ });
    }

    // Run after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkPeteVersion, { once: true });
    } else {
        checkPeteVersion();
    }
    })();
    </script>

@endif

@endpush
