<style>
:root {
    --bg: #f6f7f9; --surface: #ffffff; --surface-2: #f1f3f6; --border: #e3e6eb;
    --text: #1b1f27; --muted: #667085; --accent: #4f46e5; --accent-ink: #ffffff; --accent-soft: #eef0ff;
    --danger: #d92d20; --danger-soft: #fef3f2; --ok: #067647; --ok-soft: #ecfdf3; --warn: #b54708; --warn-soft: #fffaeb;
    --radius: 10px; --shadow: 0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.08);
    --gray: #475467; --gray-bg: #f2f4f7; --amber: #b54708; --amber-bg: #fef6e7; --red: #b42318; --red-bg: #fef3f2;
    --blue: #175cd3; --blue-bg: #eff8ff; --violet: #6927da; --violet-bg: #f4f3ff; --green: #067647; --green-bg: #ecfdf3; --slate: #344054; --slate-bg: #eaecf0;
}
@media (prefers-color-scheme: dark) {
    :root {
        --bg: #0f1115; --surface: #171a21; --surface-2: #1e222b; --border: #2a2f3a;
        --text: #e7e9ee; --muted: #98a2b3; --accent: #8b87ff; --accent-ink: #0f1115; --accent-soft: #23234a;
        --danger: #f97066; --danger-soft: #3a1714; --ok: #47cd89; --ok-soft: #0f2d20; --warn: #fdb022; --warn-soft: #33250b;
        --shadow: none;
        --gray: #cfd4dc; --gray-bg: #2a2f3a; --amber: #fdb022; --amber-bg: #3a2a0c; --red: #f97066; --red-bg: #3a1714;
        --blue: #84caff; --blue-bg: #102a43; --violet: #bdb4fe; --violet-bg: #2b2250; --green: #47cd89; --green-bg: #0f2d20; --slate: #d0d5dd; --slate-bg: #2a2f3a;
    }
}
* { box-sizing: border-box; }
body { margin: 0; background: var(--bg); color: var(--text); font: 14px/1.5 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }
h1 { font-size: 22px; margin: 0; letter-spacing: -.01em; }
h2 { font-size: 16px; margin: 0 0 12px; }
h3 { font-size: 14px; margin: 0 0 8px; }
.muted { color: var(--muted); } .small { font-size: 12px; }
.shell { display: grid; grid-template-columns: 232px 1fr; min-height: 100vh; }
.sidebar { background: var(--surface); border-right: 1px solid var(--border); padding: 18px 14px; display: flex; flex-direction: column; gap: 14px; position: sticky; top: 0; height: 100vh; }
.brand { display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--text); font-size: 15px; }
.brand:hover { text-decoration: none; }
.brand-mark { width: 30px; height: 30px; border-radius: 8px; background: var(--accent); color: var(--accent-ink); display: grid; place-items: center; font-weight: 800; }
.nav { display: flex; flex-direction: column; gap: 2px; flex: 1; }
.nav a { color: var(--text); gap: 8px; padding: 8px 10px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
.nav a:hover { background: var(--surface-2); text-decoration: none; }
.nav a.active { background: var(--accent-soft); color: var(--accent); font-weight: 600; }
.pill { background: var(--accent); color: var(--accent-ink); border-radius: 999px; font-size: 11px; padding: 0 7px; font-weight: 700; }
.whoami { display: flex; gap: 10px; align-items: center; border-top: 1px solid var(--border); padding-top: 12px; }
.whoami-name { font-weight: 600; }
.avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--surface-2); display: grid; place-items: center; font-weight: 700; }
.main { padding: 24px 28px 60px; min-width: 0; }
.page-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 18px; flex-wrap: wrap; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); padding: 18px; }
.card + .card { margin-top: 16px; }
.grid { display: grid; gap: 16px; }
.grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.layout-side { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; }
.stat { font-size: 26px; font-weight: 700; letter-spacing: -.02em; }
.btn { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--border); background: var(--surface); color: var(--text); padding: 8px 14px; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; line-height: 1.2; }
.btn:hover { background: var(--surface-2); text-decoration: none; }
.btn-primary { background: var(--accent); border-color: var(--accent); color: var(--accent-ink); }
.btn-primary:hover { background: var(--accent); filter: brightness(1.08); }
.btn-danger { color: var(--danger); }
.btn-ok { background: var(--ok); border-color: var(--ok); color: #fff; }
.btn-sm { padding: 5px 10px; font-size: 12px; }
.btn-block { width: 100%; justify-content: center; }
.link-btn { background: none; border: 0; color: var(--accent); cursor: pointer; font: inherit; padding: 0; }
.link-btn.danger { color: var(--danger); }
label { display: block; font-weight: 600; margin-bottom: 6px; }
.field { margin-bottom: 14px; }
.help { color: var(--muted); font-size: 12px; margin-top: 4px; }
input[type=text], input[type=email], input[type=url], input[type=number], input[type=search], input[type=datetime-local], input[type=file], select, textarea {
    width: 100%; background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; font: inherit;
}
input:focus, select:focus, textarea:focus, trix-editor:focus { outline: 2px solid var(--accent); outline-offset: -1px; border-color: transparent; }
textarea { min-height: 90px; resize: vertical; }
textarea.content { min-height: 380px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; }
trix-editor { background: var(--surface); border: 1px solid var(--border) !important; border-radius: 8px; min-height: 380px; }
.check { display: flex; gap: 8px; align-items: center; font-weight: 500; }
.check input { width: auto; }
.invalid { border-color: var(--danger) !important; }
.error { color: var(--danger); font-size: 12px; margin-top: 4px; }
.alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; border: 1px solid transparent; }
.alert-success { background: var(--ok-soft); color: var(--ok); }
.alert-error { background: var(--danger-soft); color: var(--danger); }
.alert-warning { background: var(--warn-soft); color: var(--warn); }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); font-weight: 600; }
tr:last-child td { border-bottom: 0; }
.table-wrap { overflow-x: auto; }
.card.flush { padding: 0; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; }
.badge-gray { color: var(--gray); background: var(--gray-bg); } .badge-amber { color: var(--amber); background: var(--amber-bg); }
.badge-red { color: var(--red); background: var(--red-bg); } .badge-blue { color: var(--blue); background: var(--blue-bg); }
.badge-violet { color: var(--violet); background: var(--violet-bg); } .badge-green { color: var(--green); background: var(--green-bg); }
.badge-slate { color: var(--slate); background: var(--slate-bg); }
.tabs { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 12px; }
.tabs a { padding: 6px 12px; border-radius: 999px; color: var(--muted); border: 1px solid transparent; }
.tabs a.active { background: var(--surface); border-color: var(--border); color: var(--text); font-weight: 600; }
.filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
.filters > * { width: auto; min-width: 150px; }
.type-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.type-card { border: 1px solid var(--border); border-radius: 10px; padding: 12px; cursor: pointer; display: flex; gap: 10px; align-items: flex-start; font-weight: 400; margin: 0; background: var(--surface); }
.type-card input { margin-top: 3px; width: auto; }
.type-card:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
.type-card strong { display: block; }
.slug-row { display: flex; align-items: center; gap: 6px; }
.slug-row .prefix { color: var(--muted); white-space: nowrap; font-size: 12px; }
.thumb { width: 56px; height: 40px; object-fit: cover; border-radius: 6px; background: var(--surface-2); display: block; }
.cover-preview { max-width: 100%; max-height: 220px; border-radius: 8px; display: block; margin-bottom: 8px; }
.timeline { list-style: none; margin: 0; padding: 0; }
.timeline li { padding: 8px 0; border-bottom: 1px dashed var(--border); }
.timeline li:last-child { border-bottom: 0; }
.comment { background: var(--surface-2); border-radius: 8px; padding: 8px 10px; margin-top: 6px; white-space: pre-wrap; }
.stack { display: flex; flex-direction: column; gap: 8px; }
.row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.spacer { flex: 1; }
.chips { display: flex; flex-wrap: wrap; gap: 6px; }
.chip { background: var(--surface-2); border-radius: 999px; padding: 2px 10px; font-size: 12px; }
.prose { max-width: 760px; font-size: 16px; line-height: 1.7; }
.prose img, .prose iframe, .prose video { max-width: 100%; }
.prose iframe { aspect-ratio: 16/9; width: 100%; height: auto; border: 0; border-radius: 8px; }
.cat-list { max-height: 220px; overflow: auto; border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; }
.pagination { margin-top: 14px; }
details > summary { cursor: pointer; font-weight: 600; }
[hidden] { display: none !important; }
@media (max-width: 1100px) { .layout-side { grid-template-columns: 1fr; } .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 800px) {
    .shell { grid-template-columns: 1fr; }
    .sidebar { position: static; height: auto; flex-direction: column; }
    .nav { flex-direction: row; flex-wrap: wrap; }
    .main { padding: 16px; }
    .grid-2, .grid-3, .grid-4, .type-grid { grid-template-columns: 1fr; }
    .filters > * { min-width: 0; width: 100%; }
}
</style>
