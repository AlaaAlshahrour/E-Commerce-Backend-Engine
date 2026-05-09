<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Load Balancer Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: monospace; background: #0f1117; color: #e2e8f0; padding: 2rem; }
        h1 { color: #7dd3fc; margin-bottom: 0.5rem; font-size: 1.5rem; }
        .subtitle { color: #64748b; margin-bottom: 2rem; font-size: 0.9rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .node-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; transition: border-color 0.3s; }
        .node-card.active { border-color: #22d3ee; box-shadow: 0 0 20px rgba(34,211,238,0.1); }
        .node-name { font-size: 1.1rem; font-weight: bold; color: #7dd3fc; margin-bottom: 1rem; }
        .stat { display: flex; justify-content: space-between; margin: 0.4rem 0; font-size: 0.85rem; }
        .stat-label { color: #94a3b8; }
        .stat-value { color: #e2e8f0; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; }
        .badge-active { background: #164e63; color: #22d3ee; }
        .badge-idle { background: #1e293b; color: #64748b; border: 1px solid #334155; }
        .log-area { background: #0d1117; border: 1px solid #21262d; border-radius: 8px; padding: 1rem; height: 260px; overflow-y: auto; font-size: 0.8rem; }
        .log-entry { padding: 3px 0; border-bottom: 1px solid #21262d; display: flex; gap: 1rem; }
        .log-time { color: #4ade80; min-width: 90px; }
        .log-host { color: #7dd3fc; min-width: 160px; }
        .log-req { color: #94a3b8; }
        .btn { background: #1d4ed8; color: white; border: none; padding: 0.6rem 1.5rem;
            border-radius: 8px; cursor: pointer; font-family: monospace; margin-left: 0.5rem; }
        .btn:hover { background: #2563eb; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #ef4444; }
        .controls { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .counter { background: #1e293b; border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.85rem; }
        .counter span { color: #7dd3fc; font-weight: bold; font-size: 1.1rem; }
        .algo-badge { background: #4c1d95; color: #c4b5fd; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body>
<h1>⚖️ Load Balancer Dashboard</h1>
<p class="subtitle">Least Connections — NGINX + Docker — Real-time monitoring</p>

<div class="controls">
    <span class="algo-badge">🔄 Least Connections</span>
    <div class="counter">إجمالي الطلبات: <span id="total">0</span></div>
    <div class="counter">الطلبات/ثانية: <span id="rps">0</span></div>
    <button class="btn" onclick="sendBurst()">⚡ أرسل 20 طلباً</button>
    <button class="btn" onclick="toggleAuto()">▶ تلقائي</button>
    <button class="btn btn-danger" onclick="clearLog()">🗑 مسح</button>
</div>

<div class="grid" id="nodes-grid">
    <!-- تُبنى ديناميكياً -->
</div>

<p style="color:#64748b;font-size:0.85rem;margin-bottom:0.5rem;">سجل الطلبات الأخيرة</p>
<div class="log-area" id="log"></div>

<script>
    const stats   = {};
    let total     = 0;
    let autoTimer = null;
    let reqLast   = 0;
    let rpsTimer  = null;

    async function fetchNode() {
        const t0 = Date.now();
        try {
            const res  = await fetch('/api/node-info');
            const data = await res.json();
            const ms   = Date.now() - t0;
            recordRequest(data, ms);
        } catch(e) {
            addLog('error', 'فشل الاتصال', e.message, 0);
        }
    }

    function recordRequest(data, ms) {
        const host = data.hostname || 'unknown';
        if (!stats[host]) {
            stats[host] = { count: 0, totalMs: 0, lastMs: 0, lastSeen: null };
        }
        stats[host].count++;
        stats[host].totalMs += ms;
        stats[host].lastMs   = ms;
        stats[host].lastSeen = new Date().toLocaleTimeString('ar');
        total++;
        reqLast++;

        document.getElementById('total').textContent = total;
        renderNodes(host);
        addLog(
            new Date().toLocaleTimeString('ar'),
            host,
            `${ms}ms`,
            stats[host].count
        );
    }

    function renderNodes(activeHost) {
        const grid = document.getElementById('nodes-grid');
        const hosts = Object.keys(stats).sort();
        if (hosts.length === 0) return;

        grid.innerHTML = hosts.map(host => {
            const s   = stats[host];
            const avg = s.count ? Math.round(s.totalMs / s.count) : 0;
            const pct = total ? Math.round((s.count / total) * 100) : 0;
            const isActive = host === activeHost;
            return `
            <div class="node-card ${isActive ? 'active' : ''}">
                <div class="node-name">
                    🖥 ${host}
                    <span class="badge ${isActive ? 'badge-active' : 'badge-idle'}">
                        ${isActive ? 'نشط ◉' : 'خامل'}
                    </span>
                </div>
                <div class="stat"><span class="stat-label">عدد الطلبات</span>
                    <span class="stat-value">${s.count}</span></div>
                <div class="stat"><span class="stat-label">نسبة التوزيع</span>
                    <span class="stat-value">${pct}%</span></div>
                <div class="stat"><span class="stat-label">متوسط زمن الاستجابة</span>
                    <span class="stat-value">${avg}ms</span></div>
                <div class="stat"><span class="stat-label">آخر استجابة</span>
                    <span class="stat-value">${s.lastMs}ms</span></div>
                <div class="stat"><span class="stat-label">آخر طلب</span>
                    <span class="stat-value">${s.lastSeen}</span></div>
                <div style="background:#0f1117;border-radius:4px;height:6px;margin-top:0.8rem;">
                    <div style="background:#22d3ee;height:6px;border-radius:4px;width:${pct}%;transition:width 0.3s;"></div>
                </div>
            </div>`;
        }).join('');
    }

    function addLog(time, host, info, count) {
        const log  = document.getElementById('log');
        const entry = document.createElement('div');
        entry.className = 'log-entry';
        entry.innerHTML = `
            <span class="log-time">${time}</span>
            <span class="log-host">${host}</span>
            <span class="log-req">${info} — طلب #${count}</span>`;
        log.prepend(entry);
        // احتفظ بآخر 100 سجل فقط
        while (log.children.length > 100) log.removeChild(log.lastChild);
    }

    async function sendBurst() {
        const promises = Array.from({length: 20}, () => fetchNode());
        await Promise.all(promises);
    }

    function toggleAuto() {
        const btn = event.target;
        if (autoTimer) {
            clearInterval(autoTimer);
            autoTimer = null;
            btn.textContent = '▶ تلقائي';
        } else {
            autoTimer = setInterval(fetchNode, 300);
            btn.textContent = '⏹ إيقاف';
        }
    }

    function clearLog() {
        document.getElementById('log').innerHTML = '';
        Object.keys(stats).forEach(k => delete stats[k]);
        total = 0;
        reqLast = 0;
        document.getElementById('total').textContent = '0';
        document.getElementById('nodes-grid').innerHTML = '';
    }

    // حساب الطلبات في الثانية
    rpsTimer = setInterval(() => {
        document.getElementById('rps').textContent = reqLast;
        reqLast = 0;
    }, 1000);

    // أرسل طلباً واحداً عند الفتح
    fetchNode();
</script>
</body>
</html>
