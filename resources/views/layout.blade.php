<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Pete Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="googlebot" content="noindex, nofollow, noarchive">

    {{-- CSRF for Ajax --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicons (keep your existing paths) --}}
    <link rel="icon" type="image/png" href="/favicon/favicon.ico">
    <link rel="manifest" href="/favicon/manifest.json">

    {{-- Google Font (optional, matches your old layout) --}}
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Your legacy CSS (safe to keep) --}}
    <link href="/assets/css/animate.min.css" rel="stylesheet"/>
    <link href="/assets/css/custom.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/assets/css/jquery.simplebanner.css"/>

    <style>
  /* === Terminal Toast (macOS Light) ===================================== */
  .terminal-toast-stack{
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    display: flex;
    flex-direction: column-reverse; /* newest on top */
    gap: .75rem;
    max-width: min(640px, 90vw);
    z-index: 1090;
  }

  .terminal-toast{
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(0,0,0,.15);
    background: #ffffff;                         /* light canvas */
    border: 1px solid rgba(0,0,0,.08);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
    color: #1c1c1c;                              /* body text */
  }

  .terminal-toast__titlebar{
    display: flex; align-items: center; justify-content: space-between;
    padding: .5rem .75rem;
    background: linear-gradient(180deg, #f5f5f7, #ededf0); /* macOS light chrome */
    border-bottom: 1px solid rgba(0,0,0,.06);
  }

  .terminal-toast.terminal--light .terminal-title{ color:#4a4a4a; }
  .terminal-title{
    font-size: .8rem;
    user-select: none;
    margin-left: .5rem;
  }

  .terminal-toast__dots{ display:inline-flex; gap:.4rem; align-items:center; }
  .terminal-dot{ width:.75rem; height:.75rem; border-radius:50%; box-shadow: inset 0 0 0 1px rgba(0,0,0,.15); }
  .terminal-dot--close { background:#ff5f56; }
  .terminal-dot--min   { background:#ffbd2e; }
  .terminal-dot--max   { background:#28c840; }

  .terminal-actions{ display:inline-flex; gap:.25rem; }
  .terminal-actions .btn-icon{
    appearance: none; border: 0; background: transparent; color: #666;
    width: 30px; height: 28px; border-radius: 6px;
  }
  .terminal-actions .btn-icon:hover{ background: rgba(0,0,0,.06); color:#111; }

  .terminal-toast__body{ padding:.75rem .75rem .5rem; background:#fff; }
  .terminal-output{
    margin:0;
    max-height:220px;
    overflow:auto;
    white-space: pre-wrap;
    word-break: break-word;
    line-height:1.35;
    font-size:.82rem;
    color:#1c1c1c;
  }

  .terminal-status{ font-size:.7rem; opacity:.9; margin-top:.5rem; }
  .terminal-status--success { color:#0f7b12; }  /* subtle green */
  .terminal-status--warning { color:#9a6b00; }  /* amber/brown */
  .terminal-status--error   { color:#b00020; }  /* material red */

  /* Expanded mode */
  .terminal-toast.expanded {
  width: 90vw;
  height: 70vh;
  max-width: 1200px;
  display: flex;
  flex-direction: column;
}

  /* Optional: thin scrollbars to match macOS feel */
  .terminal-output::-webkit-scrollbar{ width:10px; height:10px; }
  .terminal-output::-webkit-scrollbar-thumb{
    background: rgba(0,0,0,.18);
    border-radius: 8px;
    border: 2px solid #fff;
  }
  .terminal-output::-webkit-scrollbar-track{ background: transparent; }

    .terminal-toast.minimized .terminal-toast__body{
    display: none;
  }
  .terminal-toast.minimized .terminal-actions .btn-icon[aria-label="Expand/Maximize"]{
    opacity: .5;
    pointer-events: none; /* maximize disabled while minimized */
  }

  /* optional: cursor hints on dots */
  .terminal-dot{ cursor: pointer; }
  .terminal-dot:active{ transform: translateY(1px); }

  /* Expanded fullscreen-ish view */
.terminal-toast.expanded {
  width: 90vw;
  height: 70vh;
  max-width: 1200px;
  display: flex;
  flex-direction: column;
}

.terminal-toast.expanded .terminal-toast__body {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  min-height: 0;                  /* <- key for scroll inside flex */
}


.terminal-toast.expanded .terminal-output {
  flex: 1 1 auto;
  min-height: 0;                  /* <- allow it to shrink */
  max-height: none !important;    /* <- fill available space */
  overflow: auto;                 /* <- activate scrolling */
  -webkit-overflow-scrolling: touch; /* smooth on iOS */
  overscroll-behavior: contain;   /* keep scroll contained */
}


</style>

<style>
/* Pete flash for newly inserted table rows */
@keyframes peteRowFlash {
  0%   { background-color: rgba(13, 39, 77, 0.00); } /* transparent */
  20%  { background-color: rgba(13, 39, 77, 0.10); }
  50%  { background-color: rgba(13, 39, 77, 0.18); }
  80%  { background-color: rgba(13, 39, 77, 0.10); }
  100% { background-color: rgba(13, 39, 77, 0.00); }
}

/* apply to every cell so the full row highlights */
tr.row-flash > td {
  animation: peteRowFlash 1.2s ease-in-out 0s 3; /* ~3.6s total */
}
</style>


    <style>
        :root { --pete-blue:#0D274D; --pete-green:#003B24; }
        body { font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif; }
        /* App navbar gradient */
        .navbar {
            background-color: var(--pete-blue);
            background-image: linear-gradient(90deg, var(--pete-blue) 0%, var(--pete-green) 100%);
        }
        /* Offcanvas / sidebar */


        .export_button{
            padding-top: 5px !important;
            padding-bottom: 5px !important;
        }

		.upgrade_link{
			color: rgb(102, 77, 3);
			text-decoration: underline;
			font-weight:bold;
			cursor: pointer;
		}
		.upgrade_link a:hover{
			background-color: rgb(102, 77, 3);
		}

		.table-responsive a{
			padding-top: 6px;
		}

		.table-responsive button{
			border-bottom-left-radius: 0px !important;
			border-bottom-right-radius: 0px !important;
			border-top-right-radius: 0px !important;
			border-top-left-radius: 0px !important;
			font-size: 14px;
		}

        #sidebar { width: 220px; !important }

        .list-group-item {
            width: 220px; !important
        }

        .sidebar-link.active { background: rgba(13,39,77,.12); font-weight: 600; width: 220px !important; }
        .sidebar-link i { width: 1.25rem; }
        main { flex: 1 1 auto; }

        /* Pretty CTA button style you used in the other layout */
        .btn-pete{
            background-image: linear-gradient(135deg, var(--pete-blue) 0%, var(--pete-green) 100%);
            color:#fff; font-weight:600; border:none; padding:.55rem 1.25rem; border-radius:.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,.25);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .btn-pete:hover,.btn-pete:focus{ color:#fff; transform:translateY(-2px); box-shadow:0 6px 14px rgba(0,0,0,.35); text-decoration:none; }
        .btn-pete:active{ color:#fff; transform:translateY(0); box-shadow:0 3px 8px rgba(0,0,0,.2); }

        /* Panel-ish helpers to mimic your old panels with BS5 */
        .panel      { border:1px solid rgba(0,0,0,.08); border-radius:.5rem; background:#fff; }
        .panel-heading { padding:.75rem 1rem; border-bottom:1px solid rgba(0,0,0,.06); }
        .panel-footer  { padding:.75rem 1rem; border-top:1px solid rgba(0,0,0,.06); }
		.navbar{

			/* 1️⃣ Solid fallback for very old browsers */
			background-color: #0D274D;

			/* 2️⃣ Modern browsers */
			background-image: linear-gradient(90deg, #0D274D 0%, #003B24 100%);

			background-repeat: no-repeat;
			background-size: cover;
		}
    </style>


    @stack('head')
</head>
<body class="bg-light d-flex flex-column min-vh-100">

{{-- NAVBAR --}}
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" style="padding: 10px" href="/">
            <img src="/PetePanelLogo.png" alt="Pete" style="height:25px">
            <span class="small">
                {{-- show Pete version if available --}}
                {{ isset($pete_options) ? $pete_options->get_meta_value('version') : '' }}
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle sidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/register-your-license">Register</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('users.myaccount') }}">My Account</a></li>
                <li class="nav-item"><a class="nav-link" id="logout" href="/logout">Log out</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="d-flex flex-grow-1">
    {{-- SIDEBAR (Offcanvas) --}}
    <div class="offcanvas-lg offcanvas-start bg-white border-end" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="sidebarLabel">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">

            <nav class="list-group list-group-flush">
                {{-- Core sections from your original sidebar --}}
                <a href="/sites" class="list-group-item list-group-item-action sidebar-link @if($viewsw==='sites') active @endif">
                    <i class="bi bi-window"></i> Sites
                </a>

                <a href="/backups" class="list-group-item list-group-item-action sidebar-link @if($viewsw==='backups') active @endif">
					<i class="bi bi-cloud-arrow-up"></i> Backup
                </a>

                <a href="{{ route('base.security') }}" class="list-group-item list-group-item-action sidebar-link @if($viewsw==='/security') active @endif">
                    <i class="bi bi-shield-lock"></i> Security
                </a>

              <a href="/wordpress-importer" class="list-group-item list-group-item-action sidebar-link @if($viewsw==='/wordpress-importer') active @endif">
                    <i class="bi bi-plug"></i> Import
              </a>

                 {{-- Admin-only --}}

                    <a href="{{ route('admin.server.status') }}" class="list-group-item list-group-item-action sidebar-link @if($viewsw==='/serverStatus') active @endif">
                        <i class="bi bi-speedometer2"></i> Server Status
                    </a>

                    <a href="{{ route('admin.phpinfo') }}" class="list-group-item list-group-item-action sidebar-link @if($viewsw==='phpinfoPanel') active @endif">
                        <i class="bi bi-filetype-php"></i> phpinfo()
                    </a>
                
                    <a href="{{ route('admin.phpmyadmin') }}" class="list-group-item list-group-item-action sidebar-link @if($viewsw==='/phpmyadminPanel') active @endif">
                            <i class="bi bi-database-gear"></i> phpmyadmin
                    </a>

                    <a href="{{ route('users.index') }}" 
                        class="list-group-item list-group-item-action sidebar-link @if($viewsw==='users') active @endif">
                            <i class="bi bi-people"></i> Users
                    </a>
                        
                    <a href="/options" class="list-group-item list-group-item-action sidebar-link @if($viewsw==='options') active @endif">
                            <i class="bi bi-sliders2-vertical"></i> system variables
                    </a>

           
               
            </nav>

            {{-- Loader mount point (kept from your old layout) --}}
            <div id="loading_area" class="p-3"></div>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <main class="flex-fill p-4">
        {{-- Demo warning banner logic (kept) --}}
        @php $pete_demo = env('PETE_DEMO'); @endphp
        @if($pete_demo === "active")
            <div class="alert alert-warning small">
                <strong>Warning!</strong> this is a demo version of WordPress Pete, all created projects will be deleted automatically every 24 hours.
            </div>
        @endif

        {{-- Version update notice placeholder (kept) --}}
        <div id="update_area_info"></div>

        @yield('header')
        @yield('content')
    </main>
</div>

{{-- FOOTER --}}
<footer class="py-3 bg-white border-top small">
    <div class="container-fluid d-flex justify-content-between">
        <div>
            @if(isset($system_vars["apache_v_dialog"])) {{ $system_vars["apache_v_dialog"] }}<br>@endif
            @if(isset($system_vars["mysql_v_dialog"])) {{ $system_vars["mysql_v_dialog"] }} @endif
        </div>
        <div class="text-end">
           Build Anywhere. Deploy Everywhere. <a href="https://deploypete.com">deploypete.com</a>
    </div>
</footer>

{{-- JS: Bootstrap 5 bundle (Popper included) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<style>[v-cloak]{display:none}</style>

<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastArea" style="z-index: 1080;"></div>
<div class="terminal-toast-stack" id="terminalToastArea" style="z-index: 1090;"></div>


<script>
window.PETE_TOAST_DELAY = window.PETE_TOAST_DELAY ?? 3000; // default 3s

window.toast = function (msg, type, delay) {
  const area = document.getElementById('toastArea');
  const div = document.createElement('div');
  div.className = 'toast align-items-center border-0';
  div.role = 'alert';
  div.ariaLive = 'assertive';
  div.ariaAtomic = 'true';
  div.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${msg}</div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  if (type === 'success') div.classList.add('text-bg-success');
  else if (type === 'danger' || type === 'error') div.classList.add('text-bg-danger');
  else if (type === 'warning') div.classList.add('text-bg-warning');
  else div.classList.add('text-bg-secondary');

  area.appendChild(div);
  const t = new bootstrap.Toast(div, { delay: Number(delay ?? window.PETE_TOAST_DELAY) });
  t.show();
};
</script>

<script>
/**
 * Pete Terminal Toast (macOS Light) — Full JS with robust copy support
 * Public API:
 *   const id = toastTerminal(message, options?)
 *   toastTerminalAppend(id, line, opts?)
 *   toastTerminalClose(id)
 *
 * Options:
 *   id?: string
 *   title?: string = 'Terminal'
 *   status?: '' | 'success' | 'warning' | 'error'
 *   delay?: number (ms) = 0 (sticky)
 *   maxHeight?: number (px) = 220
 *   autoScroll?: boolean = true
 *   allowHTML?: boolean = false
 *   theme?: 'light' | 'dark' = 'light'
 */
(function () {
  // Ensure a stack container exists
  let stack = document.getElementById('terminalToastArea');
  if (!stack) {
    stack = document.createElement('div');
    stack.id = 'terminalToastArea';
    stack.className = 'terminal-toast-stack';
    stack.style.zIndex = 1090;
    document.body.appendChild(stack);
  }

  const ACTIVE = new Map(); // id -> { card, pre, timer }

  function uid() { return 'tt-' + Math.random().toString(36).slice(2, 10); }

  function buttonIcon(char, title) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'btn-icon';
    b.title = title || '';
    b.setAttribute('aria-label', title || '');
    b.textContent = char;
    return b;
  }

  // Robust clipboard copy with fallback for non-secure contexts / older browsers
  async function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return true;
    }
    const ta = document.createElement('textarea');
    ta.value = text || '';
    ta.style.position = 'fixed';
    ta.style.top = '-9999px';
    ta.style.left = '-9999px';
    ta.setAttribute('readonly', '');
    document.body.appendChild(ta);
    ta.select();
    ta.setSelectionRange(0, ta.value.length);
    let ok = false;
    try { ok = document.execCommand('copy'); } catch (_) { ok = false; }
    document.body.removeChild(ta);
    return ok;
  }

  function destroy(id) {
    const entry = ACTIVE.get(id);
    if (!entry) return;
    if (entry.timer) clearTimeout(entry.timer);
    entry.card.remove();
    ACTIVE.delete(id);
  }

  function createTerminalToast(message, opts = {}) {
    const {
      id = uid(),
      title = 'Terminal',
      status = '',          // '', 'success' | 'warning' | 'error'
      delay = 0,            // sticky by default
      maxHeight = 220,
      autoScroll = true,
      allowHTML = false,
      theme = 'light'       // 'light' | 'dark'
    } = opts;

    // --- Card ------------------------------------------------------------
    const card = document.createElement('div');
    card.className = 'terminal-toast';
    if (theme === 'light') card.classList.add('terminal--light');
    if (theme === 'dark')  card.classList.add('terminal--dark');
    card.dataset.toastId = id;

    // --- Titlebar --------------------------------------------------------
    const bar  = document.createElement('div');
    bar.className = 'terminal-toast__titlebar';

    const left = document.createElement('div');
    left.className = 'd-flex align-items-center';

    const dots = document.createElement('div');
    dots.className = 'terminal-toast__dots';

    // Dots: 🔴 close, 🟡 minimize, 🟢 maximize
    const d1 = document.createElement('span'); d1.className = 'terminal-dot terminal-dot--close'; d1.title = 'Close';
    const d2 = document.createElement('span'); d2.className = 'terminal-dot terminal-dot--min';   d2.title = 'Minimize';
    const d3 = document.createElement('span'); d3.className = 'terminal-dot terminal-dot--max';   d3.title = 'Expand/Maximize';
    [d1,d2,d3].forEach(dot => { dot.tabIndex = 0; dot.setAttribute('role','button'); });
    dots.append(d1,d2,d3);

    const ttl = document.createElement('span');
    ttl.className = 'terminal-title';
    ttl.textContent = title;

    left.append(dots, ttl);

    // Right actions
    const right = document.createElement('div');
    right.className = 'terminal-actions';
    const btnCopy   = buttonIcon('📋', 'Copy output');
    const btnExpand = buttonIcon('⤢', 'Expand/Maximize');
    const btnClose  = buttonIcon('✕', 'Close');
    right.append(btnCopy, btnExpand, btnClose);

    bar.append(left, right);

    // --- Body ------------------------------------------------------------
    const body = document.createElement('div');
    body.className = 'terminal-toast__body';

    const pre = document.createElement('pre');
    pre.className = 'terminal-output';
    pre.style.maxHeight = (Number(maxHeight) || 220) + 'px';
    if (allowHTML) pre.innerHTML = String(message ?? '');
    else pre.textContent = String(message ?? '');

    const statusLine = document.createElement('div');
    statusLine.className = 'terminal-status' + (status ? ` terminal-status--${status}` : '');
    if (status) statusLine.textContent = status.toUpperCase();

    body.append(pre, statusLine);

    // --- Actions wiring --------------------------------------------------
    btnCopy.addEventListener('click', async () => {
      const text = pre.textContent || '';
      try {
        const ok = await copyText(text);
        const old = btnCopy.textContent;
        btnCopy.textContent = ok ? '✓' : '⚠';
        setTimeout(() => { btnCopy.textContent = old; }, 900);
      } catch {
        const old = btnCopy.textContent;
        btnCopy.textContent = '⚠';
        setTimeout(() => { btnCopy.textContent = old; }, 900);
      }
    });

    const toggleExpand = () => {
      card.classList.toggle('expanded');       // CSS should size to 90vw × 70vh etc.
      if (card.classList.contains('expanded')) card.classList.remove('minimized');
      pre.focus?.();
    };
    btnExpand.addEventListener('click', toggleExpand);

    const destroySelf = () => destroy(id);
    btnClose.addEventListener('click', destroySelf);

    // Dots:
    // 🔴 Close
    d1.addEventListener('click', destroySelf);
    d1.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') destroySelf(); });

    // 🟡 Minimize (collapse body, keep titlebar)
    const toggleMinimize = () => {
      card.classList.toggle('minimized');
      if (card.classList.contains('minimized')) card.classList.remove('expanded');
    };
    d2.addEventListener('click', toggleMinimize);
    d2.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') toggleMinimize(); });

    // 🟢 Maximize (same as Expand)
    const toggleMaximize = () => {
      if (card.classList.contains('minimized')) return;
      toggleExpand();
    };
    d3.addEventListener('click', toggleMaximize);
    d3.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') toggleMaximize(); });

    // --- Compose + mount -------------------------------------------------
    card.append(bar, body);
    stack.appendChild(card);

    // Auto-scroll initial
    if (autoScroll) requestAnimationFrame(() => { pre.scrollTop = pre.scrollHeight; });

    // Auto-dismiss
    let timer = null;
    if (delay && Number(delay) > 0) timer = setTimeout(() => destroy(id), Number(delay));

    ACTIVE.set(id, { card, pre, timer });
    return id;
  }

  function appendToTerminalToast(id, line, opts = {}) {
    const entry = ACTIVE.get(id);
    if (!entry) return false;
    const { pre, card } = entry;

    // Optional: un-minimize when new output arrives
    if (card.classList.contains('minimized')) card.classList.remove('minimized');

    const wasAtBottom = (pre.scrollTop + pre.clientHeight) >= (pre.scrollHeight - 5);
    pre.textContent += (pre.textContent.endsWith('\n') ? '' : '\n') + String(line ?? '');
    if ((opts.autoScroll ?? true) && wasAtBottom) pre.scrollTop = pre.scrollHeight;
    return true;
  }

  // Public API ------------------------------------------------------------
  window.toastTerminal = function (message, options) {
    return createTerminalToast(message, options);
  };
  window.toastTerminalAppend = function (id, line, options) {
    return appendToTerminalToast(id, line, options);
  };
  window.toastTerminalClose = function (id) {
    destroy(id);
  };

    // Create/return a progress bar inside the toast card
    window.toastTerminalEnsureProgress = function (id) {
    const entry = (window.__TT_ACTIVE__ || new Map()).get?.(id) || (window.__TT_ACTIVE__ = window.__TT_ACTIVE__ || new Map(), null);
    // The original script stores ACTIVE internally; expose it so we can reuse:
    };

})();
</script>




@stack('scripts')



</body>
</html>
