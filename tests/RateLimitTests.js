import http from 'k6/http';
import { sleep, check, group } from 'k6';
import { Counter, Trend, Rate } from 'k6/metrics';

// ─── Base URL ────────────────────────────────────────────────────────────────
const BASE_URL = 'http://127.0.0.1:8000/api';

// ─── Custom Metrics ───────────────────────────────────────────────────────────
const throttledRequests  = new Counter('throttled_requests_429');
const rateLimitRemaining = new Trend('rate_limit_remaining');
const rateLimitReset     = new Trend('rate_limit_reset_seconds');
const successRate        = new Rate('success_rate');

// ─── Rate Limit Headers to Monitor ───────────────────────────────────────────
// Laravel + Redis emits these headers on every response:
//   X-RateLimit-Limit     → max requests allowed in the window
//   X-RateLimit-Remaining → how many requests left before throttling
//   Retry-After           → seconds to wait after a 429 (only on throttled responses)
//   X-RateLimit-Reset     → Unix timestamp when the window resets (not always present)
function captureRateLimitHeaders(res, label) {
  const limit     = res.headers['X-Ratelimit-Limit'];
  const remaining = res.headers['X-Ratelimit-Remaining'];
  const retryAfter = res.headers['Retry-After'];
  const reset     = res.headers['X-Ratelimit-Reset'];

  if (remaining !== undefined) rateLimitRemaining.add(parseInt(remaining));
  if (reset     !== undefined) rateLimitReset.add(parseInt(reset) - Math.floor(Date.now() / 1000));

  if (__ENV.VERBOSE === 'true') {
    console.log(`[${label}] Status: ${res.status} | Limit: ${limit} | Remaining: ${remaining} | Retry-After: ${retryAfter ?? 'N/A'}`);
  }

  return { limit, remaining, retryAfter, reset };
}

// ─── Shared Headers ───────────────────────────────────────────────────────────
function jsonHeaders(token = null) {
  const h = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
  if (token) h['Authorization'] = `Bearer ${token}`;
  return h;
}

// ─── Auth Helper ──────────────────────────────────────────────────────────────
function loginUser(email, password) {
  const res = http.post(
    `${BASE_URL}/login`,
    JSON.stringify({ email, password }),
    { headers: jsonHeaders() }
  );

  if (res.status !== 200) {
    console.error(`Login failed for ${email}: ${res.status} - ${res.body}`);
    return null;
  }

  return res.json('token') || res.json('data.token') || res.json('access_token');
}

// ─────────────────────────────────────────────────────────────────────────────
//  TEST SCENARIOS
//  Each scenario maps to a specific rate limiter in your AppServiceProvider
// ─────────────────────────────────────────────────────────────────────────────
export const options = {
  scenarios: {

    // ── 1. public_api ─────────────────────────────────────────────
    // Limit: 120/min per IP
    // Goal:  sustained traffic just under + burst above the limit
    public_api: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '15s', target: 30 },   // ramp up
        { duration: '30s', target: 80 },   // hold — well under 120/min
        { duration: '15s', target: 140 },  // spike above limit → expect 429s
        { duration: '10s', target: 0 },    // cool down
      ],
      exec: 'publicApiScenario',
      tags: { scenario: 'public_api' },
    },

    // ── 2. login ─────────────────────────────────────────────────
    // Limit: 5/min per email+IP
    // Goal:  quickly hit the 5-attempt cap to verify lockout
    login_bruteforce: {
      executor: 'constant-vus',
      vus: 3,
      duration: '30s',
      startTime: '70s', // run after public_api
      exec: 'loginScenario',
      tags: { scenario: 'login' },
    },

    // // ── 3. register ──────────────────────────────────────────────
    // // Limit: 3/min per IP
    // // Goal:  verify registrations beyond 3 are blocked
    // register_spam: {
    //   executor: 'constant-vus',
    //   vus: 2,
    //   duration: '20s',
    //   startTime: '105s',
    //   exec: 'registerScenario',
    //   tags: { scenario: 'register' },
    // },

    // // ── 4. cart ──────────────────────────────────────────────────
    // // Limit: 60/min per user
    // // Goal:  normal cart usage + burst to trigger throttle
    // cart_operations: {
    //   executor: 'ramping-vus',
    //   startVUs: 0,
    //   stages: [
    //     { duration: '10s', target: 10 },
    //     { duration: '30s', target: 40 },  // normal: ~40 req/s × 1 VU = fine
    //     { duration: '15s', target: 80 },  // burst → expect 429s
    //     { duration: '5s',  target: 0 },
    //   ],
    //   startTime: '130s',
    //   exec: 'cartScenario',
    //   tags: { scenario: 'cart' },
    // },

    // // ── 5. checkout ───────────────────────────────────────────────
    // // Limit: 5/min per user
    // // Goal:  confirm only 5 checkouts pass per minute
    // checkout_spam: {
    //   executor: 'constant-vus',
    //   vus: 3,
    //   duration: '30s',
    //   startTime: '195s',
    //   exec: 'checkoutScenario',
    //   tags: { scenario: 'checkout' },
    // },

    // // ── 6. wallet top-up ─────────────────────────────────────────
    // // Limit: 3/min per user
    // // Goal:  verify only 3 top-ups allowed per minute
    // wallet_topup: {
    //   executor: 'constant-vus',
    //   vus: 2,
    //   duration: '20s',
    //   startTime: '230s',
    //   exec: 'walletScenario',
    //   tags: { scenario: 'wallet' },
    // },

    // // ── 7. inventory update ───────────────────────────────────────
    // // Limit: 20/min per user
    // // Goal:  admin-level endpoint, moderate burst test
    // inventory_update: {
    //   executor: 'constant-vus',
    //   vus: 5,
    //   duration: '30s',
    //   startTime: '255s',
    //   exec: 'inventoryScenario',
    //   tags: { scenario: 'inventory' },
    // },

    // // ── 8. admin actions ─────────────────────────────────────────
    // // Limit: 10/min per user
    // // Goal:  verify admin order status updates are capped
    // admin_actions: {
    //   executor: 'constant-vus',
    //   vus: 3,
    //   duration: '30s',
    //   startTime: '290s',
    //   exec: 'adminScenario',
    //   tags: { scenario: 'admin' },
    // },

  },

  thresholds: {
    // Overall health
    http_req_duration:       ['p(95)<1000'],   // 95% of requests under 1s
    success_rate:            ['rate>0.5'],      // at least 50% success (we intentionally trigger 429s)
    throttled_requests_429:  ['count>0'],       // we EXPECT to see some 429s — test is invalid without them

    // Per-scenario response time
    'http_req_duration{scenario:public_api}':  ['p(95)<800'],
    'http_req_duration{scenario:cart}':        ['p(95)<800'],
    'http_req_duration{scenario:checkout}':    ['p(95)<1000'],
  },
};

