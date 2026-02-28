<html>
<head>
<meta charset="utf-8" />
<title>ModSecurity Audit Report</title>
<style>
  body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 16px; }
  .toolbar { display: grid; grid-template-columns: 1fr; gap: 8px; margin: 8px 0 16px; }
  .filters { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 8px; }
  .filters input { padding: 8px 10px; border:1px solid #ddd; border-radius:8px; font-size:14px; }
  .filters .hint { font-size: 12px; color: #666; grid-column: 1 / -1; }

  table { border-collapse: collapse; width: 100% }
  table, th, td { border: 1px solid #ddd; vertical-align: top; }
  th, td { padding: 0.35em 0.6em; }
  th { background:#222; color:#fff; }
  tr.overview { background:#fafafa; }
  tr.details  { display:none; opacity:0.98; }
  .meta { margin: 1rem 0; display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:10px;}
  .card { border:1px solid #ddd; border-radius:8px; padding:10px; }
  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace }
  .body { background:#F6F8FA; margin-top:1em; padding:1em; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; overflow-x:auto; width:100%; max-width:1024px; }
  .muted { color:#666; }
  a.toggle { text-decoration:none; }

  /* Badges */
  .badge { display:inline-block; padding:2px 6px; border-radius:9999px; font-size:12px; border:1px solid #ddd; margin-right:6px; }
  .badge-rule { background:#eef6ff; border-color:#cfe3ff; }
  .rule-list .badge-rule { margin: 0 6px 6px 0; }

  /* Severity colors */
  td[data-severity="CRITICAL"], .sev-CRITICAL { color:#b91c1c; font-weight:700; }
  td[data-severity="ERROR"],    .sev-ERROR    { color:#dc2626; font-weight:600; }
  td[data-severity="WARNING"],  .sev-WARNING  { color:#eab308; font-weight:600; }
  td[data-severity="NOTICE"],   .sev-NOTICE   { color:#2563eb; font-weight:600; }

  /* Hide when filtered */
  tr.hidden { display: none !important; }
</style>
</head>
<body>

<h1>ModSecurity Audit Report</h1>

<div class="meta">
  <div class="card">
    <div><strong>Total transactions</strong></div>
    <div class="mono">{{ summary.total }}</div>
  </div>
  <div class="card">
    <div><strong>Blocked (403/406/CRS)</strong></div>
    <div class="mono">{{ summary.blocked }}</div>
  </div>
  <div class="card">
    <div><strong>Status codes</strong></div>
    <div class="mono">
      {% for code, cnt in summary.by_status %}
        {{code}}: {{cnt}}{% if not loop.last %} • {% endif %}
      {% endfor %}
    </div>
  </div>
</div>

<div class="meta">
  <div class="card">
    <div><strong>Top rule IDs</strong> <span class="muted">(count)</span></div>
    <div class="mono">
      {% if summary.top_rules %}{% for rid, cnt in summary.top_rules %}
        <a class="sev-NOTICE" href="https://github.com/coreruleset/coreruleset/search?q={{ rid|e }}" target="_blank" rel="noopener">{{ rid }}</a> ({{ cnt }}){% if not loop.last %} • {% endif %}
      {% endfor %}{% else %}<span class="muted">—</span>{% endif %}
    </div>
  </div>
  <div class="card">
    <div><strong>Top IPs (blocked)</strong></div>
    <div class="mono">
      {% if summary.top_ips %}{% for ip, cnt in summary.top_ips %}
        {{ ip }} ({{ cnt }}){% if not loop.last %} • {% endif %}
      {% endfor %}{% else %}<span class="muted">—</span>{% endif %}
    </div>
  </div>
  <div class="card">
    <div><strong>Top URIs</strong></div>
    <div class="mono">
      {% if summary.top_uris %}{% for uri, cnt in summary.top_uris %}
        {{ uri }} ({{ cnt }}){% if not loop.last %} • {% endif %}
      {% endfor %}{% else %}<span class="muted">—</span>{% endif %}
    </div>
  </div>
</div>

<div class="toolbar">
  <div class="filters">
    <input id="flt-text"   type="text"   placeholder="🔎 Texto libre (msg, headers, etc.)" />
    <input id="flt-ip"     type="text"   placeholder="IP (ej. 203.0.113.5)" />
    <input id="flt-uri"    type="text"   placeholder="URI (ej. /wp-login.php)" />
    <input id="flt-rule"   type="text"   placeholder="Rule ID (ej. 942100)" />
    <input id="flt-status" type="text"   placeholder="Status (403, 200…)" />
    <div class="hint">Filtra por cualquier combinación. Borrar campos para ver todo de nuevo.</div>
  </div>
</div>

<table class="requests" id="requests">
  <tr>
    <th>Date / time</th>
    <th>Status</th>
    <th>Remote addr</th>
    <th>Request</th>
    <th>Main err</th>
    <th>Rules</th>
    <th></th>
  </tr>
  {% for entry in entries %}
    {% set req_line = entry["request"]["request_line"] or "" %}
    {% set rule_ids = (entry["errors"] | map(attribute='id') | select | list) %}
    {% set rule_ids_join = rule_ids | join(' ') %}
    <tr class="overview"
        data-ip="{{ entry['transaction']['remote_address']|e }}"
        data-uri="{{ (req_line.split()[1] if req_line.split()|length >= 2 else '')|e }}"
        data-status="{{ entry['response']['status']|e }}"
        data-ruleids="{{ rule_ids_join|e }}"
        data-text="{{ (entry['errors'][0].get('msg','') if entry['errors'] else '')|e }} {{ req_line|e }}">
      <td>{{ entry["transaction"]["time"]|e }}</td>
      <td><tt>{{ entry["response"]["status"]|e }}</tt></td>
      <td><tt>{{ entry["transaction"]["remote_address"]|e }}</tt></td>
      <td><tt>{{ req_line|e }}</tt></td>
      <td>
        {% if entry["errors"] %}
          {{ entry["errors"][0].get("msg","??")|e }}
        {% else %}
          <i>None</i>
        {% endif %}
      </td>
      <td class="rule-list">
        {% if rule_ids %}
          {% for rid in rule_ids %}
            <a class="badge badge-rule" href="https://github.com/coreruleset/coreruleset/search?q={{ rid|e }}" target="_blank" rel="noopener">#{{ rid }}</a>
          {% endfor %}
        {% else %}
          <span class="muted">—</span>
        {% endif %}
      </td>
      <td><a class="toggle" href="#" onclick="return showDetails(this);">Details</a></td>
    </tr>
    <tr class="details">
      <td colspan="7">
        <div class="details">
          <h2>ModSecurity Transaction ID</h2>
          <p class="mono">{{ entry["transaction"].get("transaction_id","")|e }}</p>

          <h2>Errors</h2>
          {% if not entry["errors"] %}
            <i>None</i>
          {% else %}
            <table class="details">
              <tr><th>Rule ID</th><th>File</th><th>Line</th><th>Severity</th><th>Msg</th></tr>
              {% for error in entry["errors"] %}
                <tr>
                  <td class="mono">
                    {% set rid = error.get("id","") %}
                    {% if rid %}
                      <a href="https://github.com/coreruleset/coreruleset/search?q={{ rid|e }}" target="_blank" rel="noopener">{{ rid|e }}</a>
                    {% else %}
                      <span class="muted">—</span>
                    {% endif %}
                  </td>
                  <td class="mono">{{ error.get("file","")|e }}</td>
                  <td class="mono">{{ error.get("line","")|e }}</td>
                  {% set sev = error.get("severity","") %}
                  <td class="mono sev-{{ sev|e }}" data-severity="{{ sev|e }}">{{ sev or "—" }}</td>
                  <td>{{ error.get("msg","")|e }}</td>
                </tr>
              {% endfor %}
            </table>
          {% endif %}

          <h2>Request</h2>
          <table class="details">
            <tr><th>Header</th><th>Value</th></tr>
            {% for header_k, header_v in entry["request"]["headers"].items() %}
              <tr><td class="mono">{{ header_k|e }}</td><td class="mono">{{ header_v|e }}</td></tr>
            {% endfor %}
          </table>

          {% if entry["request"]["body"] %}
            <h3>Body</h3>
            <div class="body">{{ "<br><hr><br>".join(entry["request"]["body"])|e }}</div>
          {% else %}
            <p><i>No request body</i></p>
          {% endif %}

          <h2>Response</h2>
          <table class="details">
            <tr><th>Header</th><th>Value</th></tr>
            {% for header_k, header_v in entry["response"].get("headers", {}).items() %}
              <tr><td class="mono">{{ header_k|e }}</td><td class="mono">{{ header_v|e }}</td></tr>
            {% endfor %}
          </table>

          {% if entry["response"].get("body") %}
            <h3>Body</h3>
            <div class="body">{{ entry["response"]["body"]|e }}</div>
          {% else %}
            <p><i>No response body</i></p>
          {% endif %}
        </div>
      </td>
    </tr>
  {% endfor %}
</table>

<script>
  function showDetails(anchor) {
    const row = anchor.closest('tr');
    const detailRow = row.nextElementSibling;
    if (!detailRow) return false;
    detailRow.style.display = (detailRow.style.display === 'table-row') ? 'none' : 'table-row';
    return false;
  }

  // Simple debounce
  function debounce(fn, ms) {
    let t; 
    return function(...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this,args), ms); };
  }

  const rows = Array.from(document.querySelectorAll('#requests tr.overview'));

  function applyFilter() {
    const qText   = document.getElementById('flt-text').value.trim().toLowerCase();
    const qIP     = document.getElementById('flt-ip').value.trim().toLowerCase();
    const qURI    = document.getElementById('flt-uri').value.trim().toLowerCase();
    const qRule   = document.getElementById('flt-rule').value.trim().toLowerCase();
    const qStatus = document.getElementById('flt-status').value.trim().toLowerCase();

    rows.forEach(row => {
      const ip     = (row.dataset.ip || '').toLowerCase();
      const uri    = (row.dataset.uri || '').toLowerCase();
      const ruleids= (row.dataset.ruleids || '').toLowerCase();
      const status = (row.dataset.status || '').toLowerCase();
      const text   = (row.dataset.text || '').toLowerCase();

      let match = true;
      if (qIP     && !ip.includes(qIP)) match = false;
      if (qURI    && !uri.includes(qURI)) match = false;
      if (qRule   && !ruleids.includes(qRule)) match = false;
      if (qStatus && !status.includes(qStatus)) match = false;
      if (qText   && !(text.includes(qText) || ip.includes(qText) || uri.includes(qText) || ruleids.includes(qText))) match = false;

      row.classList.toggle('hidden', !match);
      const details = row.nextElementSibling;
      if (details && details.classList.contains('details')) {
        details.classList.toggle('hidden', !match);
      }
    });
  }

  const inputs = ['flt-text','flt-ip','flt-uri','flt-rule','flt-status']
    .map(id => document.getElementById(id));
  inputs.forEach(i => i.addEventListener('input', debounce(applyFilter, 150)));
</script>
</body>
</html>
