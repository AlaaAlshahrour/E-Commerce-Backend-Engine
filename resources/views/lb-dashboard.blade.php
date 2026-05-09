<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Load Balancer Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: monospace; background: #0f1117; color: #e2e8f0; padding: 1.5rem; }
        h1 { color: #7dd3fc; font-size: 1.4rem; margin-bottom: 0.3rem; }
        .subtitle { color: #64748b; font-size: 0.85rem; margin-bottom: 1rem; }

        .auth-bar { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .auth-status { font-size: 0.82rem; }
        .auth-ok { color: #4ade80; }
        .auth-fail { color: #f87171; }
        .auth-pending { color: #fbbf24; }

        .controls { display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center; margin-bottom: 1rem; }
        .counter { background: #1e293b; border-radius: 8px; padding: 0.4rem 0.8rem; font-size: 0.82rem; }
        .counter span { color: #7dd3fc; font-weight: bold; }
        .algo-badge { background: #4c1d95; color: #c4b5fd; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; }

        .btn { border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: monospace; font-size: 0.82rem; color: white; }
        .btn-blue { background: #1d4ed8; } .btn-blue:hover { background: #2563eb; }
        .btn-green { background: #15803d; } .btn-green:hover { background: #16a34a; }
        .btn-red { background: #dc2626; } .btn-red:hover { background: #ef4444; }

        /* Groups */
        .groups-container { display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 1rem; }
        .group { background: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; }
        .group-header { padding: 0.5rem 1rem; font-size: 0.78rem; font-weight: bold; display: flex; align-items: center; gap: 0.5rem; }
        .group-body { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.4rem; padding: 0.5rem; }
        .action-btn { border: none; padding: 0.55rem 0.7rem; border-radius: 7px; cursor: pointer; font-family: monospace; font-size: 0.75rem; color: white; text-align: right; display: flex; flex-direction: column; gap: 2px; transition: opacity 0.2s, border 0.2s; }
        .action-btn:hover { opacity: 0.9; }
        .action-btn .method-badge { font-size: 0.6rem; font-weight: bold; padding: 1px 5px; border-radius: 4px; display: inline-block; margin-bottom: 2px; }
        .action-btn .ep-name { font-weight: bold; font-size: 0.78rem; }
        .action-btn .ep-url { font-size: 0.6rem; opacity: 0.6; }
        .method-GET { background: #15803d; color: white; }
        .method-POST { background: #1d4ed8; color: white; }
        .method-PUT { background: #b45309; color: white; }
        .method-PATCH { background: #6d28d9; color: white; }
        .method-DELETE { background: #dc2626; color: white; }

        /* Nodes */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .node-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.2rem; transition: border-color 0.3s; }
        .node-card.active { border-color: #22d3ee; box-shadow: 0 0 16px rgba(34,211,238,0.15); }
        .node-name { font-size: 0.95rem; font-weight: bold; color: #7dd3fc; margin-bottom: 0.8rem; display: flex; justify-content: space-between; align-items: center; }
        .stat { display: flex; justify-content: space-between; margin: 0.35rem 0; font-size: 0.82rem; }
        .stat-label { color: #94a3b8; }
        .stat-value { color: #e2e8f0; font-weight: bold; }
        .badge { padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; }
        .badge-active { background: #164e63; color: #22d3ee; }
        .badge-idle { background: #1e293b; color: #64748b; border: 1px solid #334155; }
        .bar-bg { background: #0f1117; border-radius: 4px; height: 5px; margin-top: 0.7rem; }
        .bar-fg { background: #22d3ee; height: 5px; border-radius: 4px; transition: width 0.4s; }

        .log-area { background: #0d1117; border: 1px solid #21262d; border-radius: 8px; padding: 0.8rem; height: 220px; overflow-y: auto; font-size: 0.78rem; }
        .log-entry { padding: 3px 0; border-bottom: 1px solid #1e293b; display: grid; grid-template-columns: 75px 80px 75px 80px 1fr; gap: 0.4rem; align-items: center; }
        .log-time { color: #4ade80; }
        .log-host { color: #7dd3fc; }
        .log-ms { color: #fbbf24; }
        .log-method { font-size: 0.7rem; }
        .log-action { color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .log-entry.error .log-action { color: #f87171; }
        .section-title { color: #64748b; font-size: 0.82rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body>

<h1>⚖️ Load Balancer Dashboard</h1>
<p class="subtitle">Least Connections — NGINX + Docker — Real-time monitoring</p>

<div class="auth-bar">
    <span class="auth-status" id="auth-status">
        <span class="auth-pending">⏳ جاري تسجيل الدخول تلقائياً...</span>
    </span>
    <button class="btn btn-blue" onclick="loginFirst(true)" style="padding:0.3rem 0.8rem;font-size:0.78rem">
        🔄 إعادة تسجيل الدخول
    </button>
    <span style="font-size:0.75rem;color:#475569" id="token-preview"></span>
</div>

<div class="controls">
    <span class="algo-badge">🔄 Least Connections</span>
    <div class="counter">إجمالي: <span id="total">0</span></div>
    <div class="counter">req/s: <span id="rps">0</span></div>
    <div class="counter">نجاح: <span id="success" style="color:#4ade80">0</span></div>
    <div class="counter">خطأ: <span id="errors" style="color:#f87171">0</span></div>
    <button class="btn btn-blue" onclick="sendBurst()">⚡ 20 طلب متزامن</button>
    <button class="btn btn-green" onclick="toggleAuto()" id="auto-btn">▶ تلقائي</button>
    <button class="btn btn-red" onclick="clearAll()">🗑 مسح</button>
</div>

<p class="section-title">اختر الـ Endpoint:</p>
<div class="groups-container" id="groups-container"></div>

<div class="grid" id="nodes-grid" style="margin-top:1rem"></div>

<p class="section-title">سجل الطلبات الأخيرة</p>
<div class="log-area" id="log"></div>

<script>
    const GROUPS = [
        {
            name: '🔐 Auth',
            color: '#1e3050',
            headerColor: '#3b82f6',
            endpoints: [
                { name: 'Register',  method: 'POST', url: '/api/register',  auth: false, body: {name:'Test',email:'t@t.com',password:'password',password_confirmation:'password'} },
                { name: 'Login',     method: 'POST', url: '/api/login',     auth: false, body: {email:'test@test.com',password:'password'} },
                { name: 'Me',        method: 'GET',  url: '/api/me',        auth: true  },
                { name: 'Logout',    method: 'POST', url: '/api/logout',    auth: true  },
            ]
        },
        {
            name: '📦 Products',
            color: '#0f2d1e',
            headerColor: '#16a34a',
            endpoints: [
                { name: 'All Products',    method: 'GET',  url: '/api/products',   auth: false },
                { name: 'Show Product',    method: 'GET',  url: '/api/products/1', auth: false },
                { name: 'Create Product',  method: 'POST', url: '/api/products',   auth: true, body: {name:'Test',price:100,category_id:1} },
            ]
        },
        {
            name: '🗂 Categories',
            color: '#1a1535',
            headerColor: '#8b5cf6',
            endpoints: [
                { name: 'All Categories',  method: 'GET',  url: '/api/categories',   auth: false },
                { name: 'Show Category',   method: 'GET',  url: '/api/categories/1', auth: false },
                { name: 'Create Category', method: 'POST', url: '/api/categories',   auth: true, body: {name:'New Cat'} },
            ]
        },
        {
            name: '🛒 Cart',
            color: '#1f1506',
            headerColor: '#d97706',
            endpoints: [
                { name: 'Get Cart',     method: 'GET',    url: '/api/cart',           auth: true },
                { name: 'Add to Cart',  method: 'POST',   url: '/api/cart/add/1',     auth: true },
                { name: 'Update Cart',  method: 'PATCH',  url: '/api/cart/update/1',  auth: true, body: {quantity:2} },
                { name: 'Remove Item',  method: 'DELETE', url: '/api/cart/remove',    auth: true },
                { name: 'Clear Cart',   method: 'DELETE', url: '/api/cart/clear',     auth: true },
            ]
        },
        {
            name: '🏭 Inventory',
            color: '#1f0d0d',
            headerColor: '#ef4444',
            endpoints: [
                { name: 'All Inventory',    method: 'GET', url: '/api/inventory',   auth: true },
                { name: 'Show Inventory',   method: 'GET', url: '/api/inventory/1', auth: true },
                { name: 'Update Inventory', method: 'PUT', url: '/api/inventory/1', auth: true, body: {quantity:50} },
            ]
        },
        {
            name: '📋 Orders',
            color: '#12102a',
            headerColor: '#6366f1',
            endpoints: [
                { name: 'All Orders',    method: 'GET',  url: '/api/orders',          auth: true },
                { name: 'Show Order',    method: 'GET',  url: '/api/orders/1',        auth: true },
                { name: 'Checkout',      method: 'POST', url: '/api/orders/checkout', auth: true, body: {} },
            ]
        },
        {
            name: '💰 Wallet',
            color: '#0d1f1a',
            headerColor: '#14b8a6',
            endpoints: [
                { name: 'Show Wallet', method: 'GET',  url: '/api/wallet',        auth: true },
                { name: 'Top Up',      method: 'POST', url: '/api/wallet/topup',  auth: true, body: {amount:100} },
            ]
        },
    ];

    const METHOD_COLORS = {
        GET:    '#15803d',
        POST:   '#1d4ed8',
        PUT:    '#b45309',
        PATCH:  '#6d28d9',
        DELETE: '#dc2626',
    };

    let selectedEndpoint = GROUPS[0].endpoints[2]; // Me by default
    let stats = {};
    let total = 0, successCount = 0, errorCount = 0, reqLast = 0;
    let autoTimer = null;
    let token = localStorage.getItem('lb_token') || null;

    // ── Build UI ──────────────────────────────────────────────
    function buildGroups() {
        const container = document.getElementById('groups-container');
        container.innerHTML = GROUPS.map((group, gi) => `
        <div class="group">
            <div class="group-header" style="background:${group.headerColor}22;border-bottom:1px solid ${group.headerColor}44">
                <span style="color:${group.headerColor}">${group.name}</span>
            </div>
            <div class="group-body" style="background:${group.color}">
                ${group.endpoints.map((ep, ei) => {
            const isSelected = selectedEndpoint === ep;
            const mc = METHOD_COLORS[ep.method] || '#374151';
            return `
                    <button class="action-btn"
                        style="background:${mc}${isSelected ? 'ff' : '55'};
                               border:2px solid ${isSelected ? '#fff' : 'transparent'}"
                        onclick="selectEndpoint(${gi},${ei})">
                        <span class="method-badge method-${ep.method}">${ep.method}</span>
                        <span class="ep-name">${ep.name}</span>
                        <span class="ep-url">${ep.url}</span>
                    </button>`;
        }).join('')}
            </div>
        </div>
    `).join('');
    }

    function selectEndpoint(gi, ei) {
        selectedEndpoint = GROUPS[gi].endpoints[ei];
        buildGroups();
    }

    // ── Auth ──────────────────────────────────────────────────
    async function loginFirst(manual = false) {
        document.getElementById('auth-status').innerHTML =
            '<span class="auth-pending">⏳ جاري تسجيل الدخول...</span>';

        if (!manual && token) {
            if (await verifyToken()) { showAuthOk(); return true; }
            token = null;
            localStorage.removeItem('lb_token');
        }

        try {
            const res  = await fetch('/api/login', {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
                body: JSON.stringify({ email:'test@test.com', password:'password' }),
            });
            const data = await res.json();
            if (data.data?.token) {
                token = data.data.token;
                localStorage.setItem('lb_token', token);
                showAuthOk();
                return true;
            }
            showAuthFail(data.message || 'فشل');
            return false;
        } catch(e) { showAuthFail(e.message); return false; }
    }

    async function verifyToken() {
        try {
            const res = await fetch('/api/me', {
                headers: { 'Accept':'application/json', 'Authorization':'Bearer '+token }
            });
            return res.ok;
        } catch { return false; }
    }

    function showAuthOk() {
        document.getElementById('auth-status').innerHTML =
            '<span class="auth-ok">✅ مسجّل دخول: test@test.com</span>';
        document.getElementById('token-preview').textContent =
            'Token: ' + token.substring(0,20) + '...';
    }

    function showAuthFail(msg) {
        document.getElementById('auth-status').innerHTML =
            `<span class="auth-fail">❌ فشل: ${msg}</span>`;
        document.getElementById('token-preview').textContent = '';
    }

    // ── Fetch ─────────────────────────────────────────────────
    async function fetchRequest() {
        const ep = selectedEndpoint;

        if (ep.auth && !token) {
            const ok = await loginFirst();
            if (!ok) { recordRequest('error', 0, ep.name, true); return; }
        }

        const t0 = Date.now();
        try {
            const headers = { 'Accept':'application/json', 'Content-Type':'application/json' };
            if (ep.auth && token) headers['Authorization'] = 'Bearer ' + token;

            const options = { method: ep.method, headers };
            if (ep.body && ['POST','PUT','PATCH'].includes(ep.method)) {
                options.body = JSON.stringify(ep.body);
            }

            const res  = await fetch(ep.url, options);
            const ms   = Date.now() - t0;
            const data = await res.json();

            if (res.status === 401) {
                token = null;
                localStorage.removeItem('lb_token');
                showAuthFail('Token منتهي');
                recordRequest('error', ms, ep.name, true);
                return;
            }

            const host = data.node || data.hostname || 'unknown';
            recordRequest(host, ms, ep.name, !res.ok, ep.method);

        } catch(e) {
            recordRequest('error', Date.now()-t0, e.message, true, ep.method);
        }
    }

    // ── Record ────────────────────────────────────────────────
    function recordRequest(host, ms, action, isError, method = 'GET') {
        total++;
        reqLast++;

        const validHosts = ['Node-1', 'Node-2', 'Node-3'];
        const cleanHost  = validHosts.includes(host) ? host : null;

        if (isError) {
            errorCount++;
        } else if (cleanHost) {
            successCount++;
        }

        document.getElementById('total').textContent   = total;
        document.getElementById('success').textContent = successCount;
        document.getElementById('errors').textContent  = errorCount;

        if (cleanHost) {
            if (!stats[cleanHost]) {
                stats[cleanHost] = { count:0, totalMs:0, lastMs:0, lastSeen:null, _lastTime:0 };
            }
            stats[cleanHost].count++;
            stats[cleanHost].totalMs  += ms;
            stats[cleanHost].lastMs    = ms;
            stats[cleanHost].lastSeen  = new Date().toLocaleTimeString('ar');
            stats[cleanHost]._lastTime = Date.now();
        }

        renderNodes();
        addLog(new Date().toLocaleTimeString('ar'), cleanHost || host, ms, method, action, isError);
    }

    function renderNodes() {
        const grid  = document.getElementById('nodes-grid');
        const hosts = Object.keys(stats).sort();
        if (!hosts.length) return;

        grid.innerHTML = hosts.map(host => {
            const s        = stats[host];
            const avg      = s.count ? Math.round(s.totalMs / s.count) : 0;
            const pct      = successCount ? Math.round((s.count / successCount) * 100) : 0;
            const isActive = (Date.now() - s._lastTime) < 800;
            return `
        <div class="node-card ${isActive ? 'active' : ''}">
            <div class="node-name">
                🖥 ${host}
                <span class="badge ${isActive ? 'badge-active' : 'badge-idle'}">
                    ${isActive ? 'نشط ◉' : 'خامل'}
                </span>
            </div>
            <div class="stat"><span class="stat-label">الطلبات</span><span class="stat-value">${s.count}</span></div>
            <div class="stat"><span class="stat-label">التوزيع</span><span class="stat-value">${pct}%</span></div>
            <div class="stat"><span class="stat-label">متوسط زمن</span><span class="stat-value">${avg}ms</span></div>
            <div class="stat"><span class="stat-label">آخر استجابة</span><span class="stat-value">${s.lastMs}ms</span></div>
            <div class="stat"><span class="stat-label">آخر طلب</span><span class="stat-value">${s.lastSeen || '-'}</span></div>
            <div class="bar-bg"><div class="bar-fg" style="width:${pct}%"></div></div>
        </div>`;
        }).join('');
    }

    function addLog(time, host, ms, method, action, isError) {
        const log   = document.getElementById('log');
        const entry = document.createElement('div');
        const mc    = METHOD_COLORS[method] || '#374151';
        entry.className = 'log-entry' + (isError ? ' error' : '');
        entry.innerHTML = `
        <span class="log-time">${time}</span>
        <span class="log-host">${host}</span>
        <span class="log-ms">${ms}ms</span>
        <span class="log-method" style="background:${mc};padding:1px 5px;border-radius:3px;font-size:0.65rem">${method}</span>
        <span class="log-action">${action}</span>`;
        log.prepend(entry);
        while (log.children.length > 150) log.removeChild(log.lastChild);
    }

    async function sendBurst() {
        await Promise.all(Array.from({length: 20}, () => fetchRequest()));
    }

    function toggleAuto() {
        const btn = document.getElementById('auto-btn');
        if (autoTimer) {
            clearInterval(autoTimer);
            autoTimer = null;
            btn.textContent = '▶ تلقائي';
            btn.className   = 'btn btn-green';
        } else {
            autoTimer       = setInterval(fetchRequest, 400);
            btn.textContent = '⏹ إيقاف';
            btn.className   = 'btn btn-red';
        }
    }

    function clearAll() {
        stats = {}; total = 0; successCount = 0; errorCount = 0; reqLast = 0;
        ['total','success','errors'].forEach(id =>
            document.getElementById(id).textContent = '0');
        document.getElementById('nodes-grid').innerHTML = '';
        document.getElementById('log').innerHTML = '';
    }

    setInterval(() => {
        document.getElementById('rps').textContent = reqLast;
        reqLast = 0;
    }, 1000);

    buildGroups();
    loginFirst();
</script>
</body>
</html>