// ─────────────────────────────────────────────────────────────────────────────
//  SETUP — runs once, returns shared data passed to every VU
// ─────────────────────────────────────────────────────────────────────────────
export function setup() {
  console.log('=== K6 Laravel Rate Limit Test Suite ===');
  console.log('Logging in test users...');

  // ⚠️  Replace these with real seeded users in your DB
  const regularToken = loginUser('user@example.com', 'password');
  const adminToken   = loginUser('admin@example.com', 'password');

  if (!regularToken) console.warn('⚠️  Regular user login failed — authenticated tests will be skipped');
  if (!adminToken)   console.warn('⚠️  Admin user login failed — admin tests will be skipped');

  // Grab a product ID for cart tests
  const productsRes = http.get(`${BASE_URL}/products`, { headers: jsonHeaders() });
  const productId   = productsRes.status === 200
    ? (productsRes.json('data.0.id') || productsRes.json('0.id') || 1)
    : 1;

  // Grab an order ID for admin tests
  let orderId = 1;
  if (adminToken) {
    const ordersRes = http.get(`${BASE_URL}/orders`, { headers: jsonHeaders(adminToken) });
    if (ordersRes.status === 200) {
      orderId = ordersRes.json('data.0.id') || ordersRes.json('0.id') || 1;
    }
  }

  console.log(`Product ID for cart tests: ${productId}`);
  console.log(`Order ID for admin tests:  ${orderId}`);

  return { regularToken, adminToken, productId, orderId };
}

// ─────────────────────────────────────────────────────────────────────────────
//  SCENARIO FUNCTIONS
// ─────────────────────────────────────────────────────────────────────────────

// ── 1. Public API (products + categories) ────────────────────────────────────
export function publicApiScenario() {
  group('public_api', () => {
    const endpoints = [
      `${BASE_URL}/products`,
      `${BASE_URL}/categories`,
    ];
    const url = endpoints[Math.floor(Math.random() * endpoints.length)];
    const res = http.get(url, { headers: jsonHeaders() });

    captureRateLimitHeaders(res, 'public-api');

    const ok = check(res, {
      'public-api: 200 or 429': (r) => r.status === 200 || r.status === 429,
    });
    successRate.add(res.status === 200);
    if (res.status === 429) throttledRequests.add(1);

    sleep(0.3);
  });
}

// ── 2. Login ─────────────────────────────────────────────────────────────────
export function loginScenario() {
  group('login', () => {
    const res = http.post(
      `${BASE_URL}/login`,
      JSON.stringify({ email: 'user@example.com', password: 'password' }),
      { headers: jsonHeaders() }
    );

    captureRateLimitHeaders(res, 'login');

    check(res, {
      'login: 200 or 429 or 422': (r) => [200, 429, 422].includes(r.status),
    });
    successRate.add(res.status === 200);
    if (res.status === 429) throttledRequests.add(1);

    sleep(0.5);
  });
}

