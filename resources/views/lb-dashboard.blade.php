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

        /* Tabs */
        .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid #334155; padding-bottom: 0.5rem; }
        .tab { padding: 0.5rem 1.2rem; border-radius: 8px 8px 0 0; cursor: pointer; font-size: 0.85rem; border: 1px solid transparent; }
        .tab.active { background: #1e293b; border-color: #334155; border-bottom-color: #0f1117; color: #7dd3fc; }
        .tab:not(.active) { color: #64748b; }
        .tab:not(.active):hover { color: #94a3b8; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

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
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-blue { background: #1d4ed8; } .btn-blue:hover:not(:disabled) { background: #2563eb; }
        .btn-green { background: #15803d; } .btn-green:hover:not(:disabled) { background: #16a34a; }
        .btn-red { background: #dc2626; } .btn-red:hover:not(:disabled) { background: #ef4444; }
        .btn-purple { background: #7c3aed; } .btn-purple:hover:not(:disabled) { background: #8b5cf6; }

        .groups-container { display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 1rem; }
        .group { background: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; }
        .group-header { padding: 0.5rem 1rem; font-size: 0.78rem; font-weight: bold; }
        .group-body { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.4rem; padding: 0.5rem; }
        .action-btn { border: none; padding: 0.55rem 0.7rem; border-radius: 7px; cursor: pointer; font-family: monospace; font-size: 0.75rem; color: white; text-align: right; display: flex; flex-direction: column; gap: 2px; }
        .action-btn .method-badge { font-size: 0.6rem; font-weight: bold; padding: 1px 5px; border-radius: 4px; display: inline-block; margin-bottom: 2px; }
        .action-btn .ep-name { font-weight: bold; font-size: 0.78rem; }
        .action-btn .ep-url { font-size: 0.6rem; opacity: 0.6; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .node-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.2rem; transition: all 0.3s; }
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

        .log-area { background: #0d1117; border: 1px solid #21262d; border-radius: 8px; padding: 0.8rem; height: 200px; overflow-y: auto; font-size: 0.78rem; }
        .log-entry { padding: 3px 0; border-bottom: 1px solid #1e293b; display: grid; grid-template-columns: 75px 80px 75px 80px 1fr; gap: 0.4rem; align-items: center; }
        .log-time { color: #4ade80; }
        .log-host { color: #7dd3fc; }
        .log-ms { color: #fbbf24; }
        .log-action { color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .log-entry.error .log-action { color: #f87171; }
        .section-title { color: #64748b; font-size: 0.82rem; margin-bottom: 0.5rem; }

        /* ── Stress Test Tab ── */
        .stress-config { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 1.2rem; margin-bottom: 1rem; }
        .stress-config h3 { color: #7dd3fc; font-size: 0.9rem; margin-bottom: 1rem; }
        .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.8rem; margin-bottom: 1rem; }
        .config-item label { display: block; color: #94a3b8; font-size: 0.75rem; margin-bottom: 0.3rem; }
        .config-item input, .config-item select { width: 100%; background: #0f1117; border: 1px solid #334155; color: #e2e8f0; padding: 0.4rem 0.6rem; border-radius: 6px; font-family: monospace; font-size: 0.82rem; }
        .config-item input:focus, .config-item select:focus { outline: none; border-color: #7dd3fc; }

        .stress-progress { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 1.2rem; margin-bottom: 1rem; display: none; }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; }
        .progress-title { color: #7dd3fc; font-size: 0.9rem; }
        .progress-bar-bg { background: #0f1117; border-radius: 4px; height: 8px; margin-bottom: 1rem; }
        .progress-bar-fg { background: linear-gradient(90deg, #22d3ee, #7dd3fc); height: 8px; border-radius: 4px; transition: width 0.3s; }
        .progress-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.6rem; }
        .progress-stat { background: #0f1117; border-radius: 8px; padding: 0.6rem; text-align: center; }
        .progress-stat .ps-value { font-size: 1.2rem; font-weight: bold; color: #7dd3fc; }
        .progress-stat .ps-label { font-size: 0.7rem; color: #64748b; }

        .stress-results { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 1.2rem; margin-bottom: 1rem; display: none; }
        .results-header { color: #4ade80; font-size: 0.9rem; margin-bottom: 1rem; }
        .results-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.8rem; margin-bottom: 1rem; }
        .result-node { background: #0f1117; border-radius: 8px; padding: 1rem; border-left: 3px solid #22d3ee; }
        .result-node h4 { color: #7dd3fc; margin-bottom: 0.6rem; font-size: 0.85rem; }
        .result-stat { display: flex; justify-content: space-between; font-size: 0.78rem; margin: 0.25rem 0; }
        .result-stat .rl { color: #94a3b8; }
        .result-stat .rv { color: #e2e8f0; font-weight: bold; }
        .result-bar { background: #1e293b; border-radius: 4px; height: 4px; margin-top: 0.5rem; }
        .result-bar-fill { height: 4px; border-radius: 4px; background: #22d3ee; }
        .summary-box { background: #0f1117; border-radius: 8px; padding: 1rem; }
        .summary-box h4 { color: #fbbf24; margin-bottom: 0.8rem; font-size: 0.85rem; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.6rem; }
        .summary-item { text-align: center; }
        .summary-item .sv { font-size: 1.4rem; font-weight: bold; color: #7dd3fc; }
        .summary-item .sl { font-size: 0.7rem; color: #64748b; }

        .stress-log { background: #0d1117; border: 1px solid #21262d; border-radius: 8px; padding: 0.8rem; height: 160px; overflow-y: auto; font-size: 0.75rem; }
        .slog-entry { padding: 2px 0; border-bottom: 1px solid #1e293b; display: grid; grid-template-columns: 70px 70px 70px 70px 1fr; gap: 0.4rem; }
        .slog-ok { color: #4ade80; }
        .slog-err { color: #f87171; }
    </style>
</head>
<body>

<h1>⚖️ Load Balancer Dashboard</h1>
<p class="subtitle">Least Connections — NGINX + Docker — Real-time monitoring</p>

<div class="auth-bar">
    <span class="auth-status" id="auth-status">
        <span class="auth-pending">⏳ جاري تسجيل الدخول...</span>
    </span>
    <button class="btn btn-blue" onclick="loginFirst(true)" style="padding:0.3rem 0.8rem;font-size:0.78rem">🔄 إعادة تسجيل الدخول</button>
    <span style="font-size:0.75rem;color:#475569" id="token-preview"></span>
</div>

<!-- Tabs -->
<div class="tabs">
    <div class="tab active" onclick="switchTab('monitor')">📊 مراقبة مباشرة</div>
    <div class="tab" onclick="switchTab('stress')">🔥 Stress Test</div>
</div>

<!-- ══ Tab 1: Monitor ══ -->
<div class="tab-content active" id="tab-monitor">
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

    <p class="section-title">سجل الطلبات</p>
    <div class="log-area" id="log"></div>
</div>

<!-- ══ Tab 2: Stress Test ══ -->
<div class="tab-content" id="tab-stress">

    <!-- إعدادات -->
    <div class="stress-config">
        <h3>⚙️ إعدادات الاختبار</h3>
        <div class="config-grid">
            <div class="config-item">
                <label>عدد المستخدمين المتزامنين</label>
                <input type="number" id="st-users" value="100" min="1" max="500">
            </div>
            <div class="config-item">
                <label>مدة الاختبار (ثانية)</label>
                <input type="number" id="st-duration" value="30" min="5" max="120">
            </div>
            <div class="config-item">
                <label>نوع الطلبات</label>
                <select id="st-mode">
                    <option value="random">عشوائي — كل الـ APIs</option>
                    <option value="light">خفيف فقط (Products, Categories)</option>
                    <option value="heavy">ثقيل فقط (Orders, Inventory)</option>
                    <option value="mixed">مختلط (80% خفيف، 20% ثقيل)</option>
                </select>
            </div>
            <div class="config-item">
                <label>الفاصل بين الطلبات (ms)</label>
                <input type="number" id="st-interval" value="100" min="50" max="2000">
            </div>
        </div>
        <div style="display:flex;gap:0.5rem">
            <button class="btn btn-purple" id="st-start-btn" onclick="startStressTest()">🚀 ابدأ الاختبار</button>
            <button class="btn btn-red" id="st-stop-btn" onclick="stopStressTest()" disabled>⏹ إيقاف</button>
        </div>
    </div>

    <!-- تقدم الاختبار -->
    <div class="stress-progress" id="stress-progress">
        <div class="progress-header">
            <span class="progress-title" id="st-status-text">جاري الاختبار...</span>
            <span style="color:#64748b;font-size:0.78rem" id="st-time-left"></span>
        </div>
        <div class="progress-bar-bg">
            <div class="progress-bar-fg" id="st-progress-bar" style="width:0%"></div>
        </div>
        <div class="progress-stats">
            <div class="progress-stat">
                <div class="ps-value" id="st-live-total">0</div>
                <div class="ps-label">إجمالي الطلبات</div>
            </div>
            <div class="progress-stat">
                <div class="ps-value" id="st-live-rps" style="color:#4ade80">0</div>
                <div class="ps-label">طلب/ثانية</div>
            </div>
            <div class="progress-stat">
                <div class="ps-value" id="st-live-ok" style="color:#4ade80">0</div>
                <div class="ps-label">ناجح</div>
            </div>
            <div class="progress-stat">
                <div class="ps-value" id="st-live-err" style="color:#f87171">0</div>
                <div class="ps-label">فاشل</div>
            </div>
            <div class="progress-stat">
                <div class="ps-value" id="st-live-avg">0ms</div>
                <div class="ps-label">متوسط زمن</div>
            </div>
        </div>

        <!-- nodes live -->
        <div style="margin-top:1rem">
            <p class="section-title">توزيع حي على العقد</p>
            <div class="grid" id="st-nodes-live"></div>
        </div>
    </div>

    <!-- نتائج نهائية -->
    <div class="stress-results" id="stress-results">
        <div class="results-header" id="results-header">✅ اكتمل الاختبار</div>

        <div class="results-grid" id="results-nodes"></div>

        <div class="summary-box" style="margin-top:1rem">
            <h4>📊 ملخص الاختبار</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="sv" id="r-total">0</div>
                    <div class="sl">إجمالي الطلبات</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-success" style="color:#4ade80">0</div>
                    <div class="sl">ناجح</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-errors" style="color:#f87171">0</div>
                    <div class="sl">فاشل</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-rps">0</div>
                    <div class="sl">طلب/ثانية</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-avg">0ms</div>
                    <div class="sl">متوسط الاستجابة</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-max">0ms</div>
                    <div class="sl">أقصى استجابة</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-users">0</div>
                    <div class="sl">مستخدم متزامن</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-duration">0s</div>
                    <div class="sl">مدة الاختبار</div>
                </div>
            </div>
        </div>
    </div>

    <!-- سجل stress -->
    <p class="section-title" style="margin-top:1rem">سجل الاختبار</p>
    <div class="stress-log" id="stress-log"></div>
</div>

<script>
    // ══════════════════════════════════════════════
    // البيانات
    // ══════════════════════════════════════════════
    const VALID_NODES = ['Node-1', 'Node-2', 'Node-3'];

    const LIGHT_ENDPOINTS = [
        { name: 'All Products',   method: 'GET', url: '/api/products',     auth: false },
        { name: 'Show Product',   method: 'GET', url: '/api/products/1',   auth: false },
        { name: 'All Categories', method: 'GET', url: '/api/categories',   auth: false },
        { name: 'Show Category',  method: 'GET', url: '/api/categories/1', auth: false },
    ];

    const HEAVY_ENDPOINTS = [
        { name: 'All Inventory', method: 'GET', url: '/api/inventory',   auth: true },
        { name: 'All Orders',    method: 'GET', url: '/api/orders',      auth: true },
        { name: 'My Profile',    method: 'GET', url: '/api/me',          auth: true },
        { name: 'Show Wallet',   method: 'GET', url: '/api/wallet',      auth: true },
        { name: 'Get Cart',      method: 'GET', url: '/api/cart',        auth: true },
    ];

    const ALL_ENDPOINTS = [...LIGHT_ENDPOINTS, ...HEAVY_ENDPOINTS];

    const GROUPS = [
        { name: '🔐 Auth', color: '#1e3050', headerColor: '#3b82f6', endpoints: [
                { name: 'Register', method: 'POST', url: '/api/register', auth: false, body: {name:'Test',email:'t@t.com',password:'password',password_confirmation:'password'} },
                { name: 'Login',    method: 'POST', url: '/api/login',    auth: false, body: {email:'test@test.com',password:'password'} },
                { name: 'Me',       method: 'GET',  url: '/api/me',       auth: true },
                { name: 'Logout',   method: 'POST', url: '/api/logout',   auth: true },
            ]},
        { name: '📦 Products', color: '#0f2d1e', headerColor: '#16a34a', endpoints: [
                { name: 'All Products',   method: 'GET',  url: '/api/products',   auth: false },
                { name: 'Show Product',   method: 'GET',  url: '/api/products/1', auth: false },
                { name: 'Create Product', method: 'POST', url: '/api/products',   auth: true, body: {name:'Test',price:100,category_id:1} },
            ]},
        { name: '🗂 Categories', color: '#1a1535', headerColor: '#8b5cf6', endpoints: [
                { name: 'All Categories',  method: 'GET',  url: '/api/categories',   auth: false },
                { name: 'Show Category',   method: 'GET',  url: '/api/categories/1', auth: false },
                { name: 'Create Category', method: 'POST', url: '/api/categories',   auth: true, body: {name:'New Cat'} },
            ]},
        { name: '🛒 Cart', color: '#1f1506', headerColor: '#d97706', endpoints: [
                { name: 'Get Cart',    method: 'GET',    url: '/api/cart',          auth: true },
                { name: 'Add to Cart', method: 'POST',   url: '/api/cart/add/1',    auth: true },
                { name: 'Update Cart', method: 'PATCH',  url: '/api/cart/update/1', auth: true, body: {quantity:2} },
                { name: 'Clear Cart',  method: 'DELETE', url: '/api/cart/clear',    auth: true },
            ]},
        { name: '🏭 Inventory', color: '#1f0d0d', headerColor: '#ef4444', endpoints: [
                { name: 'All Inventory',    method: 'GET', url: '/api/inventory',   auth: true },
                { name: 'Show Inventory',   method: 'GET', url: '/api/inventory/1', auth: true },
                { name: 'Update Inventory', method: 'PUT', url: '/api/inventory/1', auth: true, body: {quantity:50} },
            ]},
        { name: '📋 Orders', color: '#12102a', headerColor: '#6366f1', endpoints: [
                { name: 'All Orders', method: 'GET',  url: '/api/orders',          auth: true },
                { name: 'Show Order', method: 'GET',  url: '/api/orders/1',        auth: true },
                { name: 'Checkout',   method: 'POST', url: '/api/orders/checkout', auth: true, body: {} },
            ]},
        { name: '💰 Wallet', color: '#0d1f1a', headerColor: '#14b8a6', endpoints: [
                { name: 'Show Wallet', method: 'GET',  url: '/api/wallet',       auth: true },
                { name: 'Top Up',      method: 'POST', url: '/api/wallet/topup', auth: true, body: {amount:100} },
            ]},
    ];

    const METHOD_COLORS = { GET:'#15803d', POST:'#1d4ed8', PUT:'#b45309', PATCH:'#6d28d9', DELETE:'#dc2626' };

    // ══════════════════════════════════════════════
    // State
    // ══════════════════════════════════════════════
    let selectedEndpoint = GROUPS[0].endpoints[2];
    let monitorStats = {};
    let total = 0, successCount = 0, errorCount = 0, reqLast = 0;
    let autoTimer = null;
    let token = localStorage.getItem('lb_token') || null;

    // Stress state
    let stressRunning   = false;
    let stressTimer     = null;
    let stressNodes     = {};
    let stTotal         = 0, stOk = 0, stErr = 0, stTotalMs = 0, stMaxMs = 0, stRpsCount = 0;
    let stStartTime     = 0;
    let stRpsInterval   = null;

    // ══════════════════════════════════════════════
    // Tabs
    // ══════════════════════════════════════════════
    function switchTab(name) {
        document.querySelectorAll('.tab').forEach((t,i) => {
            t.classList.toggle('active', (name==='monitor'&&i===0)||(name==='stress'&&i===1));
        });
        document.getElementById('tab-monitor').classList.toggle('active', name==='monitor');
        document.getElementById('tab-stress').classList.toggle('active',  name==='stress');
    }

    // ══════════════════════════════════════════════
    // Auth
    // ══════════════════════════════════════════════
    async function loginFirst(manual = false) {
        document.getElementById('auth-status').innerHTML =
            '<span class="auth-pending">⏳ جاري تسجيل الدخول...</span>';
        if (!manual && token) {
            if (await verifyToken()) { showAuthOk(); return true; }
            token = null; localStorage.removeItem('lb_token');
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
                showAuthOk(); return true;
            }
            showAuthFail(data.message || 'فشل'); return false;
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
        document.getElementById('auth-status').innerHTML = '<span class="auth-ok">✅ مسجّل دخول: test@test.com</span>';
        document.getElementById('token-preview').textContent = 'Token: ' + token.substring(0,20) + '...';
    }
    function showAuthFail(msg) {
        document.getElementById('auth-status').innerHTML = `<span class="auth-fail">❌ فشل: ${msg}</span>`;
        document.getElementById('token-preview').textContent = '';
    }

    // ══════════════════════════════════════════════
    // Monitor Tab
    // ══════════════════════════════════════════════
    function buildGroups() {
        const c = document.getElementById('groups-container');
        c.innerHTML = GROUPS.map((g, gi) => `
        <div class="group">
            <div class="group-header" style="background:${g.headerColor}22;border-bottom:1px solid ${g.headerColor}44">
                <span style="color:${g.headerColor}">${g.name}</span>
            </div>
            <div class="group-body" style="background:${g.color}">
                ${g.endpoints.map((ep,ei) => {
            const sel = selectedEndpoint === ep;
            const mc  = METHOD_COLORS[ep.method]||'#374151';
            return `<button class="action-btn"
                        style="background:${mc}${sel?'ff':'55'};border:2px solid ${sel?'#fff':'transparent'}"
                        onclick="selectEndpoint(${gi},${ei})">
                        <span class="method-badge" style="background:${mc}">${ep.method}</span>
                        <span class="ep-name">${ep.name}</span>
                        <span class="ep-url">${ep.url}</span>
                    </button>`;
        }).join('')}
            </div>
        </div>`).join('');
    }

    function selectEndpoint(gi, ei) { selectedEndpoint = GROUPS[gi].endpoints[ei]; buildGroups(); }

    async function fetchRequest() {
        const ep = selectedEndpoint;
        if (ep.auth && !token) { const ok = await loginFirst(); if (!ok) { recordMonitor('error',0,ep.name,true,ep.method); return; } }
        const t0 = Date.now();
        try {
            const headers = { 'Accept':'application/json','Content-Type':'application/json' };
            if (ep.auth && token) headers['Authorization'] = 'Bearer '+token;
            const opt = { method:ep.method, headers };
            if (ep.body && ['POST','PUT','PATCH'].includes(ep.method)) opt.body = JSON.stringify(ep.body);
            const res  = await fetch(ep.url, opt);
            const ms   = Date.now()-t0;
            const data = await res.json();
            if (res.status===401) { token=null; localStorage.removeItem('lb_token'); showAuthFail('Token منتهي'); recordMonitor('error',ms,ep.name,true,ep.method); return; }
            const host = data.node || data.hostname || 'unknown';
            recordMonitor(host, ms, ep.name, !res.ok, ep.method);
        } catch(e) { recordMonitor('error', Date.now()-t0, e.message, true, ep.method); }
    }

    function recordMonitor(host, ms, action, isError, method='GET') {
        total++; reqLast++;
        const clean = VALID_NODES.includes(host) ? host : null;
        if (isError) errorCount++; else if (clean) successCount++;
        document.getElementById('total').textContent   = total;
        document.getElementById('success').textContent = successCount;
        document.getElementById('errors').textContent  = errorCount;
        if (clean) {
            if (!monitorStats[clean]) monitorStats[clean] = {count:0,totalMs:0,lastMs:0,lastSeen:null,_lastTime:0};
            const s = monitorStats[clean];
            s.count++; s.totalMs+=ms; s.lastMs=ms;
            s.lastSeen=new Date().toLocaleTimeString('ar'); s._lastTime=Date.now();
        }
        renderMonitorNodes();
        addMonitorLog(new Date().toLocaleTimeString('ar'), clean||host, ms, method, action, isError);
    }

    function renderMonitorNodes() {
        const grid = document.getElementById('nodes-grid');
        const hosts = Object.keys(monitorStats).sort();
        if (!hosts.length) return;
        grid.innerHTML = hosts.map(host => {
            const s = monitorStats[host];
            const avg = s.count ? Math.round(s.totalMs/s.count) : 0;
            const pct = successCount ? Math.round((s.count/successCount)*100) : 0;
            const isActive = (Date.now()-s._lastTime)<800;
            return `<div class="node-card ${isActive?'active':''}">
            <div class="node-name">🖥 ${host}<span class="badge ${isActive?'badge-active':'badge-idle'}">${isActive?'نشط ◉':'خامل'}</span></div>
            <div class="stat"><span class="stat-label">الطلبات</span><span class="stat-value">${s.count}</span></div>
            <div class="stat"><span class="stat-label">التوزيع</span><span class="stat-value">${pct}%</span></div>
            <div class="stat"><span class="stat-label">متوسط زمن</span><span class="stat-value">${avg}ms</span></div>
            <div class="stat"><span class="stat-label">آخر استجابة</span><span class="stat-value">${s.lastMs}ms</span></div>
            <div class="stat"><span class="stat-label">آخر طلب</span><span class="stat-value">${s.lastSeen||'-'}</span></div>
            <div class="bar-bg"><div class="bar-fg" style="width:${pct}%"></div></div>
        </div>`;
        }).join('');
    }

    function addMonitorLog(time, host, ms, method, action, isError) {
        const log = document.getElementById('log');
        const e   = document.createElement('div');
        const mc  = METHOD_COLORS[method]||'#374151';
        e.className = 'log-entry'+(isError?' error':'');
        e.innerHTML = `<span class="log-time">${time}</span><span class="log-host">${host}</span><span class="log-ms">${ms}ms</span><span style="background:${mc};padding:1px 5px;border-radius:3px;font-size:0.65rem">${method}</span><span class="log-action">${action}</span>`;
        log.prepend(e);
        while (log.children.length>150) log.removeChild(log.lastChild);
    }

    async function sendBurst() { await Promise.all(Array.from({length:20},()=>fetchRequest())); }

    function toggleAuto() {
        const btn = document.getElementById('auto-btn');
        if (autoTimer) { clearInterval(autoTimer); autoTimer=null; btn.textContent='▶ تلقائي'; btn.className='btn btn-green'; }
        else { autoTimer=setInterval(fetchRequest,400); btn.textContent='⏹ إيقاف'; btn.className='btn btn-red'; }
    }

    function clearAll() {
        monitorStats={}; total=0; successCount=0; errorCount=0; reqLast=0;
        ['total','success','errors'].forEach(id=>document.getElementById(id).textContent='0');
        document.getElementById('nodes-grid').innerHTML='';
        document.getElementById('log').innerHTML='';
    }

    // ══════════════════════════════════════════════
    // Stress Test
    // ══════════════════════════════════════════════
    function getEndpointsForMode(mode) {
        if (mode==='light')  return LIGHT_ENDPOINTS;
        if (mode==='heavy')  return HEAVY_ENDPOINTS;
        if (mode==='mixed') {
            // 80% light, 20% heavy
            const pool = [];
            for(let i=0;i<4;i++) pool.push(...LIGHT_ENDPOINTS);
            pool.push(...HEAVY_ENDPOINTS);
            return pool;
        }
        return ALL_ENDPOINTS;
    }

    function randomEndpoint(pool) { return pool[Math.floor(Math.random()*pool.length)]; }

    async function stressFetchOne(pool) {
        const ep = randomEndpoint(pool);
        if (ep.auth && !token) return; // skip if no token
        const t0 = Date.now();
        try {
            const headers = { 'Accept':'application/json','Content-Type':'application/json' };
            if (ep.auth && token) headers['Authorization'] = 'Bearer '+token;
            const opt = { method:ep.method, headers };
            if (ep.body && ['POST','PUT','PATCH'].includes(ep.method)) opt.body = JSON.stringify(ep.body);
            const res  = await fetch(ep.url, opt);
            const ms   = Date.now()-t0;
            const data = await res.json();
            const node = VALID_NODES.includes(data.node) ? data.node : null;
            stTotal++; stTotalMs+=ms; if(ms>stMaxMs) stMaxMs=ms; stRpsCount++;
            if (res.ok && node) {
                stOk++;
                if (!stressNodes[node]) stressNodes[node]={count:0,totalMs:0,lastMs:0,errors:0};
                stressNodes[node].count++; stressNodes[node].totalMs+=ms; stressNodes[node].lastMs=ms;
            } else { stErr++; }
            addStressLog(new Date().toLocaleTimeString('ar'), node||'?', ms, ep.method, ep.name, !res.ok);
        } catch(e) { stTotal++; stErr++; addStressLog(new Date().toLocaleTimeString('ar'),'error',0,ep.method,ep.name,true); }
        updateStressLive();
    }

    async function startStressTest() {
        if (stressRunning) return;

        // تأكد من الـ token
        if (!token) { const ok = await loginFirst(); if (!ok) { alert('يجب تسجيل الدخول أولاً'); return; } }

        const users    = parseInt(document.getElementById('st-users').value)    || 100;
        const duration = parseInt(document.getElementById('st-duration').value) || 30;
        const mode     = document.getElementById('st-mode').value;
        const interval = parseInt(document.getElementById('st-interval').value) || 100;
        const pool     = getEndpointsForMode(mode);

        // Reset
        stressRunning=true; stressNodes={}; stTotal=0; stOk=0; stErr=0; stTotalMs=0; stMaxMs=0; stRpsCount=0;
        stStartTime=Date.now();

        document.getElementById('stress-progress').style.display='block';
        document.getElementById('stress-results').style.display='none';
        document.getElementById('st-start-btn').disabled=true;
        document.getElementById('st-stop-btn').disabled=false;
        document.getElementById('stress-log').innerHTML='';
        document.getElementById('st-nodes-live').innerHTML='';

        // RPS counter
        stRpsInterval = setInterval(()=>{
            document.getElementById('st-live-rps').textContent = stRpsCount;
            stRpsCount=0;
        },1000);

        // Progress updater
        const progressInterval = setInterval(()=>{
            const elapsed = (Date.now()-stStartTime)/1000;
            const pct     = Math.min((elapsed/duration)*100,100);
            document.getElementById('st-progress-bar').style.width = pct+'%';
            document.getElementById('st-time-left').textContent = `${Math.max(0,Math.round(duration-elapsed))}s متبقي`;
        },500);

        // طلبات متزامنة — كل interval يرسل batch بعدد المستخدمين
        stressTimer = setInterval(async ()=>{
            if (!stressRunning) return;
            const batch = Array.from({length: Math.min(users, 20)}, ()=>stressFetchOne(pool));
            await Promise.all(batch);
        }, interval);

        // إيقاف تلقائي بعد المدة
        setTimeout(()=>{
            stopStressTest();
            clearInterval(progressInterval);
        }, duration*1000);
    }

    function stopStressTest() {
        stressRunning=false;
        if (stressTimer)   { clearInterval(stressTimer);   stressTimer=null; }
        if (stRpsInterval) { clearInterval(stRpsInterval); stRpsInterval=null; }
        document.getElementById('st-start-btn').disabled=false;
        document.getElementById('st-stop-btn').disabled=true;
        document.getElementById('st-progress-bar').style.width='100%';
        document.getElementById('st-status-text').textContent='✅ اكتمل الاختبار';
        showStressResults();
    }

    function updateStressLive() {
        document.getElementById('st-live-total').textContent = stTotal;
        document.getElementById('st-live-ok').textContent    = stOk;
        document.getElementById('st-live-err').textContent   = stErr;
        const avg = stTotal ? Math.round(stTotalMs/stTotal) : 0;
        document.getElementById('st-live-avg').textContent   = avg+'ms';

        // Live nodes
        const grid  = document.getElementById('st-nodes-live');
        const hosts = Object.keys(stressNodes).sort();
        if (!hosts.length) return;
        grid.innerHTML = hosts.map(node=>{
            const s   = stressNodes[node];
            const avg = s.count ? Math.round(s.totalMs/s.count) : 0;
            const pct = stOk    ? Math.round((s.count/stOk)*100) : 0;
            return `<div class="node-card active">
            <div class="node-name">🖥 ${node}<span class="badge badge-active">نشط ◉</span></div>
            <div class="stat"><span class="stat-label">الطلبات</span><span class="stat-value">${s.count}</span></div>
            <div class="stat"><span class="stat-label">التوزيع</span><span class="stat-value">${pct}%</span></div>
            <div class="stat"><span class="stat-label">متوسط زمن</span><span class="stat-value">${avg}ms</span></div>
            <div class="bar-bg"><div class="bar-fg" style="width:${pct}%"></div></div>
        </div>`;
        }).join('');
    }

    function showStressResults() {
        const elapsed  = Math.round((Date.now()-stStartTime)/1000);
        const avg      = stTotal ? Math.round(stTotalMs/stTotal) : 0;
        const rps      = elapsed ? Math.round(stTotal/elapsed) : 0;
        const hosts    = Object.keys(stressNodes).sort();

        document.getElementById('stress-results').style.display='block';
        document.getElementById('r-total').textContent    = stTotal;
        document.getElementById('r-success').textContent  = stOk;
        document.getElementById('r-errors').textContent   = stErr;
        document.getElementById('r-rps').textContent      = rps;
        document.getElementById('r-avg').textContent      = avg+'ms';
        document.getElementById('r-max').textContent      = stMaxMs+'ms';
        document.getElementById('r-users').textContent    = document.getElementById('st-users').value;
        document.getElementById('r-duration').textContent = elapsed+'s';

        document.getElementById('results-nodes').innerHTML = hosts.map(node=>{
            const s    = stressNodes[node];
            const avg  = s.count ? Math.round(s.totalMs/s.count) : 0;
            const pct  = stOk    ? Math.round((s.count/stOk)*100) : 0;
            return `<div class="result-node">
            <h4>🖥 ${node}</h4>
            <div class="result-stat"><span class="rl">الطلبات المعالجة</span><span class="rv">${s.count}</span></div>
            <div class="result-stat"><span class="rl">نسبة التوزيع</span><span class="rv">${pct}%</span></div>
            <div class="result-stat"><span class="rl">متوسط زمن الاستجابة</span><span class="rv">${avg}ms</span></div>
            <div class="result-stat"><span class="rl">آخر استجابة</span><span class="rv">${s.lastMs}ms</span></div>
            <div class="result-bar"><div class="result-bar-fill" style="width:${pct}%"></div></div>
        </div>`;
        }).join('');
    }

    function addStressLog(time, node, ms, method, action, isError) {
        const log = document.getElementById('stress-log');
        const e   = document.createElement('div');
        const mc  = METHOD_COLORS[method]||'#374151';
        e.className = 'slog-entry';
        e.innerHTML = `
        <span class="${isError?'slog-err':'slog-ok'}">${time}</span>
        <span style="color:#7dd3fc">${node}</span>
        <span style="color:#fbbf24">${ms}ms</span>
        <span style="background:${mc};padding:1px 4px;border-radius:3px;font-size:0.65rem">${method}</span>
        <span style="color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${action}</span>`;
        log.prepend(e);
        while (log.children.length>200) log.removeChild(log.lastChild);
    }

    setInterval(()=>{ document.getElementById('rps').textContent=reqLast; reqLast=0; },1000);
    buildGroups();
    loginFirst();
</script>
</body>
</html>
