<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Load Balancer Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: monospace;
            background: #0f1117;
            color: #e2e8f0;
            padding: 1.5rem;
        }

        h1 {
            color: #7dd3fc;
            font-size: 1.4rem;
            margin-bottom: 0.3rem;
        }

        .subtitle {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .auth-bar {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .auth-status {
            font-size: 0.82rem;
        }

        .auth-ok {
            color: #4ade80;
        }

        .auth-fail {
            color: #f87171;
        }

        .auth-pending {
            color: #fbbf24;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .node-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.2rem;
            transition: border-color 0.3s;
        }

        .node-card.active {
            border-color: #22d3ee;
            box-shadow: 0 0 16px rgba(34, 211, 238, 0.15);
        }

        .node-name {
            font-size: 0.95rem;
            font-weight: bold;
            color: #7dd3fc;
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat {
            display: flex;
            justify-content: space-between;
            margin: 0.35rem 0;
            font-size: 0.82rem;
        }

        .stat-label {
            color: #94a3b8;
        }

        .stat-value {
            color: #e2e8f0;
            font-weight: bold;
        }

        .badge {
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
        }

        .badge-active {
            background: #164e63;
            color: #22d3ee;
        }

        .badge-idle {
            background: #1e293b;
            color: #64748b;
            border: 1px solid #334155;
        }

        .bar-bg {
            background: #0f1117;
            border-radius: 4px;
            height: 5px;
            margin-top: 0.7rem;
        }

        .bar-fg {
            background: #22d3ee;
            height: 5px;
            border-radius: 4px;
            transition: width 0.4s;
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .counter {
            background: #1e293b;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-size: 0.82rem;
        }

        .counter span {
            color: #7dd3fc;
            font-weight: bold;
        }

        .algo-badge {
            background: #4c1d95;
            color: #c4b5fd;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .btn {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-family: monospace;
            font-size: 0.82rem;
            color: white;
        }

        .btn-blue {
            background: #1d4ed8;
        }

        .btn-blue:hover {
            background: #2563eb;
        }

        .btn-green {
            background: #15803d;
        }

        .btn-green:hover {
            background: #16a34a;
        }

        .btn-red {
            background: #dc2626;
        }

        .btn-red:hover {
            background: #ef4444;
        }

        .actions-grid {
            display: grid;
            grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .action-btn {
            border: none;
            padding: 0.7rem;
            border-radius: 8px;
            cursor: pointer;
            font-family: monospace;
            font-size: 0.8rem;
            color: white;
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .action-btn .method {
            font-size: 0.65rem;
            opacity: 0.7;
        }

        .action-btn .name {
            font-weight: bold;
        }

        .action-btn .type {
            font-size: 0.65rem;
            opacity: 0.6;
        }

        .log-area {
            background: #0d1117;
            border: 1px solid #21262d;
            border-radius: 8px;
            padding: 0.8rem;
            height: 220px;
            overflow-y: auto;
            font-size: 0.78rem;
        }

        .log-entry {
            padding: 3px 0;
            border-bottom: 1px solid #1e293b;
            display: grid;
            grid-template-columns:80px 140px 70px 1fr;
            gap: 0.5rem;
            align-items: center;
        }

        .log-time {
            color: #4ade80;
        }

        .log-host {
            color: #7dd3fc;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .log-ms {
            color: #fbbf24;
        }

        .log-action {
            color: #94a3b8;
        }

        .log-entry.error .log-action {
            color: #f87171;
        }

        .section-title {
            color: #64748b;
            font-size: 0.82rem;
            margin-bottom: 0.5rem;
        }

        .divider {
            color: #334155;
            font-size: 0.75rem;
            margin: 0.5rem 0 0.3rem;
        }
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

<p class="section-title">اختر نوع الطلب:</p>
<div class="actions-grid" id="actions-grid"></div>

<div class="grid" id="nodes-grid" style="margin-top:1rem"></div>

<p class="section-title">سجل الطلبات الأخيرة</p>
<div class="log-area" id="log"></div>

<script>
    const ACTIONS = [
        {name: 'Products API', method: 'GET', url: '/api/products', color: '#065f46', auth: false, real: true},
        {name: 'Categories API', method: 'GET', url: '/api/categories', color: '#1e3a5f', auth: false, real: true},

        {name: 'Inventory API', method: 'GET', url: '/api/inventory', color: '#7c2d12', auth: true, real: true},
        {name: 'Orders API', method: 'GET', url: '/api/orders', color: '#3b0764', auth: true, real: true},
        {name: 'My Profile', method: 'GET', url: '/api/me', color: '#164e63', auth: true, real: true},

    ];

    let selectedAction = ACTIONS[0];
    let stats = {};
    let total = 0, successCount = 0, errorCount = 0, reqLast = 0;
    let autoTimer = null;
    let token = localStorage.getItem('lb_token') || null;

    async function loginFirst(manual = false) {
        document.getElementById('auth-status').innerHTML =
            '<span class="auth-pending">⏳ جاري تسجيل الدخول...</span>';

        if (!manual && token) {

            const valid = await verifyToken();
            if (valid) {
                showAuthOk();
                return true;
            }

            token = null;
            localStorage.removeItem('lb_token');
        }

        try {
            const res = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    email: 'test@test.com',
                    password: 'password',
                }),
            });

            const data = await res.json();

            if (data.data?.token) {
                token = data.data.token;
                localStorage.setItem('lb_token', token);
                showAuthOk();
                return true;
            } else {
                showAuthFail(data.message || 'فشل تسجيل الدخول');
                return false;
            }
        } catch (e) {
            showAuthFail(e.message);
            return false;
        }
    }

    async function verifyToken() {
        try {
            const res = await fetch('/api/me', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                },
            });
            return res.ok;
        } catch {
            return false;
        }
    }

    function showAuthOk() {
        document.getElementById('auth-status').innerHTML =
            '<span class="auth-ok">✅ مسجّل دخول: test@test.com</span>';
        document.getElementById('token-preview').textContent =
            'Token: ' + token.substring(0, 20) + '...';
    }

    function showAuthFail(msg) {
        document.getElementById('auth-status').innerHTML =
            `<span class="auth-fail">❌ فشل تسجيل الدخول: ${msg}</span>`;
        document.getElementById('token-preview').textContent = '';
    }


    function buildActions() {
        const grid = document.getElementById('actions-grid');
        let html = '<div class="divider">── APIs حقيقية ──</div>';

        ACTIONS.forEach((a, i) => {
            if (!a.real && i > 0 && ACTIONS[i - 1].real) {
                html += '<div class="divider" style="grid-column:1/-1">── APIs تجريبية ──</div>';
            }
            const isSelected = selectedAction === a;
            html += `
        <button class="action-btn"
            style="background:${a.color}${isSelected ? '' : 'bb'};
                   border:2px solid ${isSelected ? '#fff' : 'transparent'}"
            onclick="selectAction(${i})">
            <span class="method">${a.method} ${a.auth ? '🔒' : '🌐'}</span>
            <span class="name">${a.name}</span>
            <span class="type">${a.real ? '✦ API حقيقي' : '◦ تجريبي'}</span>
        </button>`;
        });

        grid.innerHTML = html;
    }

    function selectAction(i) {
        selectedAction = ACTIONS[i];
        buildActions();
    }


    async function fetchRequest() {
        const action = selectedAction;


        if (action.auth && !token) {
            const ok = await loginFirst();
            if (!ok) {
                recordRequest('error', 0, 'يحتاج تسجيل دخول — فشل', true);
                return;
            }
        }

        const t0 = Date.now();
        try {
            const headers = {'Accept': 'application/json'};
            if (action.auth && token) {
                headers['Authorization'] = 'Bearer ' + token;
            }

            const res = await fetch(action.url, {headers});
            const ms = Date.now() - t0;
            const data = await res.json();

            // إذا انتهت صلاحية الـ token
            if (res.status === 401) {
                token = null;
                localStorage.removeItem('lb_token');
                showAuthFail('انتهت صلاحية الـ Token — أعد تسجيل الدخول');
                recordRequest('error', ms, 'Unauthenticated — token expired', true);
                return;
            }

            if (!res.ok) throw new Error(data.message || res.statusText);

            const host = data.node || data.hostname || 'unknown';
            recordRequest(host, ms, action.name, false);

        } catch (e) {
            const ms = Date.now() - t0;
            recordRequest('error', ms, e.message, true);
        }
    }

    function extractNode(res) {
        return null;
    }


    function recordRequest(host, ms, action, isError) {
        total++;
        reqLast++;
        if (isError) errorCount++; else successCount++;

        document.getElementById('total').textContent   = total;
        document.getElementById('success').textContent = successCount;
        document.getElementById('errors').textContent  = errorCount;

        // ← تجاهل أي host غير Node-1/2/3
        const validHosts = ['Node-1', 'Node-2', 'Node-3'];
        const cleanHost  = validHosts.includes(host) ? host : null;

        if (cleanHost) {
            if (!stats[cleanHost]) {
                stats[cleanHost] = {count: 0, totalMs: 0, lastMs: 0, lastSeen: null, _lastTime: 0};
            }
            stats[cleanHost].count++;
            stats[cleanHost].totalMs  += ms;
            stats[cleanHost].lastMs    = ms;
            stats[cleanHost].lastSeen  = new Date().toLocaleTimeString('ar');
            stats[cleanHost]._lastTime = Date.now();
        }

        renderNodes();
        addLog(
            new Date().toLocaleTimeString('ar'),
            cleanHost || host,
            ms,
            action,
            isError
        );
    }
    function renderNodes() {
        const grid = document.getElementById('nodes-grid');
        const hosts = Object.keys(stats).sort();
        if (!hosts.length) return;

        grid.innerHTML = hosts.map(host => {
            const s = stats[host];
            const avg = s.count ? Math.round(s.totalMs / s.count) : 0;
            const pct = successCount ? Math.round((s.count / successCount) * 100) : 0;
            const isActive = (Date.now() - s._lastTime) < 800;
            return `
        <div class="node-card ${isActive ? 'active' : ''}">
            <div class="node-name">
                🖥 ${host.substring(0, 14)}
                <span class="badge ${isActive ? 'badge-active' : 'badge-idle'}">
                    ${isActive ? 'نشط ◉' : 'خامل'}
                </span>
            </div>
            <div class="stat">
                <span class="stat-label">الطلبات</span>
                <span class="stat-value">${s.count}</span>
            </div>
            <div class="stat">
                <span class="stat-label">التوزيع</span>
                <span class="stat-value">${pct}%</span>
            </div>
            <div class="stat">
                <span class="stat-label">متوسط زمن</span>
                <span class="stat-value">${avg}ms</span>
            </div>
            <div class="stat">
                <span class="stat-label">آخر استجابة</span>
                <span class="stat-value">${s.lastMs}ms</span>
            </div>
            <div class="stat">
                <span class="stat-label">آخر طلب</span>
                <span class="stat-value">${s.lastSeen || '-'}</span>
            </div>
            <div class="bar-bg">
                <div class="bar-fg" style="width:${pct}%"></div>
            </div>
        </div>`;
        }).join('');
    }

    function addLog(time, host, ms, action, isError) {
        const log = document.getElementById('log');
        const entry = document.createElement('div');
        entry.className = 'log-entry' + (isError ? ' error' : '');
        entry.innerHTML = `
        <span class="log-time">${time}</span>
        <span class="log-host">${host.substring(0, 14)}</span>
        <span class="log-ms">${ms}ms</span>
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
            btn.className = 'btn btn-green';
        } else {
            autoTimer = setInterval(fetchRequest, 400);
            btn.textContent = '⏹ إيقاف';
            btn.className = 'btn btn-red';
        }
    }

    function clearAll() {
        stats = {};
        total = 0;
        successCount = 0;
        errorCount = 0;
        reqLast = 0;
        ['total', 'success', 'errors'].forEach(id =>
            document.getElementById(id).textContent = '0');
        document.getElementById('nodes-grid').innerHTML = '';
        document.getElementById('log').innerHTML = '';
    }

    setInterval(() => {
        document.getElementById('rps').textContent = reqLast;
        reqLast = 0;
    }, 1000);


    buildActions();
    loginFirst();
</script>
</body>
</html>
