import http from 'k6/http';
import { sleep } from 'k6';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: 5-User Race — UNSAFE (No Lock, No Retry)
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    5 users simultaneously race to buy a product with only 3 units
 *    in stock. The unsafe endpoint has no concurrency protection:
 *    no pessimistic locks, no optimistic version checks, no retry.
 *
 *  WHAT YOU SHOULD SEE (❌ BROKEN STATE):
 *    • More than 3 "success" responses          → overselling
 *    • Final inventory quantity = 0 or negative → data corruption
 *    • Multiple orders created for phantom stock
 *    • Wallet balances decremented beyond what stock justified
 *
 *  DB COHERENCE CHECK (run after test):
 *    SELECT quantity FROM inventories WHERE product_id = 201;
 *    → Should be 0 if safe, can go NEGATIVE if unsafe (oversold)
 *
 *    SELECT COUNT(*) FROM orders o
 *    JOIN order_items oi ON oi.order_id = o.id
 *    WHERE oi.product_id = 201;
 *    → Should be ≤ 3. More than 3 = overselling confirmed.
 *
 *  SEEDER:   php artisan db:seed --class=RaceRetrySeeder
 *  ENDPOINT: POST /api/orders/checkout?safe=0
 *
 *  Run: k6 run retry-race-unsafe.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        race_unsafe: {
            executor: 'shared-iterations',
            vus: 5,        // one VU per user — all fire at the same time
            iterations: 5,
            maxDuration: '30s',
        },
    },
};

const BASE_URL   = 'http://localhost';
const PRODUCT_ID = 201;

const USERS = [
    { email: 'racer1@example.com', password: 'password' },
    { email: 'racer2@example.com', password: 'password' },
    { email: 'racer3@example.com', password: 'password' },
    { email: 'racer4@example.com', password: 'password' },
    { email: 'racer5@example.com', password: 'password' },
];

// ── Login all 5 users before the race ───────────────────────────────
export function setup() {
    const tokens = USERS.map(u => {
        const res = http.post(
            `${BASE_URL}/api/login`,
            JSON.stringify({ email: u.email, password: u.password }),
            { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
        );
        const body = JSON.parse(res.body);
        if (!body.data?.token) throw new Error(`Login failed for ${u.email}: ${res.body}`);
        console.log(`🔑 Logged in as ${u.email}`);
        return body.data.token;
    });

    // Capture DB state before the race
    const productRes  = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);
    const initialQty  = productBody.data?.inventory?.quantity ?? '?';

    console.log('\n╔══════════════════════════════════════════════════════╗');
    console.log('║           UNSAFE RACE — PRE-TEST STATE               ║');
    console.log('╠══════════════════════════════════════════════════════╣');
    console.log(`║  Product ${PRODUCT_ID} initial quantity : ${String(initialQty).padEnd(25)}║`);
    console.log(`║  Competing users           : ${String(USERS.length).padEnd(25)}║`);
    console.log(`║  Expected successes (safe) : ${'3 (only 3 in stock)'.padEnd(25)}║`);
    console.log('╚══════════════════════════════════════════════════════╝\n');

    return { tokens, initialQty };
}

// ── Each VU fires its checkout immediately (no coordination delay) ───
export default function (data) {
    const idx   = (__VU - 1) % data.tokens.length;
    const token = data.tokens[idx];
    const user  = USERS[idx].email;

    const res = http.post(
        `${BASE_URL}/api/orders/checkout?safe=0`,
        JSON.stringify({ shipping_address: 'Damascus' }),
        {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
        }
    );

    const body   = JSON.parse(res.body);
    const ok     = body.success === true;
    const icon   = ok ? '✅' : '❌';
    const status = ok ? 'SUCCESS  ' : 'FAILED   ';

    console.log(`${icon} VU${__VU} (${user}) → ${status} | ${body.message ?? '-'}${ok ? ` | Order #${body.data?.order?.id} | Wallet: $${body.data?.wallet_balance}` : ''}`);
}

// ── Teardown: show final DB state and coherence verdict ─────────────
export function teardown(data) {
    // Use first token to check product state
    const token = data.tokens[0];

    const productRes  = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);
    const finalQty    = productBody.data?.inventory?.quantity ?? '?';

    const initialQty  = data.initialQty;
    const sold        = (typeof initialQty === 'number' && typeof finalQty === 'number')
                        ? initialQty - finalQty
                        : '?';

    const isNegative  = typeof finalQty === 'number' && finalQty < 0;
    const coherent    = typeof finalQty === 'number' && finalQty >= 0 && sold <= initialQty;

    console.log('\n╔══════════════════════════════════════════════════════╗');
    console.log('║          UNSAFE RACE — POST-TEST DB STATE            ║');
    console.log('╠══════════════════════════════════════════════════════╣');
    console.log(`║  Quantity BEFORE race : ${String(initialQty).padEnd(29)}║`);
    console.log(`║  Quantity AFTER race  : ${String(finalQty).padEnd(29)}║`);
    console.log(`║  Units sold           : ${String(sold).padEnd(29)}║`);
    console.log('╠══════════════════════════════════════════════════════╣');

    if (isNegative) {
        console.log('║  ❌ DATA INCOHERENT — quantity went NEGATIVE!        ║');
        console.log('║     Overselling confirmed. More orders than stock.   ║');
    } else if (sold > initialQty) {
        console.log('║  ❌ DATA INCOHERENT — sold more units than existed!  ║');
    } else {
        console.log('║  ✅ Quantity looks OK (race may not have overlapped) ║');
        console.log('║     Check order count manually to be sure.           ║');
    }

    console.log('╠══════════════════════════════════════════════════════╣');
    console.log('║  MANUAL DB VERIFICATION QUERIES:                    ║');
    console.log('║                                                      ║');
    console.log('║  -- How many orders were placed for this product?   ║');
    console.log('║  SELECT COUNT(*) FROM orders o                      ║');
    console.log('║  JOIN order_items oi ON oi.order_id = o.id          ║');
    console.log('║  WHERE oi.product_id = 201;                         ║');
    console.log('║  → Should be ≤ 3. More = overselling confirmed.     ║');
    console.log('║                                                      ║');
    console.log('║  -- Final inventory                                  ║');
    console.log('║  SELECT quantity FROM inventories                    ║');
    console.log('║  WHERE product_id = 201;                            ║');
    console.log('║  → Negative = data corruption.                      ║');
    console.log('╚══════════════════════════════════════════════════════╝\n');
}
