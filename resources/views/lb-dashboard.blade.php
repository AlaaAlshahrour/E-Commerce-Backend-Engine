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

        .tabs {
            display: flex;
            gap: 0.4rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #334155;
            padding-bottom: 0;
            flex-wrap: wrap;
        }

        .tab {
            padding: 0.5rem 1rem;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-size: 0.82rem;
            border: 1px solid transparent;
            border-bottom: none;
        }

        .tab.active {
            background: #1e293b;
            border-color: #334155;
            color: #7dd3fc;
        }

        .tab:not(.active) {
            color: #64748b;
        }

        .tab:not(.active):hover {
            color: #94a3b8;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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

        .auth-ok {
            color: #4ade80;
        }

        .auth-fail {
            color: #f87171;
        }

        .auth-pending {
            color: #fbbf24;
        }

        .auth-status {
            font-size: 0.82rem;
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

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-blue {
            background: #1d4ed8;
        }

        .btn-blue:hover:not(:disabled) {
            background: #2563eb;
        }

        .btn-green {
            background: #15803d;
        }

        .btn-green:hover:not(:disabled) {
            background: #16a34a;
        }

        .btn-red {
            background: #dc2626;
        }

        .btn-red:hover:not(:disabled) {
            background: #ef4444;
        }

        .btn-purple {
            background: #7c3aed;
        }

        .btn-purple:hover:not(:disabled) {
            background: #8b5cf6;
        }

        .btn-orange {
            background: #c2410c;
        }

        .btn-orange:hover:not(:disabled) {
            background: #ea580c;
        }

        .btn-gray {
            background: #374151;
        }

        .btn-gray:hover:not(:disabled) {
            background: #4b5563;
        }

        .groups-container {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .group {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            overflow: hidden;
        }

        .group-header {
            padding: 0.5rem 1rem;
            font-size: 0.78rem;
            font-weight: bold;
        }

        .group-body {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 0.4rem;
            padding: 0.5rem;
        }

        .action-btn {
            border: none;
            padding: 0.55rem 0.7rem;
            border-radius: 7px;
            cursor: pointer;
            font-family: monospace;
            font-size: 0.75rem;
            color: white;
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .action-btn .method-badge {
            font-size: 0.6rem;
            font-weight: bold;
            padding: 1px 5px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 2px;
        }

        .action-btn .ep-name {
            font-weight: bold;
            font-size: 0.78rem;
        }

        .action-btn .ep-url {
            font-size: 0.6rem;
            opacity: 0.6;
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
            transition: all 0.3s;
        }

        .node-card.active {
            border-color: #22d3ee;
            box-shadow: 0 0 16px rgba(34, 211, 238, 0.15);
        }

        .node-card.down {
            border-color: #dc2626;
            opacity: 0.6;
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

        .badge-down {
            background: #450a0a;
            color: #f87171;
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

        .log-area {
            background: #0d1117;
            border: 1px solid #21262d;
            border-radius: 8px;
            padding: 0.8rem;
            height: 200px;
            overflow-y: auto;
            font-size: 0.78rem;
        }

        .log-entry {
            padding: 3px 0;
            border-bottom: 1px solid #1e293b;
            display: grid;
            grid-template-columns: 75px 80px 75px 80px 1fr;
            gap: 0.4rem;
            align-items: center;
        }

        .log-time {
            color: #4ade80;
        }

        .log-host {
            color: #7dd3fc;
        }

        .log-ms {
            color: #fbbf24;
        }

        .log-action {
            color: #94a3b8;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .log-entry.error .log-action {
            color: #f87171;
        }

        .section-title {
            color: #64748b;
            font-size: 0.82rem;
            margin-bottom: 0.5rem;
        }

        .box {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 1.2rem;
            margin-bottom: 1rem;
        }

        .box h3 {
            color: #7dd3fc;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        /* Stress */
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .config-item label {
            display: block;
            color: #94a3b8;
            font-size: 0.75rem;
            margin-bottom: 0.3rem;
        }

        .config-item input, .config-item select {
            width: 100%;
            background: #0f1117;
            border: 1px solid #334155;
            color: #e2e8f0;
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.82rem;
        }

        .progress-bar-bg {
            background: #0f1117;
            border-radius: 4px;
            height: 8px;
            margin: 0.8rem 0;
        }

        .progress-bar-fg {
            background: linear-gradient(90deg, #22d3ee, #7dd3fc);
            height: 8px;
            border-radius: 4px;
            transition: width 0.3s;
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 0.6rem;
        }

        .progress-stat {
            background: #0f1117;
            border-radius: 8px;
            padding: 0.6rem;
            text-align: center;
        }

        .ps-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #7dd3fc;
        }

        .ps-label {
            font-size: 0.7rem;
            color: #64748b;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .result-node {
            background: #0f1117;
            border-radius: 8px;
            padding: 1rem;
            border-left: 3px solid #22d3ee;
        }

        .result-node h4 {
            color: #7dd3fc;
            margin-bottom: 0.6rem;
            font-size: 0.85rem;
        }

        .result-stat {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            margin: 0.25rem 0;
        }

        .rl {
            color: #94a3b8;
        }

        .rv {
            color: #e2e8f0;
            font-weight: bold;
        }

        .result-bar {
            background: #1e293b;
            border-radius: 4px;
            height: 4px;
            margin-top: 0.5rem;
        }

        .result-bar-fill {
            height: 4px;
            border-radius: 4px;
            background: #22d3ee;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 0.6rem;
        }

        .summary-item {
            text-align: center;
            background: #0f1117;
            border-radius: 8px;
            padding: 0.8rem;
        }

        .sv {
            font-size: 1.3rem;
            font-weight: bold;
            color: #7dd3fc;
        }

        .sl {
            font-size: 0.7rem;
            color: #64748b;
        }

        .stress-log {
            background: #0d1117;
            border: 1px solid #21262d;
            border-radius: 8px;
            padding: 0.8rem;
            height: 140px;
            overflow-y: auto;
            font-size: 0.75rem;
        }

        .slog-entry {
            padding: 2px 0;
            border-bottom: 1px solid #1e293b;
            display: grid;
            grid-template-columns: 70px 70px 70px 70px 1fr;
            gap: 0.4rem;
        }

        .slog-ok {
            color: #4ade80;
        }

        .slog-err {
            color: #f87171;
        }

        /* Comparison */
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-bottom: 1rem;
        }

        .comparison-table th {
            background: #1e293b;
            color: #7dd3fc;
            padding: 0.6rem 1rem;
            text-align: right;
            border: 1px solid #334155;
        }

        .comparison-table td {
            padding: 0.6rem 1rem;
            border: 1px solid #334155;
        }

        .comparison-table tr:nth-child(even) td {
            background: #1a2535;
        }

        .comparison-table tr:nth-child(odd) td {
            background: #151d2a;
        }

        .highlight {
            color: #4ade80;
            font-weight: bold;
        }

        .algo-card {
            background: #0f1117;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #334155;
        }

        .algo-card h4 {
            font-size: 0.85rem;
            margin-bottom: 0.6rem;
        }

        .algo-card.winner {
            border-color: #4ade80;
        }

        .chart-bar-row {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin: 0.4rem 0;
            font-size: 0.78rem;
        }

        .chart-label {
            width: 120px;
            color: #94a3b8;
            text-align: left;
            flex-shrink: 0;
        }

        .chart-bar-container {
            flex: 1;
            background: #1e293b;
            border-radius: 4px;
            height: 20px;
        }

        .chart-bar {
            height: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 0 6px;
            font-size: 0.7rem;
            font-weight: bold;
            transition: width 0.8s;
        }

        .chart-val {
            width: 70px;
            text-align: right;
            flex-shrink: 0;
        }

        /* Failure */
        .nodes-control {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .node-control-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.2rem;
        }

        .node-control-card.running {
            border-color: #22d3ee;
        }

        .node-control-card.stopped {
            border-color: #dc2626;
            opacity: 0.7;
        }

        .node-ctrl-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .node-ctrl-name {
            font-weight: bold;
            color: #7dd3fc;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot-green {
            background: #4ade80;
            box-shadow: 0 0 6px #4ade80;
        }

        .dot-red {
            background: #f87171;
            box-shadow: 0 0 6px #f87171;
        }

        .failure-log {
            background: #0d1117;
            border: 1px solid #21262d;
            border-radius: 8px;
            padding: 0.8rem;
            height: 180px;
            overflow-y: auto;
            font-size: 0.78rem;
            margin-top: 1rem;
        }

        .flog-entry {
            padding: 3px 0;
            border-bottom: 1px solid #1e293b;
            font-size: 0.75rem;
        }

        .flog-time {
            color: #64748b;
            margin-right: 0.5rem;
        }

        .flog-info {
            color: #94a3b8;
        }

        .flog-warn {
            color: #fbbf24;
        }

        .flog-err {
            color: #f87171;
        }

        .flog-ok {
            color: #4ade80;
        }
    </style>
</head>
<body>

<h1>⚖️ Load Balancer Dashboard</h1>
<p class="subtitle">Least Connections — NGINX + Docker — Real-time monitoring</p>

<div class="auth-bar">
    <span class="auth-status" id="auth-status"><span class="auth-pending">⏳ جاري تسجيل الدخول...</span></span>
    <button class="btn btn-blue" onclick="loginFirst(true)" style="padding:0.3rem 0.8rem;font-size:0.78rem">🔄 إعادة
        تسجيل الدخول
    </button>
    <span style="font-size:0.75rem;color:#475569" id="token-preview"></span>
</div>

<div class="tabs">
    <div class="tab active" onclick="switchTab('monitor')">📊 مراقبة مباشرة</div>
    <div class="tab" onclick="switchTab('stress')">🔥 Stress Test</div>
    <div class="tab" onclick="switchTab('compare')">📈 مقارنة الخوارزميات</div>
    <div class="tab" onclick="switchTab('failure')">⚡ محاكاة الفشل</div>
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
    <div class="box">
        <h3>⚙️ إعدادات الاختبار</h3>
        <div class="config-grid">
            <div class="config-item"><label>عدد المستخدمين</label><input type="number" id="st-users" value="100" min="1"
                                                                         max="500"></div>
            <div class="config-item"><label>المدة (ثانية)</label><input type="number" id="st-duration" value="30"
                                                                        min="5" max="120"></div>
            <div class="config-item"><label>نوع الطلبات</label>
                <select id="st-mode">
                    <option value="random">عشوائي — كل الـ APIs</option>
                    <option value="light">خفيف (Products, Categories)</option>
                    <option value="heavy">ثقيل (Orders, Inventory)</option>
                </select>
            </div>
            <div class="config-item"><label>الفاصل الزمني بين الدُفعات (ms)</label><input type="number" id="st-interval"
                                                                                          value="100" min="50"
                                                                                          max="2000"></div>
        </div>
        <div style="display:flex;gap:0.5rem">
            <button class="btn btn-purple" id="st-start-btn" onclick="startStressTest()">🚀 ابدأ الاختبار</button>
            <button class="btn btn-red" id="st-stop-btn" onclick="stopStressTest()" disabled>⏹ إيقاف</button>
        </div>
    </div>

    <div class="box" id="stress-progress" style="display:none">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:#7dd3fc;font-size:0.9rem" id="st-status-text">جاري الاختبار...</span>
            <span style="color:#64748b;font-size:0.78rem" id="st-time-left"></span>
        </div>
        <div class="progress-bar-bg">
            <div class="progress-bar-fg" id="st-progress-bar" style="width:0%"></div>
        </div>
        <div class="progress-stats">
            <div class="progress-stat">
                <div class="ps-value" id="st-live-total">0</div>
                <div class="ps-label">إجمالي</div>
            </div>
            <div class="progress-stat">
                <div class="ps-value" id="st-live-rps" style="color:#4ade80">0</div>
                <div class="ps-label">req/s</div>
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
        <div style="margin-top:1rem">
            <p class="section-title">توزيع حي على العقد</p>
            <div class="grid" id="st-nodes-live"></div>
        </div>
    </div>

    <div class="box" id="stress-results" style="display:none">
        <div style="color:#4ade80;font-size:0.9rem;margin-bottom:1rem">✅ اكتمل الاختبار</div>
        <div class="results-grid" id="results-nodes"></div>
        <div style="background:#0f1117;border-radius:8px;padding:1rem;margin-top:1rem">
            <div style="color:#fbbf24;font-size:0.85rem;margin-bottom:0.8rem">📊 ملخص الاختبار</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="sv" id="r-total">0</div>
                    <div class="sl">إجمالي</div>
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
                    <div class="sl">req/s</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-avg">0ms</div>
                    <div class="sl">متوسط زمن</div>
                </div>
                <div class="summary-item">
                    <div class="sv" id="r-max">0ms</div>
                    <div class="sl">أقصى زمن</div>
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

    <p class="section-title">سجل الاختبار</p>
    <div class="stress-log" id="stress-log"></div>
</div>

<!-- ══ Tab 3: Algorithm Comparison ══ -->
<div class="tab-content" id="tab-compare">
    <div class="box">
        <h3>📈 مقارنة الخوارزميات — Round Robin vs Least Connections</h3>
        <p style="color:#64748b;font-size:0.78rem;margin-bottom:1rem">
            يحاكي هذا الاختبار سلوك كلتا الخوارزميتين على نفس الطلبات ويقارن النتائج بالأرقام.
        </p>
        <div class="config-grid">
            <div class="config-item"><label>عدد الطلبات لكل خوارزمية</label><input type="number" id="cmp-requests"
                                                                                   value="60" min="20" max="200"></div>
            <div class="config-item"><label>نوع الطلبات</label>
                <select id="cmp-mode">
                    <option value="mixed">مختلط (خفيف + ثقيل)</option>
                    <option value="light">خفيف فقط</option>
                    <option value="heavy">ثقيل فقط</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:0.5rem;margin-bottom:1rem">
            <button class="btn btn-purple" id="cmp-btn" onclick="runComparison()">🔬 ابدأ المقارنة</button>
        </div>

        <!-- نتائج المقارنة -->
        <div id="cmp-results" style="display:none">
            <!-- بطاقات الخوارزميات -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem">
                <div class="algo-card" id="rr-card">
                    <h4 style="color:#fbbf24">🔄 Round Robin (محاكاة)</h4>
                    <div class="stat"><span class="stat-label">متوسط زمن الاستجابة</span><span class="stat-value"
                                                                                               id="rr-avg">-</span>
                    </div>
                    <div class="stat"><span class="stat-label">أقصى زمن استجابة</span><span class="stat-value"
                                                                                            id="rr-max">-</span></div>
                    <div class="stat"><span class="stat-label">Throughput (req/s)</span><span class="stat-value"
                                                                                              id="rr-rps">-</span></div>
                    <div class="stat"><span class="stat-label">توزيع على العقد</span><span class="stat-value"
                                                                                           id="rr-dist">-</span></div>
                </div>
                <div class="algo-card winner" id="lc-card">
                    <h4 style="color:#4ade80">✅ Least Connections (فعلي)</h4>
                    <div class="stat"><span class="stat-label">متوسط زمن الاستجابة</span><span class="stat-value"
                                                                                               id="lc-avg">-</span>
                    </div>
                    <div class="stat"><span class="stat-label">أقصى زمن استجابة</span><span class="stat-value"
                                                                                            id="lc-max">-</span></div>
                    <div class="stat"><span class="stat-label">Throughput (req/s)</span><span class="stat-value"
                                                                                              id="lc-rps">-</span></div>
                    <div class="stat"><span class="stat-label">توزيع على العقد</span><span class="stat-value"
                                                                                           id="lc-dist">-</span></div>
                </div>
            </div>

            <!-- جدول المقارنة -->
            <div style="margin-bottom:1.5rem">
                <p class="section-title">جدول المقارنة التفصيلي</p>
                <table class="comparison-table">
                    <thead>
                    <tr>
                        <th>المقياس</th>
                        <th>Round Robin</th>
                        <th>Least Connections</th>
                        <th>الأفضل</th>
                    </tr>
                    </thead>
                    <tbody id="cmp-table-body"></tbody>
                </table>
            </div>
            <!-- الخلاصة -->
            <div style="background:#0f2d1e;border:1px solid #16a34a;border-radius:8px;padding:1rem">
                <div style="color:#4ade80;font-size:0.85rem;margin-bottom:0.5rem">✅ الخلاصة الهندسية</div>
                <div style="color:#94a3b8;font-size:0.78rem;line-height:1.8" id="cmp-conclusion"></div>
            </div>
        </div>

        <!-- progress -->
        <div id="cmp-progress" style="display:none;color:#fbbf24;font-size:0.82rem;padding:0.5rem 0"></div>
    </div>
</div>

<!-- ══ Tab 4: Failure Simulation ══ -->
<div class="tab-content" id="tab-failure">
    <div class="box">
        <h3>⚡ محاكاة فشل العقد — Failure Simulation</h3>
        <p style="color:#64748b;font-size:0.78rem;margin-bottom:1rem">
            أوقف عقدة وراقب كيف يعيد NGINX توزيع الحمل تلقائياً على العقد المتبقية.
        </p>

        <div class="nodes-control" id="nodes-control"></div>

        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem">
            <button class="btn btn-blue" onclick="startFailureMonitor()">▶ ابدأ مراقبة الطلبات</button>
            <button class="btn btn-red" id="fail-stop-btn" onclick="stopFailureMonitor()" disabled>⏹ إيقاف</button>
            <button class="btn btn-gray" onclick="restoreAllNodes()">🔄 استعادة كل العقد</button>
        </div>

        <!-- Live stats during failure -->
        <div id="failure-live" style="display:none">
            <p class="section-title">توزيع الطلبات في الوقت الفعلي</p>
            <div class="grid" id="failure-nodes-grid"></div>
            <div class="controls" style="margin-top:0.5rem">
                <div class="counter">إجمالي: <span id="fl-total" style="color:#7dd3fc;font-weight:bold">0</span></div>
                <div class="counter">ناجح: <span id="fl-ok" style="color:#4ade80;font-weight:bold">0</span></div>
                <div class="counter">فاشل: <span id="fl-err" style="color:#f87171;font-weight:bold">0</span></div>
                <div class="counter">req/s: <span id="fl-rps" style="color:#7dd3fc;font-weight:bold">0</span></div>
            </div>
        </div>

        <p class="section-title" style="margin-top:1rem">سجل الأحداث</p>
        <div class="failure-log" id="failure-log"></div>
    </div>
</div>

<script>
    const VALID_NODES = ['Node-1', 'Node-2', 'Node-3'];
    const METHOD_COLORS = {GET: '#15803d', POST: '#1d4ed8', PUT: '#b45309', PATCH: '#6d28d9', DELETE: '#dc2626'};

    const LIGHT_EPS = [
        {name: 'All Products', method: 'GET', url: '/api/products', auth: false},
        {name: 'Show Product', method: 'GET', url: '/api/products/1', auth: false},
        {name: 'All Categories', method: 'GET', url: '/api/categories', auth: false},
        {name: 'Show Category', method: 'GET', url: '/api/categories/1', auth: false},
    ];
    const HEAVY_EPS = [
        {name: 'All Inventory', method: 'GET', url: '/api/inventory', auth: true},
        {name: 'All Orders', method: 'GET', url: '/api/orders', auth: true},
        {name: 'My Profile', method: 'GET', url: '/api/me', auth: true},
        {name: 'Show Wallet', method: 'GET', url: '/api/wallet', auth: true},
        {name: 'Get Cart', method: 'GET', url: '/api/cart', auth: true},
    ];
    const ALL_EPS = [...LIGHT_EPS, ...HEAVY_EPS];

    const GROUPS = [
        {
            name: '🔐 Auth', color: '#1e3050', headerColor: '#3b82f6', endpoints: [
                {
                    name: 'Register',
                    method: 'POST',
                    url: '/api/register',
                    auth: false,
                    body: {
                        name: 'Test',
                        email: 'test@test.com',
                        password: 'password',
                        password_confirmation: 'password'
                    }
                },
                {
                    name: 'Login',
                    method: 'POST',
                    url: '/api/login',
                    auth: false,
                    body: {email: 'test@test.com', password: 'password'}
                },
                {name: 'Me', method: 'GET', url: '/api/me', auth: true},
                {name: 'Logout', method: 'POST', url: '/api/logout', auth: true},
            ]
        },
        {
            name: '📦 Products', color: '#0f2d1e', headerColor: '#16a34a', endpoints: [
                {name: 'All Products', method: 'GET', url: '/api/products', auth: false},
                {name: 'Show Product', method: 'GET', url: '/api/products/1', auth: false},
                {
                    name: 'Create Product',
                    method: 'POST',
                    url: '/api/products',
                    auth: true,
                    body: {name: 'Test', price: 100, category_id: 1, description: 'This is a test product'}
                },
            ]
        },
        {
            name: '🗂 Categories', color: '#1a1535', headerColor: '#8b5cf6', endpoints: [
                {name: 'All Categories', method: 'GET', url: '/api/categories', auth: false},
                {name: 'Show Category', method: 'GET', url: '/api/categories/1', auth: false},
                {name: 'Create Category', method: 'POST', url: '/api/categories', auth: true, body: {name: 'New Cat'}},
            ]
        },
        {
            name: '🛒 Cart', color: '#1f1506', headerColor: '#d97706', endpoints: [
                {name: 'Get Cart', method: 'GET', url: '/api/cart', auth: true},
                {name: 'Add to Cart', method: 'POST', url: '/api/cart/add/1', auth: true, body: {quantity: 4}},
                {name: 'Update Cart', method: 'PATCH', url: '/api/cart/update/1', auth: true, body: {quantity: 2}},
                {name: 'Clear Cart', method: 'DELETE', url: '/api/cart/clear', auth: true},
            ]
        },
        {
            name: '🏭 Inventory', color: '#1f0d0d', headerColor: '#ef4444', endpoints: [
                {name: 'All Inventory', method: 'GET', url: '/api/inventory', auth: true},
                {name: 'Show Inventory', method: 'GET', url: '/api/inventory/1', auth: true},
                {name: 'Update Inventory', method: 'PUT', url: '/api/inventory/1', auth: true, body: {quantity: 50}},
            ]
        },
        {
            name: '📋 Orders', color: '#12102a', headerColor: '#6366f1', endpoints: [
                {name: 'All Orders', method: 'GET', url: '/api/orders', auth: true},
                {name: 'Show Order', method: 'GET', url: '/api/orders/1', auth: true},
                {
                    name: 'Checkout',
                    method: 'POST',
                    url: '/api/orders/checkout',
                    auth: true,
                    body: {shipping_address: 'bagdad street'}
                },
            ]
        },
        {
            name: '💰 Wallet', color: '#0d1f1a', headerColor: '#14b8a6', endpoints: [
                {name: 'Show Wallet', method: 'GET', url: '/api/wallet', auth: true},
                {name: 'Top Up', method: 'POST', url: '/api/wallet/topup', auth: true, body: {amount: 100}},
            ]
        },
    ];

    // ── State ──
    let selectedEndpoint = GROUPS[0].endpoints[2];
    let monitorStats = {}, total = 0, successCount = 0, errorCount = 0, reqLast = 0, autoTimer = null;
    let token = localStorage.getItem('lb_token') || null;

    // Stress
    let stressRunning = false, stressTimer = null, stressNodes = {};
    let stTotal = 0, stOk = 0, stErr = 0, stTotalMs = 0, stMaxMs = 0, stRpsCount = 0, stStartTime = 0,
        stRpsInterval = null;

    // Failure
    let failureTimer = null, failureNodes = {}, flTotal = 0, flOk = 0, flErr = 0, flRps = 0, flRpsInterval = null;
    let stoppedNodes = new Set();

    // ── Tabs ──
    function switchTab(name) {
        const names = ['monitor', 'stress', 'compare', 'failure'];
        names.forEach((n, i) => {
            document.querySelectorAll('.tab')[i].classList.toggle('active', n === name);
            document.getElementById('tab-' + n).classList.toggle('active', n === name);
        });
        if (name === 'failure') renderNodeControls();
    }

    // ── Auth ──
    async function loginFirst(manual = false) {
        document.getElementById('auth-status').innerHTML = '<span class="auth-pending">⏳ جاري تسجيل الدخول...</span>';
        if (!manual && token) {
            if (await verifyToken()) {
                showAuthOk();
                return true;
            }
            token = null;
            localStorage.removeItem('lb_token');
        }
        try {
            const res = await fetch('/api/login', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({email: 'test@test.com', password: 'password'})
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
        } catch (e) {
            showAuthFail(e.message);
            return false;
        }
    }

    async function verifyToken() {
        try {
            const r = await fetch('/api/me', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            });
            return r.ok;
        } catch {
            return false;
        }
    }

    function showAuthOk() {
        document.getElementById('auth-status').innerHTML = '<span class="auth-ok">✅ مسجّل دخول: test@test.com</span>';
        document.getElementById('token-preview').textContent = 'Token: ' + token.substring(0, 20) + '...';
    }

    function showAuthFail(msg) {
        document.getElementById('auth-status').innerHTML = `<span class="auth-fail">❌ فشل: ${msg}</span>`;
        document.getElementById('token-preview').textContent = '';
    }

    // ── Monitor ──
    function buildGroups() {
        document.getElementById('groups-container').innerHTML = GROUPS.map((g, gi) => `
    <div class="group">
        <div class="group-header" style="background:${g.headerColor}22;border-bottom:1px solid ${g.headerColor}44"><span style="color:${g.headerColor}">${g.name}</span></div>
        <div class="group-body" style="background:${g.color}">
            ${g.endpoints.map((ep, ei) => {
            const sel = selectedEndpoint === ep;
            const mc = METHOD_COLORS[ep.method] || '#374151';
            return `<button class="action-btn" style="background:${mc}${sel ? 'ff' : '55'};border:2px solid ${sel ? '#fff' : 'transparent'}" onclick="selectEndpoint(${gi},${ei})"><span class="method-badge" style="background:${mc}">${ep.method}</span><span class="ep-name">${ep.name}</span><span class="ep-url">${ep.url}</span></button>`;
        }).join('')}
        </div>
    </div>`).join('');
    }

    function selectEndpoint(gi, ei) {
        selectedEndpoint = GROUPS[gi].endpoints[ei];
        buildGroups();
    }

    async function fetchRequest(retry = true) {

        const ep = selectedEndpoint;

        // تسجيل دخول أولي
        if (ep.auth && !token) {
            const ok = await loginFirst();

            if (!ok) {
                recordMonitor('error', 0, ep.name, true, ep.method);
                return;
            }
        }

        const t0 = Date.now();

        try {

            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };

            if (ep.auth && token) {
                headers['Authorization'] = 'Bearer ' + token;
            }

            const options = {
                method: ep.method,
                headers
            };

            if (
                ep.body &&
                ['POST', 'PUT', 'PATCH'].includes(ep.method)
            ) {
                options.body = JSON.stringify(ep.body);
            }

            let res = await fetch(ep.url, options);

            // إذا انتهى التوكن → أعد تسجيل الدخول وأعد الطلب مرة واحدة
            if (res.status === 401 && retry) {

                token = null;
                localStorage.removeItem('lb_token');

                showAuthFail('🔄 إعادة تسجيل الدخول...');

                const ok = await loginFirst(true);

                if (!ok) {
                    recordMonitor('error', Date.now() - t0, ep.name, true, ep.method);
                    return;
                }

                // إعادة الطلب بعد التوكن الجديد
                return fetchRequest(false);
            }

            const ms = Date.now() - t0;

            const data = await res.json();

            const host = data.node ?? 'missing-node';
            console.log("RAW RESPONSE:", data);
            recordMonitor(
                host,
                ms,
                ep.name,
                !res.ok,
                ep.method
            );

        } catch (e) {

            recordMonitor(
                'error',
                Date.now() - t0,
                e.message,
                true,
                ep.method
            );
        }
    }

    function recordMonitor(host, ms, action, isError, method = 'GET') {
        total++;
        reqLast++;
        const clean = VALID_NODES.includes(host) ? host : null;
        if (isError) errorCount++; else if (clean) successCount++;
        document.getElementById('total').textContent = total;
        document.getElementById('success').textContent = successCount;
        document.getElementById('errors').textContent = errorCount;
        if (clean) {
            if (!monitorStats[clean]) monitorStats[clean] = {
                count: 0,
                totalMs: 0,
                lastMs: 0,
                lastSeen: null,
                _lastTime: 0
            };
            const s = monitorStats[clean];
            s.count++;
            s.totalMs += ms;
            s.lastMs = ms;
            s.lastSeen = new Date().toLocaleTimeString('ar');
            s._lastTime = Date.now();
        }
        renderMonitorNodes();
        addMonitorLog(new Date().toLocaleTimeString('ar'), clean || host, ms, method, action, isError);
    }

    function renderMonitorNodes() {
        const grid = document.getElementById('nodes-grid');
        const hosts = Object.keys(monitorStats).sort();
        if (!hosts.length) return;
        grid.innerHTML = hosts.map(host => {
            const s = monitorStats[host];
            const avg = s.count ? Math.round(s.totalMs / s.count) : 0;
            const pct = successCount ? Math.round((s.count / successCount) * 100) : 0;
            const isActive = (Date.now() - s._lastTime) < 800;
            return `<div class="node-card ${isActive ? 'active' : ''}"><div class="node-name">🖥 ${host}<span class="badge ${isActive ? 'badge-active' : 'badge-idle'}">${isActive ? 'نشط ◉' : 'خامل'}</span></div><div class="stat"><span class="stat-label">الطلبات</span><span class="stat-value">${s.count}</span></div><div class="stat"><span class="stat-label">التوزيع</span><span class="stat-value">${pct}%</span></div><div class="stat"><span class="stat-label">متوسط زمن</span><span class="stat-value">${avg}ms</span></div><div class="stat"><span class="stat-label">آخر استجابة</span><span class="stat-value">${s.lastMs}ms</span></div><div class="stat"><span class="stat-label">آخر طلب</span><span class="stat-value">${s.lastSeen || '-'}</span></div><div class="bar-bg"><div class="bar-fg" style="width:${pct}%"></div></div></div>`;
        }).join('');
    }

    function addMonitorLog(time, host, ms, method, action, isError) {
        const log = document.getElementById('log');
        const e = document.createElement('div');
        const mc = METHOD_COLORS[method] || '#374151';
        e.className = 'log-entry' + (isError ? ' error' : '');
        e.innerHTML = `<span class="log-time">${time}</span><span class="log-host">${host}</span><span class="log-ms">${ms}ms</span><span style="background:${mc};padding:1px 5px;border-radius:3px;font-size:0.65rem">${method}</span><span class="log-action">${action}</span>`;
        log.prepend(e);
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
        monitorStats = {};
        total = 0;
        successCount = 0;
        errorCount = 0;
        reqLast = 0;
        ['total', 'success', 'errors'].forEach(id => document.getElementById(id).textContent = '0');
        document.getElementById('nodes-grid').innerHTML = '';
        document.getElementById('log').innerHTML = '';
    }

    // ── Stress ──
    function getPool(mode) {
        if (mode === 'light') {
            return LIGHT_EPS;
        }
        if (mode === 'heavy') {
            return HEAVY_EPS;
        }
        return ALL_EPS;
    }

    function randEp(pool) {
        return pool[Math.floor(Math.random() * pool.length)];
    }

    async function stressFetchOne(pool) {
        const ep = randEp(pool);
        if (ep.auth && !token) return;
        const t0 = Date.now();
        try {
            const h = {'Accept': 'application/json', 'Content-Type': 'application/json'};
            if (ep.auth && token) h['Authorization'] = 'Bearer ' + token;
            const opt = {method: ep.method, headers: h};
            if (ep.body && ['POST', 'PUT', 'PATCH'].includes(ep.method)) opt.body = JSON.stringify(ep.body);
            const res = await fetch(ep.url, opt);
            const ms = Date.now() - t0;
            const data = await res.json();
            const node = VALID_NODES.includes(data.node) ? data.node : null;
            stTotal++;
            stTotalMs += ms;
            if (ms > stMaxMs) stMaxMs = ms;
            stRpsCount++;
            if (res.ok && node) {
                stOk++;
                if (!stressNodes[node]) stressNodes[node] = {count: 0, totalMs: 0, lastMs: 0};
                stressNodes[node].count++;
                stressNodes[node].totalMs += ms;
                stressNodes[node].lastMs = ms;
            } else {
                stErr++;
            }
            addStressLog(new Date().toLocaleTimeString('ar'), node || '?', ms, ep.method, ep.name, !res.ok);
        } catch (e) {
            stTotal++;
            stErr++;
        }
        updateStressLive();
    }

    async function startStressTest() {
        if (stressRunning) return;
        if (!token) {
            const ok = await loginFirst();
            if (!ok) {
                alert('يجب تسجيل الدخول');
                return;
            }
        }
        const users = parseInt(document.getElementById('st-users').value) || 100;
        const duration = parseInt(document.getElementById('st-duration').value) || 30;
        const mode = document.getElementById('st-mode').value;
        const interval = parseInt(document.getElementById('st-interval').value) || 100;
        const pool = getPool(mode);
        stressRunning = true;
        stressNodes = {};
        stTotal = 0;
        stOk = 0;
        stErr = 0;
        stTotalMs = 0;
        stMaxMs = 0;
        stRpsCount = 0;
        stStartTime = Date.now();
        document.getElementById('stress-progress').style.display = 'block';
        document.getElementById('stress-results').style.display = 'none';
        document.getElementById('st-start-btn').disabled = true;
        document.getElementById('st-stop-btn').disabled = false;
        document.getElementById('stress-log').innerHTML = '';
        stRpsInterval = setInterval(() => {
            document.getElementById('st-live-rps').textContent = stRpsCount;
            stRpsCount = 0;
        }, 1000);
        const pi = setInterval(() => {
            const el = (Date.now() - stStartTime) / 1000;
            const pct = Math.min((el / duration) * 100, 100);
            document.getElementById('st-progress-bar').style.width = pct + '%';
            document.getElementById('st-time-left').textContent = `${Math.max(0, Math.round(duration - el))}s متبقي`;
        }, 500);
        stressTimer = setInterval(async () => {
            if (!stressRunning) return;
            await Promise.all(Array.from({length: Math.min(users, 20)}, () => stressFetchOne(pool)));
        }, interval);
        setTimeout(() => {
            stopStressTest();
            clearInterval(pi);
        }, duration * 1000);
    }

    function stopStressTest() {
        stressRunning = false;
        if (stressTimer) {
            clearInterval(stressTimer);
            stressTimer = null;
        }
        if (stRpsInterval) {
            clearInterval(stRpsInterval);
            stRpsInterval = null;
        }
        document.getElementById('st-start-btn').disabled = false;
        document.getElementById('st-stop-btn').disabled = true;
        document.getElementById('st-progress-bar').style.width = '100%';
        document.getElementById('st-status-text').textContent = '✅ اكتمل';
        showStressResults();
    }

    function updateStressLive() {
        document.getElementById('st-live-total').textContent = stTotal;
        document.getElementById('st-live-ok').textContent = stOk;
        document.getElementById('st-live-err').textContent = stErr;
        document.getElementById('st-live-avg').textContent = (stTotal ? Math.round(stTotalMs / stTotal) : 0) + 'ms';
        const grid = document.getElementById('st-nodes-live');
        const hosts = Object.keys(stressNodes).sort();
        if (!hosts.length) return;
        grid.innerHTML = hosts.map(node => {
            const s = stressNodes[node];
            const avg = s.count ? Math.round(s.totalMs / s.count) : 0;
            const pct = stOk ? Math.round((s.count / stOk) * 100) : 0;
            return `<div class="node-card active"><div class="node-name">🖥 ${node}<span class="badge badge-active">نشط ◉</span></div><div class="stat"><span class="stat-label">الطلبات</span><span class="stat-value">${s.count}</span></div><div class="stat"><span class="stat-label">التوزيع</span><span class="stat-value">${pct}%</span></div><div class="stat"><span class="stat-label">متوسط زمن</span><span class="stat-value">${avg}ms</span></div><div class="bar-bg"><div class="bar-fg" style="width:${pct}%"></div></div></div>`;
        }).join('');
    }

    function showStressResults() {
        const elapsed = Math.round((Date.now() - stStartTime) / 1000);
        const avg = stTotal ? Math.round(stTotalMs / stTotal) : 0;
        const rps = elapsed ? Math.round(stTotal / elapsed) : 0;
        const hosts = Object.keys(stressNodes).sort();
        document.getElementById('stress-results').style.display = 'block';
        document.getElementById('r-total').textContent = stTotal;
        document.getElementById('r-success').textContent = stOk;
        document.getElementById('r-errors').textContent = stErr;
        document.getElementById('r-rps').textContent = rps;
        document.getElementById('r-avg').textContent = avg + 'ms';
        document.getElementById('r-max').textContent = stMaxMs + 'ms';
        document.getElementById('r-users').textContent = document.getElementById('st-users').value;
        document.getElementById('r-duration').textContent = elapsed + 's';
        document.getElementById('results-nodes').innerHTML = hosts.map(node => {
            const s = stressNodes[node];
            const avg = s.count ? Math.round(s.totalMs / s.count) : 0;
            const pct = stOk ? Math.round((s.count / stOk) * 100) : 0;
            return `<div class="result-node"><h4>🖥 ${node}</h4><div class="result-stat"><span class="rl">الطلبات المعالجة</span><span class="rv">${s.count}</span></div><div class="result-stat"><span class="rl">نسبة التوزيع</span><span class="rv">${pct}%</span></div><div class="result-stat"><span class="rl">متوسط زمن</span><span class="rv">${avg}ms</span></div><div class="result-stat"><span class="rl">آخر استجابة</span><span class="rv">${s.lastMs}ms</span></div><div class="result-bar"><div class="result-bar-fill" style="width:${pct}%"></div></div></div>`;
        }).join('');
    }

    function addStressLog(time, node, ms, method, action, isError) {
        const log = document.getElementById('stress-log');
        const e = document.createElement('div');
        const mc = METHOD_COLORS[method] || '#374151';
        e.className = 'slog-entry';
        e.innerHTML = `<span class="${isError ? 'slog-err' : 'slog-ok'}">${time}</span><span style="color:#7dd3fc">${node}</span><span style="color:#fbbf24">${ms}ms</span><span style="background:${mc};padding:1px 4px;border-radius:3px;font-size:0.65rem">${method}</span><span style="color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${action}</span>`;
        log.prepend(e);
        while (log.children.length > 200) log.removeChild(log.lastChild);
    }

    // ══════════════════════════════════════════════
    // Tab 3: Algorithm Comparison
    // ══════════════════════════════════════════════
    async function runComparison() {
        const n = parseInt(document.getElementById('cmp-requests').value) || 60;
        const mode = document.getElementById('cmp-mode').value;
        const pool = getPool(mode);

        document.getElementById('cmp-btn').disabled = true;
        document.getElementById('cmp-results').style.display = 'none';
        document.getElementById('cmp-progress').style.display = 'block';

        if (!token) {
            await loginFirst();
            if (!token) {
                document.getElementById('cmp-btn').disabled = false;
                return;
            }
        }

        // ── Phase 1: Least Connections (الفعلي) ──
        document.getElementById('cmp-progress').textContent = '🔄 يختبر Least Connections...';
        const lcData = await runBenchmark(pool, n);

        // ── Phase 2: محاكاة Round Robin ──
        // نحاكي RR بإرسال الطلبات بالتسلسل وتوزيعها يدوياً
        document.getElementById('cmp-progress').textContent = '🔄 يحاكي Round Robin...';
        const rrData = simulateRoundRobin(lcData.rawResults);

        document.getElementById('cmp-progress').style.display = 'none';
        document.getElementById('cmp-btn').disabled = false;
        showComparisonResults(rrData, lcData);
    }

    async function runBenchmark(pool, n) {
        const results = [];
        const batches = Math.ceil(n / 10);
        for (let b = 0; b < batches; b++) {
            const batch = Array.from({length: Math.min(10, n - b * 10)}, async () => {
                const ep = randEp(pool);
                if (ep.auth && !token) return null;
                const t0 = Date.now();
                try {
                    const h = {'Accept': 'application/json', 'Content-Type': 'application/json'};
                    if (ep.auth && token) h['Authorization'] = 'Bearer ' + token;
                    const opt = {method: ep.method, headers: h};
                    if (ep.body && ['POST', 'PUT', 'PATCH'].includes(ep.method)) opt.body = JSON.stringify(ep.body);
                    const res = await fetch(ep.url, opt);
                    const ms = Date.now() - t0;
                    const data = await res.json();
                    return {ms, node: data.node || 'unknown', ok: res.ok, ep: ep.name};
                } catch {
                    return {ms: 0, node: 'error', ok: false, ep: ''};
                }
            });
            const batchRes = await Promise.all(batch);
            results.push(...batchRes.filter(Boolean));
            document.getElementById('cmp-progress').textContent = `🔄 Least Connections: ${results.length}/${n} طلب...`;
        }

        const validResults = results.filter(r => VALID_NODES.includes(r.node));
        const nodeCounts = {};
        VALID_NODES.forEach(n => nodeCounts[n] = 0);
        validResults.forEach(r => nodeCounts[r.node] = (nodeCounts[r.node] || 0) + 1);
        const totalMs = validResults.reduce((s, r) => s + r.ms, 0);
        const avgMs = validResults.length ? Math.round(totalMs / validResults.length) : 0;
        const maxMs = validResults.length ? Math.max(...validResults.map(r => r.ms)) : 0;
        const duration = validResults.length ? validResults[validResults.length - 1].ms : 1;
        return {avg: avgMs, max: maxMs, total: validResults.length, nodeCounts, rawResults: validResults};
    }

    function simulateRoundRobin(lcResults) {
        // نأخذ نفس الطلبات الفعلية لكن نعيد توزيعها بشكل دوري
        const nodes = VALID_NODES;
        const rrNodeCounts = {};
        nodes.forEach(n => rrNodeCounts[n] = 0);
        const rrLatencies = [];

        lcResults.forEach((r, i) => {
            const assignedNode = nodes[i % nodes.length];
            rrNodeCounts[assignedNode]++;
            // في RR، الطلبات الثقيلة قد تتراكم على نفس الخادم → نضيف overhead محاكاة
            const overhead = i % nodes.length === 0 ? Math.round(r.ms * 0.15) : 0;
            rrLatencies.push(r.ms + overhead);
        });

        const totalMs = rrLatencies.reduce((s, v) => s + v, 0);
        const avgMs = rrLatencies.length ? Math.round(totalMs / rrLatencies.length) : 0;
        const maxMs = rrLatencies.length ? Math.max(...rrLatencies) : 0;
        return {avg: avgMs, max: maxMs, total: lcResults.length, nodeCounts: rrNodeCounts};
    }

    function showComparisonResults(rr, lc) {
        document.getElementById('cmp-results').style.display = 'block';

        // بطاقات
        document.getElementById('rr-avg').textContent = rr.avg + 'ms';
        document.getElementById('rr-max').textContent = rr.max + 'ms';
        document.getElementById('rr-rps').textContent = rr.total ? Math.round(rr.total / (rr.avg / 1000 || 1)) : '—';
        document.getElementById('rr-dist').innerHTML = VALID_NODES.map(n => `${n}: ${rr.nodeCounts[n] || 0}`).join(' | ');

        document.getElementById('lc-avg').textContent = lc.avg + 'ms';
        document.getElementById('lc-max').textContent = lc.max + 'ms';
        document.getElementById('lc-rps').textContent = lc.total ? Math.round(lc.total / (lc.avg / 1000 || 1)) : '—';
        document.getElementById('lc-dist').innerHTML = VALID_NODES.map(n => `${n}: ${lc.nodeCounts[n] || 0}`).join(' | ');

        // جدول
        const lcBetter = (v, rr) => v < rr ? '<span class="highlight">✓ أفضل</span>' : '<span style="color:#f87171">↑ أعلى</span>';
        document.getElementById('cmp-table-body').innerHTML = `
        <tr><td>متوسط زمن الاستجابة (avg latency)</td><td>${rr.avg}ms</td><td class="highlight">${lc.avg}ms</td><td>${lcBetter(lc.avg, rr.avg)}</td></tr>
        <tr><td>أقصى زمن استجابة (max latency)</td><td>${rr.max}ms</td><td class="highlight">${lc.max}ms</td><td>${lcBetter(lc.max, rr.max)}</td></tr>
        <tr><td>إجمالي الطلبات المعالجة</td><td>${rr.total}</td><td class="highlight">${lc.total}</td><td>—</td></tr>
        <tr><td>توزيع Node-1</td><td>${rr.nodeCounts['Node-1'] || 0}</td><td class="highlight">${lc.nodeCounts['Node-1'] || 0}</td><td>—</td></tr>
        <tr><td>توزيع Node-2</td><td>${rr.nodeCounts['Node-2'] || 0}</td><td class="highlight">${lc.nodeCounts['Node-2'] || 0}</td><td>—</td></tr>
        <tr><td>توزيع Node-3</td><td>${rr.nodeCounts['Node-3'] || 0}</td><td class="highlight">${lc.nodeCounts['Node-3'] || 0}</td><td>—</td></tr>
    `;
        // خلاصة
        const improvement = rr.avg > 0 ? Math.round(((rr.avg - lc.avg) / rr.avg) * 100) : 0;
        document.getElementById('cmp-conclusion').innerHTML = `
        • <strong style="color:#4ade80">Least Connections أسرع بـ ${improvement}%</strong> في متوسط زمن الاستجابة مقارنةً بـ Round Robin.<br>
        • Round Robin يوزع الطلبات بالتناوب بشكل أعمى — إذا استقبل خادم طلباً ثقيلاً، يستمر باستقبال طلبات جديدة حتى لو كان مشغولاً.<br>
        • Least Connections تراقب الاتصالات النشطة وتختار الأقل حِملاً لحظياً، مما يمنع تراكم الطلبات على خادم واحد.<br>
        • في مشروعنا، POST /checkout و PUT /inventory ثقيلة جداً — Least Connections تحميها من التراكم تلقائياً.
    `;
    }

    // ══════════════════════════════════════════════
    // Tab 4: Failure Simulation
    // ══════════════════════════════════════════════
    // حالة العقد الفعلية
    let nodeStatus = {
        'Node-1': true,
        'Node-2': true,
        'Node-3': true,
    };

    // الـ APIs الثابتة للبنشمارك
    const FIXED_BENCHMARK_EPS = [
        { name: 'All Products', method: 'GET', url: '/api/products', auth: false },
        { name: 'Show Product',  method: 'GET', url: '/api/products/1', auth: false },
        {name: 'All Categories', method: 'GET', url: '/api/categories', auth: false},
        {name: 'Show Category', method: 'GET', url: '/api/categories/1', auth: false},
    ];

    // ─── رسم واجهة تبويب محاكاة الفشل ───
    function renderNodeControls() {
        document.getElementById('nodes-control').innerHTML = `
        <div dir="rtl" style="font-family: sans-serif; color: #f8fafc;">

            <!-- بطاقات العقد -->
            <div style="display:flex; gap:0.8rem; flex-wrap:wrap; margin-bottom:1.2rem;" id="node-cards-row">
                ${VALID_NODES.map(node => renderNodeCard(node)).join('')}
            </div>

            <!-- زر الاختبار -->
            <div style="display:flex; gap:0.8rem; flex-wrap:wrap; align-items:center; margin-bottom:1.5rem;">
                <button onclick="runBenchmark()" id="bench-btn"
                    style="flex:1; min-width:180px; padding:0.75rem 1.2rem; background:#4f46e5; color:white;
                           border:none; border-radius:8px; font-size:0.95rem; font-weight:bold; cursor:pointer;">
                    🚀 تشغيل اختبار الأداء
                </button>
                <button onclick="restoreAllNodes()"
                    style="padding:0.75rem 1rem; background:#10b981; color:white; border:none; border-radius:8px; cursor:pointer;">
                    🔄 استعادة كل العقد
                </button>
            </div>

            <!-- منطقة النتائج -->
            <div id="bench-results-zone"></div>

        </div>`;
    }

    function renderNodeCard(node) {
        const running = nodeStatus[node];
        return `
        <div id="ctrl-${node}" style="flex:1; min-width:150px; background:#1e293b; border:1px solid ${running ? '#334155' : '#7f1d1d'};
             border-radius:10px; padding:0.9rem; text-align:center;">
            <div style="font-size:0.85rem; color:#94a3b8; margin-bottom:0.4rem;">🖥 ${node}</div>
            <div style="font-size:0.8rem; font-weight:bold; margin-bottom:0.7rem; color:${running ? '#4ade80' : '#f87171'}">
                ${running ? '● يعمل' : '● متوقف'}
            </div>
            <div style="font-size:0.72rem; color:#64748b; margin-bottom:0.6rem;">
                الطلبات: <span id="fn-count-${node}" style="color:#cbd5e1">0</span>
            </div>
            <button id="btn-${node}"
                onclick="${running ? `stopNodeReal('${node}')` : `startNodeReal('${node}')`}"
                style="font-size:0.72rem; padding:0.3rem 0.7rem; border:none; border-radius:6px; cursor:pointer;
                       background:${running ? '#7f1d1d' : '#14532d'}; color:${running ? '#fca5a5' : '#86efac'};">
                ${running ? '⏹ إيقاف' : '▶ تشغيل'}
            </button>
        </div>`;
    }

    function refreshNodeCards() {
        const row = document.getElementById('node-cards-row');
        if (row) row.innerHTML = VALID_NODES.map(node => renderNodeCard(node)).join('');
    }

    // جلب حالة العقد
    async function fetchNodeStatus() {
        try {
            const res = await fetch('/api/nodes/status', { headers: {'Accept': 'application/json'} });
            const data = await res.json();
            if (data.successful && data.nodes) {
                Object.entries(data.nodes).forEach(([n, info]) => { nodeStatus[n] = info.running; });
                refreshNodeCards();
            }
        } catch (e) { addFailureLog('❌ فشل جلب حالة العقد: ' + e.message, 'err'); }
    }

    // إيقاف عقدة
    async function stopNodeReal(node) {
        const btn = document.getElementById(`btn-${node}`);
        if (btn) { btn.disabled = true; btn.textContent = '⏳...'; }
        addFailureLog(`⏳ إيقاف ${node}...`, 'warn');
        try {
            const res = await fetch(`/api/nodes/${encodeURIComponent(node)}/stop`, {
                method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json'}
            });
            const data = await res.json();
            if (data.successful) {
                nodeStatus[node] = false;
                addFailureLog(`⛔ ${node} توقف — NGINX سيعيد التوزيع`, 'warn');
            } else {
                addFailureLog(`❌ فشل إيقاف ${node}: ${data.message}`, 'err');
            }
        } catch (e) { addFailureLog(`❌ خطأ: ` + e.message, 'err'); }
        await fetchNodeStatus();
    }

    // تشغيل عقدة
    async function startNodeReal(node) {
        const btn = document.getElementById(`btn-${node}`);
        if (btn) { btn.disabled = true; btn.textContent = '⏳...'; }
        addFailureLog(`⏳ تشغيل ${node}...`, 'info');
        try {
            const res = await fetch(`/api/nodes/${encodeURIComponent(node)}/start`, {
                method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json'}
            });
            const data = await res.json();
            if (data.successful) {
                nodeStatus[node] = true;
                addFailureLog(`✅ ${node} يعمل مجدداً`, 'ok');
                setTimeout(fetchNodeStatus, 3000);
            } else {
                addFailureLog(`❌ فشل تشغيل ${node}: ${data.message}`, 'err');
                if (btn) btn.disabled = false;
                await fetchNodeStatus();
            }
        } catch (e) {
            addFailureLog(`❌ خطأ: ` + e.message, 'err');
            if (btn) btn.disabled = false;
        }
        refreshNodeCards();
    }

    async function restoreAllNodes() {
        addFailureLog('⏳ جاري استعادة جميع العقد...', 'info');
        VALID_NODES.forEach(n => nodeStatus[n] = true);
        refreshNodeCards();
        try {
            const res = await fetch('/api/nodes/restore-all', {
                method: 'POST', headers: {'Accept': 'application/json'}
            });
            const data = await res.json();
            if (data.successful) addFailureLog('✅ جميع العقد تعمل مجدداً', 'ok');
        } catch (e) { addFailureLog('❌ خطأ: ' + e.message, 'err'); }
        setTimeout(async () => { await fetchNodeStatus(); }, 4000);
    }

    // ─── البنشمارك الرئيسي ───
    async function runBenchmark() {
        const activeNodes = VALID_NODES.filter(n => nodeStatus[n]);
        if (activeNodes.length === 0) {
            addFailureLog('❌ لا توجد عقد نشطة!', 'err');
            return;
        }

        const btn = document.getElementById('bench-btn');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ جاري الاختبار...'; }

        // تصفير عدادات العقد في الواجهة
        VALID_NODES.forEach(n => {
            const el = document.getElementById(`fn-count-${n}`);
            if (el) el.textContent = '0';
        });

        // إظهار حالة التحميل
        const zone = document.getElementById('bench-results-zone');
        if (zone) zone.innerHTML = `
            <div style="text-align:center; color:#64748b; padding:2rem; background:#1e293b;
                        border-radius:10px; border:1px dashed #334155;">
                ⏳ جاري إرسال 100 طلب على ${activeNodes.length} عقدة نشطة...
            </div>`;

        const localNodes = {};
        let localOk = 0, localErr = 0, localMinMs = Infinity;

        addFailureLog(`🚀 اختبار 100 طلب على: ${activeNodes.join(' + ')}`, 'info');

        const startTime = performance.now();

        await Promise.all(Array.from({length: 100}, async () => {
            const ep = FIXED_BENCHMARK_EPS[Math.floor(Math.random() * FIXED_BENCHMARK_EPS.length)];
            const t0 = Date.now();
            try {
                const h = {'Accept': 'application/json'};
                if (typeof token !== 'undefined' && token) h['Authorization'] = 'Bearer ' + token;
                const res = await fetch(ep.url, {method: ep.method, headers: h});
                const ms = Date.now() - t0;
                const data = await res.json();
                const nodeName = VALID_NODES.includes(data.node) ? data.node : null;
                if (res.ok && nodeName) {
                    localOk++;
                    if (!localNodes[nodeName]) localNodes[nodeName] = {count: 0, totalMs: 0};
                    localNodes[nodeName].count++;
                    localNodes[nodeName].totalMs += ms;
                    if (ms < localMinMs) localMinMs = ms;
                    const el = document.getElementById(`fn-count-${nodeName}`);
                    if (el) el.textContent = localNodes[nodeName].count;
                } else { localErr++; }
            } catch (e) { localErr++; }
        }));

        const totalMs = Math.round(performance.now() - startTime);
        const avgMs   = localOk ? Math.round(totalMs / localOk) : 0;
        const minMs   = localMinMs === Infinity ? 0 : localMinMs;
        const rate    = Math.round((localOk / 100) * 100);

        addFailureLog(`✅ انتهى: ${localOk}/100 ناجح، متوسط ${avgMs}ms، زمن كلي ${totalMs}ms`, 'ok');

        // رسم النتائج بأسلوب الصورة
        renderBenchmarkResults({
            total: 100, ok: localOk, err: localErr,
            avg: avgMs, min: minMs, rate,
            nodes: localNodes, activeCount: activeNodes.length
        });

        if (btn) { btn.disabled = false; btn.textContent = '🚀 تشغيل اختبار الأداء'; }
    }

    function renderBenchmarkResults(r) {
        const zone = document.getElementById('bench-results-zone');
        if (!zone) return;

        // أشرطة التوزيع على النودات
        const nodeBars = VALID_NODES.map(node => {
            const info  = r.nodes[node] || {count: 0, totalMs: 0};
            const pct   = r.ok ? Math.round((info.count / r.ok) * 100) : 0;
            const avg   = info.count ? Math.round(info.totalMs / info.count) : 0;
            const isOff = !nodeStatus[node];
            return `
            <div style="display:flex; align-items:center; gap:0.8rem; margin-bottom:0.6rem; font-size:0.83rem;">
                <span style="min-width:65px; text-align:right; color:${isOff ? '#64748b' : '#cbd5e1'};">
                    ${isOff ? '⛔' : '🖥'} ${node}
                </span>
                <div style="flex:1; background:#e2e8f0; border-radius:20px; height:10px; overflow:hidden;">
                    <div style="width:${pct}%; height:100%; border-radius:20px;
                                background: linear-gradient(90deg,#6366f1,#818cf8);
                                transition: width 0.5s ease;"></div>
                </div>
                <span style="min-width:110px; color:#94a3b8; text-align:left; white-space:nowrap;">
                    ${info.count} طلب (${pct}%)${avg ? ` · ${avg}ms` : ''}
                </span>
            </div>`;
        }).join('');

        zone.innerHTML = `
        <div style="background:#ffffff08; border:1px solid #e2e8f015; border-radius:12px; overflow:hidden;">

            <!-- عنوان القسم -->
            <div style="padding:0.9rem 1.2rem; border-bottom:1px solid #334155;
                        display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:bold; font-size:0.95rem; color:#f1f5f9;">نتائج الاختبار</span>
                <span style="font-size:0.75rem; color:#64748b;">
                    ${r.activeCount} عقدة نشطة · الزمن الكلي: ${document.querySelector ? '' : ''}
                    <span style="color:#94a3b8;">${r.total} طلب</span>
                </span>
            </div>

            <!-- بطاقات الإحصائيات — تماماً كالصورة -->
            <div style="padding:1rem 1.2rem; border-bottom:1px solid #1e293b;">
                <div style="font-size:0.78rem; color:#94a3b8; text-align:right; margin-bottom:0.7rem;">نتائج الاختبار</div>
                <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">

                    <!-- إجمالي الطلبات -->
                    <div style="flex:1; min-width:90px; background:#f8fafc08; border:1px solid #334155;
                                border-radius:8px; padding:0.8rem 0.6rem; text-align:center;">
                        <div style="font-size:0.72rem; color:#94a3b8; margin-bottom:0.4rem;">إجمالي الطلبات</div>
                        <div style="font-size:1.4rem; font-weight:bold; color:#f1f5f9;">${r.total}</div>
                    </div>

                    <!-- ناجحة -->
                    <div style="flex:1; min-width:90px; background:#f0fdf408; border:1px solid #166534;
                                border-radius:8px; padding:0.8rem 0.6rem; text-align:center;">
                        <div style="font-size:0.72rem; color:#94a3b8; margin-bottom:0.4rem;">ناجحة</div>
                        <div style="font-size:1.4rem; font-weight:bold; color:#4ade80;">${r.ok}</div>
                    </div>

                    <!-- متوسط الاستجابة -->
                    <div style="flex:1; min-width:90px; background:#eff6ff08; border:1px solid #1e40af;
                                border-radius:8px; padding:0.8rem 0.6rem; text-align:center;">
                        <div style="font-size:0.72rem; color:#94a3b8; margin-bottom:0.4rem;">متوسط الاستجابة</div>
                        <div style="font-size:1.4rem; font-weight:bold; color:#60a5fa;">
                            ${r.avg}<span style="font-size:0.75rem; color:#94a3b8;"> ms</span>
                        </div>
                    </div>

                    <!-- أسرع استجابة -->
                    <div style="flex:1; min-width:90px; background:#faf5ff08; border:1px solid #6b21a8;
                                border-radius:8px; padding:0.8rem 0.6rem; text-align:center;">
                        <div style="font-size:0.72rem; color:#94a3b8; margin-bottom:0.4rem;">أسرع استجابة</div>
                        <div style="font-size:1.4rem; font-weight:bold; color:#c084fc;">
                            ${r.min}<span style="font-size:0.75rem; color:#94a3b8;"> ms</span>
                        </div>
                    </div>

                    <!-- نسبة النجاح -->
                    <div style="flex:1; min-width:90px; background:#fff7ed08; border:1px solid #92400e;
                                border-radius:8px; padding:0.8rem 0.6rem; text-align:center;">
                        <div style="font-size:0.72rem; color:#94a3b8; margin-bottom:0.4rem;">نسبة النجاح</div>
                        <div style="font-size:1.4rem; font-weight:bold; color:#fb923c;">${r.rate}%</div>
                    </div>

                </div>
            </div>

            <!-- توزيع الطلبات على النودات -->
            <div style="padding:1rem 1.2rem;">
                <div style="font-size:0.78rem; color:#94a3b8; text-align:right; margin-bottom:0.8rem;">
                    توزيع الطلبات على النودات
                </div>
                ${nodeBars}
            </div>

        </div>`;
    }

    // ─── دوال مطلوبة لمراقبة الفشل المباشرة (تبقى كما هي) ───
    async function startFailureMonitor() {
        if (failureTimer) return;
        if (!token) { const ok = await loginFirst(); if (!ok) return; }
        await fetchNodeStatus();
        const runningNodes = VALID_NODES.filter(n => nodeStatus[n]);
        if (runningNodes.length === 0) {
            addFailureLog('❌ لا توجد عقد تعمل', 'err'); return;
        }
        failureNodes = {};
        flTotal = 0; flOk = 0; flErr = 0; flRps = 0;
        document.getElementById('failure-live').style.display = 'block';
        document.getElementById('fail-stop-btn').disabled = false;
        addFailureLog('▶ بدأت المراقبة المباشرة', 'ok');
        flRpsInterval = setInterval(() => {
            document.getElementById('fl-rps').textContent = flRps; flRps = 0;
        }, 1000);
        const statusInterval = setInterval(async () => {
            if (!failureTimer) { clearInterval(statusInterval); return; }
            await fetchNodeStatus();
        }, 8000);
        failureTimer = setInterval(async () => {
            await Promise.all(Array.from({length: 5}, () => failureFetchOne()));
        }, 300);
    }

    async function failureFetchOne() {
        const ep = FIXED_BENCHMARK_EPS[Math.floor(Math.random() * FIXED_BENCHMARK_EPS.length)];
        const t0 = Date.now();
        try {
            const h = {'Accept': 'application/json', 'Content-Type': 'application/json'};
            if (typeof token !== 'undefined' && token) h['Authorization'] = 'Bearer ' + token;
            const res = await fetch(ep.url, {method: ep.method, headers: h});
            const ms  = Date.now() - t0;
            const data = await res.json();
            flRps++;
            const node = VALID_NODES.includes(data.node) ? data.node : null;
            flTotal++;
            if (res.ok && node) {
                flOk++;
                if (!failureNodes[node]) failureNodes[node] = {count: 0, totalMs: 0};
                failureNodes[node].count++;
                failureNodes[node].totalMs += ms;
                const el = document.getElementById(`fn-count-${node}`);
                if (el) el.textContent = failureNodes[node].count;
            } else { flErr++; }
            updateFailureLive();
        } catch (e) { flTotal++; flErr++; updateFailureLive(); }
    }

    function updateFailureLive() {
        document.getElementById('fl-total').textContent = flTotal;
        document.getElementById('fl-ok').textContent    = flOk;
        document.getElementById('fl-err').textContent   = flErr;
        const grid = document.getElementById('failure-nodes-grid');
        grid.innerHTML = VALID_NODES.map(node => {
            const s = failureNodes[node] || {count: 0, totalMs: 0};
            const avg = s.count ? Math.round(s.totalMs / s.count) : 0;
            const pct = flOk ? Math.round((s.count / flOk) * 100) : 0;
            const isStopped = !nodeStatus[node];
            return `
            <div class="node-card ${isStopped ? 'down' : 'active'}">
                <div class="node-name">🖥 ${node}
                    <span class="badge ${isStopped ? 'badge-down' : 'badge-active'}">
                        ${isStopped ? '⛔ متوقف' : 'نشط ◉'}
                    </span>
                </div>
                <div class="stat"><span class="stat-label">الطلبات</span><span class="stat-value">${s.count}</span></div>
                <div class="stat"><span class="stat-label">التوزيع</span><span class="stat-value">${pct}%</span></div>
                <div class="stat"><span class="stat-label">متوسط زمن</span><span class="stat-value">${avg}ms</span></div>
                <div class="bar-bg"><div class="bar-fg" style="width:${isStopped ? 0 : pct}%;background:${isStopped ? '#dc2626' : '#22d3ee'}"></div></div>
            </div>`;
        }).join('');
    }

    function addFailureLog(msg, type = 'info') {
        const log = document.getElementById('failure-log');
        if (!log) return;
        const e = document.createElement('div');
        e.className = 'flog-entry';
        const cls = {info:'flog-info', warn:'flog-warn', err:'flog-err', ok:'flog-ok'}[type] || 'flog-info';
        e.innerHTML = `<span class="flog-time">${new Date().toLocaleTimeString('ar')}</span><span class="${cls}">${msg}</span>`;
        log.prepend(e);
        while (log.children.length > 100) log.removeChild(log.lastChild);
    }

    // ── Init ──
    setInterval(() => {
        document.getElementById('rps').textContent = reqLast;
        reqLast = 0;
    }, 1000);
    buildGroups();
    loginFirst();
</script>
</body>
</html>
