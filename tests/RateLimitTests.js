import http from 'k6/http';
import { sleep, check, group } from 'k6';
import { Counter, Trend, Rate } from 'k6/metrics';

// ─── Base URL ────────────────────────────────────────────────────────────────
const BASE_URL = 'http://127.0.0.1:8080/api';

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
  const body = res.json();
  return body?.token
    || body?.data?.token
    || body?.access_token
    || null;
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

    // ── 3. register ──────────────────────────────────────────────
    // Limit: 3/min per IP
    // Goal:  verify registrations beyond 3 are blocked
    register_spam: {
      executor: 'constant-vus',
      vus: 2,
      duration: '20s',
      startTime: '105s',
      exec: 'registerScenario',
      tags: { scenario: 'register' },
    },
  },

  thresholds: {
    // Overall health
    throttled_requests_429:  ['count>0'],       // we EXPECT to see some 429s — test is invalid without them
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

  return { regularToken, adminToken };
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
    logFailedRequest(res, 'public-api');

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

function logFailedRequest(res, label) {
  if (res.status === 0 || res.error) {
    console.error(`
=== REQUEST FAILED [${label}] ===
Error: ${res.error}
Error Code: ${res.error_code}

Request:
${JSON.stringify(res.request, null, 2)}

Response:
${JSON.stringify({
  status: res.status,
  headers: res.headers,
  body: res.body,
  timings: res.timings,
}, null, 2)}
=================================
`);
  }
}