// ── 3. Register ───────────────────────────────────────────────────────────────
export function registerScenario() {
  group('register', () => {
    const unique = `testuser_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;
    const res = http.post(
      `${BASE_URL}/register`,
      JSON.stringify({
        name:                  unique,
        email:                 `${unique}@example.com`,
        password:              'Password123!',
        password_confirmation: 'Password123!',
      }),
      { headers: jsonHeaders() }
    );

    captureRateLimitHeaders(res, 'register');

    check(res, {
      'register: 201 or 429 or 422': (r) => [201, 200, 429, 422].includes(r.status),
    });
    successRate.add([200, 201].includes(res.status));
    if (res.status === 429) throttledRequests.add(1);

    sleep(1);
  });
}

// ── 4. Cart ───────────────────────────────────────────────────────────────────
export function cartScenario(data) {
  if (!data.regularToken) { sleep(1); return; }

  group('cart', () => {
    // Randomly pick a cart operation to simulate realistic mixed traffic
    const action = Math.random();

    let res;
    if (action < 0.5) {
      // GET cart (most common)
      res = http.get(`${BASE_URL}/cart`, { headers: jsonHeaders(data.regularToken) });
    } else if (action < 0.75) {
      // Add to cart
      res = http.post(
        `${BASE_URL}/cart/add/${data.productId}`,
        JSON.stringify({ quantity: 1 }),
        { headers: jsonHeaders(data.regularToken) }
      );
    } else {
      // Update cart
      res = http.patch(
        `${BASE_URL}/cart/update/${data.productId}`,
        JSON.stringify({ quantity: 2 }),
        { headers: jsonHeaders(data.regularToken) }
      );
    }

    captureRateLimitHeaders(res, 'cart');

    check(res, {
      'cart: not a server error': (r) => r.status < 500,
    });
    successRate.add(res.status < 400 || res.status === 429);
    if (res.status === 429) throttledRequests.add(1);

    sleep(0.2);
  });
}

// ── 5. Checkout ───────────────────────────────────────────────────────────────
export function checkoutScenario(data) {
  if (!data.regularToken) { sleep(1); return; }

  group('checkout', () => {
    const res = http.post(
      `${BASE_URL}/orders/checkout`,
      JSON.stringify({}),
      { headers: jsonHeaders(data.regularToken) }
    );

    captureRateLimitHeaders(res, 'checkout');

    check(res, {
      'checkout: 200/201 or 429 or 422': (r) => [200, 201, 422, 429].includes(r.status),
    });
    successRate.add([200, 201].includes(res.status));
    if (res.status === 429) throttledRequests.add(1);

    sleep(0.5);
  });
}

// ── 6. Wallet Top-Up ─────────────────────────────────────────────────────────
export function walletScenario(data) {
  if (!data.regularToken) { sleep(1); return; }

  group('wallet', () => {
    const res = http.post(
      `${BASE_URL}/wallet/topup`,
      JSON.stringify({ amount: 50 }),
      { headers: jsonHeaders(data.regularToken) }
    );

    captureRateLimitHeaders(res, 'wallet');

    check(res, {
      'wallet: 200/201 or 429 or 422': (r) => [200, 201, 422, 429].includes(r.status),
    });
    successRate.add([200, 201].includes(res.status));
    if (res.status === 429) throttledRequests.add(1);

    sleep(0.5);
  });
}

// ── 7. Inventory Update ───────────────────────────────────────────────────────
export function inventoryScenario(data) {
  if (!data.adminToken) { sleep(1); return; }

  group('inventory', () => {
    const action = Math.random() > 0.4;
    let res;

    if (action) {
      res = http.get(`${BASE_URL}/inventory`, { headers: jsonHeaders(data.adminToken) });
    } else {
      res = http.put(
        `${BASE_URL}/inventory/${data.productId}`,
        JSON.stringify({ quantity: Math.floor(Math.random() * 100) }),
        { headers: jsonHeaders(data.adminToken) }
      );
    }

    captureRateLimitHeaders(res, 'inventory');

    check(res, {
      'inventory: not a server error': (r) => r.status < 500,
    });
    successRate.add(res.status < 400 || res.status === 429);
    if (res.status === 429) throttledRequests.add(1);

    sleep(0.5);
  });
}

// ── 8. Admin Order Status ─────────────────────────────────────────────────────
export function adminScenario(data) {
  if (!data.adminToken) { sleep(1); return; }

  group('admin', () => {
    const statuses = ['pending', 'processing', 'completed', 'cancelled'];
    const res = http.put(
      `${BASE_URL}/orders/${data.orderId}/status`,
      JSON.stringify({ status: statuses[Math.floor(Math.random() * statuses.length)] }),
      { headers: jsonHeaders(data.adminToken) }
    );

    captureRateLimitHeaders(res, 'admin-actions');

    check(res, {
      'admin: 200 or 429 or 422 or 403': (r) => [200, 422, 429, 403].includes(r.status),
    });
    successRate.add(res.status === 200);
    if (res.status === 429) throttledRequests.add(1);

    sleep(0.5);
  });
}
