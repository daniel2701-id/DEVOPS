<?php
require_once '../includes/auth.php';
requireLogin();
$username = e($_SESSION['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Seismograph</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #0a0a0a; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
header {
  background: #0d0d0d;
  border-bottom: 1px solid #1e1e1e;
  padding: 14px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
}
.brand { font-size: 1rem; font-weight: 700; color: #fff; }
.header-right { display: flex; align-items: center; gap: 16px; }
.user-info { font-size: .8rem; color: #666; }
.user-info strong { color: #ccc; }
.logout { font-size: .8rem; color: #ff6060; text-decoration: none; border: 1px solid rgba(255,96,96,.3); padding: 5px 12px; border-radius: 4px; transition: all .2s; }
.logout:hover { background: rgba(255,96,96,.1); }
.live { font-size: .75rem; color: #4caf50; }
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  padding: 16px 24px;
}
.stat { background: #111; border: 1px solid #1e1e1e; border-radius: 8px; padding: 14px; }
.stat-label { font-size: .7rem; color: #555; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
.stat-value { font-size: 1.8rem; font-weight: 700; color: #4da6ff; line-height: 1; }
.stat-sub { font-size: .72rem; color: #444; margin-top: 4px; }
.main-grid {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 12px;
  padding: 0 24px 16px;
}
@media(max-width:900px){ .main-grid { grid-template-columns: 1fr; } }
.panel { background: #111; border: 1px solid #1e1e1e; border-radius: 8px; overflow: hidden; }
.panel-header { padding: 10px 16px; border-bottom: 1px solid #1e1e1e; display: flex; justify-content: space-between; align-items: center; }
.panel-title { font-size: .8rem; font-weight: 600; color: #ccc; }
.panel-badge { font-size: .7rem; color: #555; background: #0d0d0d; border: 1px solid #1e1e1e; border-radius: 4px; padding: 2px 8px; }
#map { height: 400px; }
.chart-wrap { padding: 14px; height: 240px; }
.sidebar { display: flex; flex-direction: column; gap: 12px; }
.event-list { max-height: 380px; overflow-y: auto; }
.event-item { padding: 10px 14px; border-bottom: 1px solid #161616; display: grid; grid-template-columns: 40px 1fr; gap: 10px; cursor: pointer; transition: background .15s; }
.event-item:hover { background: #161616; }
.event-item.active { background: #141a22; border-left: 2px solid #4da6ff; }
.mag-badge { width: 40px; height: 40px; border-radius: 6px; display: grid; place-items: center; font-size: .82rem; font-weight: 700; }
.mag-green  { background: rgba(76,175,80,.15); color: #4caf50; border: 1px solid rgba(76,175,80,.3); }
.mag-yellow { background: rgba(255,193,7,.15); color: #ffc107; border: 1px solid rgba(255,193,7,.3); }
.mag-orange { background: rgba(255,152,0,.15); color: #ff9800; border: 1px solid rgba(255,152,0,.3); }
.mag-red    { background: rgba(244,67,54,.15); color: #f44336; border: 1px solid rgba(244,67,54,.3); }
.event-loc  { font-size: .78rem; font-weight: 600; color: #ccc; margin-bottom: 3px; }
.event-meta { font-size: .68rem; color: #555; }
.detail-box { padding: 14px; font-size: .78rem; }
.detail-row { display: flex; justify-content: space-between; margin-bottom: 7px; }
.detail-key { color: #555; }
.detail-val { color: #ccc; text-align: right; max-width: 55%; }
.bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 24px 24px; }
@media(max-width:700px){ .bottom-grid { grid-template-columns: 1fr; } }
.loading { display: flex; align-items: center; justify-content: center; padding: 30px; color: #444; font-size: .8rem; }
footer { text-align: center; padding: 14px; font-size: .72rem; color: #333; border-top: 1px solid #1a1a1a; }
</style>
</head>
<body>
<header>
  <div class="brand">Seismograph Dashboard</div>
  <div class="header-right">
    <span class="live">Live · Auto-refresh 60s</span>
    <span class="user-info">Login sebagai <strong><?= $username ?></strong></span>
    <a href="/auth/logout.php" class="logout">Logout</a>
  </div>
</header>

<div class="stats">
  <div class="stat"><div class="stat-label">Total Tercatat</div><div class="stat-value" id="stat-total">-</div><div class="stat-sub">entri di log</div></div>
  <div class="stat"><div class="stat-label">Magnitudo Terkini</div><div class="stat-value" id="stat-mag">-</div><div class="stat-sub">skala Richter</div></div>
  <div class="stat"><div class="stat-label">Mag. Tertinggi</div><div class="stat-value" id="stat-max">-</div><div class="stat-sub">dalam log</div></div>
  <div class="stat"><div class="stat-label">Mag. Rata-rata</div><div class="stat-value" id="stat-avg">-</div><div class="stat-sub">seluruh log</div></div>
  <div class="stat"><div class="stat-label">Update Terakhir</div><div class="stat-value" style="font-size:1rem;padding-top:4px" id="stat-time">-</div><div class="stat-sub" id="stat-date">-</div></div>
</div>

<div class="main-grid">
  <div style="display:flex;flex-direction:column;gap:12px">
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Peta Epicentrum</span>
        <span class="panel-badge" id="map-count">memuat...</span>
      </div>
      <div id="map"></div>
    </div>
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Riwayat Magnitudo</span>
        <span class="panel-badge">20 data terakhir</span>
      </div>
      <div class="chart-wrap"><canvas id="magChart"></canvas></div>
    </div>
  </div>
  <div class="sidebar">
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Gempa Terkini</span>
        <span class="panel-badge" id="alert-status">-</span>
      </div>
      <div id="detail-box" class="detail-box"><div class="loading">Memuat data...</div></div>
    </div>
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Log Kejadian</span>
        <span class="panel-badge" id="list-count">-</span>
      </div>
      <div class="event-list" id="event-list"><div class="loading">Memuat data...</div></div>
    </div>
  </div>
</div>

<div class="bottom-grid">
  <div class="panel">
    <div class="panel-header"><span class="panel-title">Distribusi Magnitudo</span><span class="panel-badge">kategori</span></div>
    <div class="chart-wrap"><canvas id="distChart"></canvas></div>
  </div>
  <div class="panel">
    <div class="panel-header"><span class="panel-title">Distribusi Kedalaman</span><span class="panel-badge">km</span></div>
    <div class="chart-wrap"><canvas id="depthChart"></canvas></div>
  </div>
</div>

<footer>Data dari BMKG &mdash; Diperbarui otomatis via Jenkins &mdash; Last refresh: <span id="last-refresh">-</span></footer>

<script>
// ============================================================
// OWASP A03 — XSS Prevention: semua data dari JSON di-escape
// sebelum dimasukkan ke DOM
// ============================================================
function esc(s) {
  if (s === null || s === undefined) return '-';
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#x27;');
}

const DATA_URL = './earthquake_log.json';
const map = L.map('map').setView([-2.5, 118], 5);
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 18 }).addTo(map);
let markers = L.layerGroup().addTo(map);

Chart.defaults.color = '#555';
Chart.defaults.borderColor = '#1e1e1e';

const magChart = new Chart(document.getElementById('magChart'), {
  type: 'line',
  data: { labels: [], datasets: [{ label: 'Magnitudo', data: [], borderColor: '#4da6ff', backgroundColor: 'rgba(77,166,255,.08)', pointBackgroundColor: '#4da6ff', pointRadius: 3, fill: true, tension: 0.3 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { maxTicksLimit: 8, font: { size: 9 } } }, y: { min: 0, max: 9, ticks: { stepSize: 1 } } } }
});

const distChart = new Chart(document.getElementById('distChart'), {
  type: 'doughnut',
  data: { labels: ['< 4.0', '4.0-4.9', '5.0-5.9', '>= 6.0'], datasets: [{ data: [0,0,0,0], backgroundColor: ['rgba(76,175,80,.3)','rgba(255,193,7,.3)','rgba(255,152,0,.3)','rgba(244,67,54,.3)'], borderColor: ['#4caf50','#ffc107','#ff9800','#f44336'], borderWidth: 1.5 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 10 } } } }
});

const depthChart = new Chart(document.getElementById('depthChart'), {
  type: 'bar',
  data: { labels: ['< 10 km', '10-60 km', '60-300 km', '> 300 km'], datasets: [{ data: [0,0,0,0], backgroundColor: ['rgba(77,166,255,.5)','rgba(76,175,80,.5)','rgba(255,193,7,.5)','rgba(244,67,54,.5)'], borderColor: ['#4da6ff','#4caf50','#ffc107','#f44336'], borderWidth: 1.5, borderRadius: 4 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

function magClass(m) {
  if (m >= 6) return { cls: 'mag-red',    clr: '#f44336' };
  if (m >= 5) return { cls: 'mag-orange', clr: '#ff9800' };
  if (m >= 4) return { cls: 'mag-yellow', clr: '#ffc107' };
  return       { cls: 'mag-green',  clr: '#4caf50' };
}
function parseMag(s)   { return parseFloat(String(s).replace(/[^\d.]/g,'')) || 0; }
function parseDepth(s) { const m = String(s).match(/[\d.]+/); return m ? parseFloat(m[0]) : 0; }

let activeIdx = 0, currentQuakes = [];

function buildDetailBox(q) {
  const m    = parseMag(q.magnitude);
  const mc   = magClass(m);
  const box  = document.getElementById('detail-box');
  box.innerHTML = '';

  const rows = [
    ['Tanggal',   q.tanggal],
    ['Jam',       q.jam],
    ['Magnitude', q.magnitude + ' SR', mc.clr],
    ['Lokasi',    q.lokasi],
    ['Kedalaman', q.kedalaman],
    ['Dirasakan', q.dirasakan || '-'],
    ['Potensi',   q.potensi   || '-'],
  ];

  rows.forEach(([key, val, color]) => {
    const row  = document.createElement('div');
    row.className = 'detail-row';
    const k = document.createElement('span');
    k.className   = 'detail-key';
    k.textContent = key;
    const v = document.createElement('span');
    v.className   = 'detail-val';
    v.textContent = val;
    if (color) { v.style.color = color; v.style.fontWeight = '700'; }
    row.appendChild(k);
    row.appendChild(v);
    box.appendChild(row);
  });
}

function buildEventList(quakes) {
  const list = document.getElementById('event-list');
  list.innerHTML = '';
  quakes.forEach((q, i) => {
    const m  = parseMag(q.magnitude);
    const mc = magClass(m);

    const item = document.createElement('div');
    item.className = 'event-item' + (i === activeIdx ? ' active' : '');
    item.onclick   = () => focusQuake(i);

    const badge = document.createElement('div');
    badge.className   = 'mag-badge ' + mc.cls;
    badge.textContent = m.toFixed(1);

    const info = document.createElement('div');

    const loc = document.createElement('div');
    loc.className   = 'event-loc';
    loc.textContent = q.lokasi;

    const meta = document.createElement('div');
    meta.className   = 'event-meta';
    meta.textContent = (q.tanggal || '') + ' ' + (q.jam || '') + ' · ' + (q.kedalaman || '');

    info.appendChild(loc);
    info.appendChild(meta);
    item.appendChild(badge);
    item.appendChild(info);
    list.appendChild(item);
  });
}

function render(data) {
  const quakes = data.earthquakes || [];
  if (!quakes.length) {
    document.getElementById('detail-box').innerHTML = '<div class="loading">Belum ada data.</div>';
    return;
  }

  const mags   = quakes.map(q => parseMag(q.magnitude));
  const latest = quakes[0];

  document.getElementById('stat-total').textContent = quakes.length;
  document.getElementById('stat-mag').textContent   = parseMag(latest.magnitude).toFixed(1);
  document.getElementById('stat-max').textContent   = Math.max(...mags).toFixed(1);
  document.getElementById('stat-avg').textContent   = (mags.reduce((a,b)=>a+b,0)/mags.length).toFixed(1);
  document.getElementById('stat-time').textContent  = latest.jam     || '-';
  document.getElementById('stat-date').textContent  = latest.tanggal || '-';

  // alert-status pakai textContent — aman dari XSS
  document.getElementById('alert-status').textContent = 'M ' + esc(latest.magnitude) + ' SR';

  buildDetailBox(latest);
  document.getElementById('list-count').textContent = quakes.length + ' gempa';
  buildEventList(quakes);

  markers.clearLayers();
  quakes.forEach((q, i) => {
    const lat = parseFloat(q.lat);
    const lon = parseFloat(q.lon);
    if (isNaN(lat) || isNaN(lon)) return;
    const m  = parseMag(q.magnitude);
    const mc = magClass(m);
    const c  = L.circleMarker([lat,lon], {
      radius:      Math.max(6, m*4),
      color:       mc.clr,
      fillColor:   mc.clr,
      fillOpacity: i === 0 ? .7 : .3,
      weight:      i === 0 ? 2  :  1
    }).addTo(markers);
    // Leaflet popup: pakai esc() untuk semua field dari JSON
    c.bindPopup(
      '<div style="font-family:sans-serif;font-size:12px">'
      + '<b>M ' + esc(q.magnitude) + ' SR</b><br>'
      + esc(q.lokasi) + '<br>'
      + '<span style="color:#888">' + esc(q.tanggal) + ' ' + esc(q.jam) + '</span>'
      + '</div>'
    );
    if (i === 0) { c.openPopup(); map.setView([lat,lon], 6, {animate:true}); }
  });
  document.getElementById('map-count').textContent = quakes.length + ' epicentrum';

  const recent = quakes.slice(0,20).reverse();
  magChart.data.labels                  = recent.map(q => q.jam || '');
  magChart.data.datasets[0].data        = recent.map(q => parseMag(q.magnitude));
  magChart.update();

  const dist = [0,0,0,0];
  mags.forEach(m => { if(m>=6)dist[3]++; else if(m>=5)dist[2]++; else if(m>=4)dist[1]++; else dist[0]++; });
  distChart.data.datasets[0].data = dist;
  distChart.update();

  const dep = [0,0,0,0];
  quakes.forEach(q => {
    const d = parseDepth(q.kedalaman);
    if(d>300)dep[3]++; else if(d>60)dep[2]++; else if(d>=10)dep[1]++; else dep[0]++;
  });
  depthChart.data.datasets[0].data = dep;
  depthChart.update();

  document.getElementById('last-refresh').textContent = new Date().toLocaleTimeString('id-ID');
}

function focusQuake(idx) {
  activeIdx    = idx;
  const q      = currentQuakes[idx];
  if (!q) return;
  const lat = parseFloat(q.lat);
  const lon = parseFloat(q.lon);
  if (!isNaN(lat) && !isNaN(lon)) map.setView([lat,lon], 8, {animate:true});
  render({ earthquakes: currentQuakes });
}
window.focusQuake = focusQuake;

async function fetchData() {
  try {
    const res = await fetch(DATA_URL + '?t=' + Date.now());
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data     = await res.json();
    currentQuakes  = data.earthquakes || [];
    render(data);
  } catch(e) {
    const box = document.getElementById('detail-box');
    box.innerHTML = '';
    const msg = document.createElement('div');
    msg.className   = 'loading';
    msg.style.color = '#f44336';
    msg.textContent = 'Gagal memuat: ' + e.message;
    box.appendChild(msg);
  }
}
fetchData();
setInterval(fetchData, 60000);
</script>
</body>
</html>
