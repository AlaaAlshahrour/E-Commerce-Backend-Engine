import http from 'k6/http';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: Double Checkout (Same User)
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    A single user fires two checkout requests at the exact same
 *    millisecond (via http.batch). In an unsafe implementation both
 *    succeed — the wallet is double-charged and two identical orders
 *    are created. A safe implementation blocks the second request
 *    with a "Checkout in progress" error using a distributed lock.
 *
 *  HOW TO READ THE RESULTS:
 *    ✅ SAFE   — exactly one SUCCESS + one "Checkout in progress" failure
 *    ❌ UNSAFE — both return SUCCESS (double order, double charge)
 *
 *  SEEDER:   php artisan db:seed --class=DoubleCheckoutSeeder
 *  ENDPOINT: POST /api/orders/checkout/double-checkout
 *
 *  Run: k6 run double-checkout-safe.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        double_checkout: {
            executor: 'shared-iterations',
            vus: 1,        // 1 VU — http.batch() fires both requests in parallel
            iterations: 1,
            maxDuration: '30s',
        },
    },
};

const BASE_URL = 'http://localhost';

// ── Login once before any VU starts ──────────────────────────────────
export function setup() {
    const res = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify({ email: 'double@example.com', password: 'password' }),
        { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
    );

    const body = JSON.parse(res.body);
    if (!body.data?.token) {
        throw new Error(`Login failed: ${res.body}`);
    }

    console.log('🔑 Logged in as double@example.com');
    return { token: body.data.token };
}

// ── Fire both requests at the same instant ───────────────────────────
export default function (data) {
    const req = {
        method: 'POST',
        url:    `${BASE_URL}/api/orders/checkout?safe=0`,
        body:   JSON.stringify({ shipping_address: 'Damascus' }),
        params: {
            headers: {
                'Authorization': `Bearer ${data.token}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
        },
    };

    console.log('\n══════════════════════════════════════════');
    console.log('  Firing 2 checkout requests simultaneously');
    console.log('══════════════════════════════════════════');

    // http.batch sends both requests in parallel from the same VU
    const [res1, res2] = http.batch([req, req]);

    const b1 = JSON.parse(res1.body);
    const b2 = JSON.parse(res2.body);

    // ── Report ────────────────────────────────────────────────────────
    console.log('\n── Request 1 ──────────────────────────────');
    console.log(`  HTTP Status : ${res1.status}`);
    console.log(`  Success     : ${b1.success ?? 'N/A'}`);
    console.log(`  Message     : ${b1.message ?? '-'}`);
    if (b1.data?.order)       console.log(`  Order ID    : ${b1.data.order.id}`);
    if (b1.data?.wallet_balance !== undefined)
                              console.log(`  Wallet after: $${b1.data.wallet_balance}`);

    console.log('\n── Request 2 ──────────────────────────────');
    console.log(`  HTTP Status : ${res2.status}`);
    console.log(`  Success     : ${b2.success ?? 'N/A'}`);
    console.log(`  Message     : ${b2.message ?? '-'}`);
    if (b2.data?.order)       console.log(`  Order ID    : ${b2.data.order.id}`);
    if (b2.data?.wallet_balance !== undefined)
                              console.log(`  Wallet after: $${b2.data.wallet_balance}`);

    // ── Verdict ───────────────────────────────────────────────────────
    const bothSucceeded = b1.success === true && b2.success === true;
    const oneBlocked    = (b1.success === true && b2.success === false)
                       || (b1.success === false && b2.success === true);

    console.log('\n══════════════════════════════════════════');
    if (bothSucceeded) {
        console.log('  ❌ UNSAFE BEHAVIOUR DETECTED');
        console.log('     Both requests succeeded — double order created,');
        console.log('     wallet charged twice. No concurrency protection.');
    } else if (oneBlocked) {
        console.log('  ✅ SAFE BEHAVIOUR CONFIRMED');
        console.log('     One request succeeded, the other was blocked.');
        console.log('     Distributed lock prevented the double checkout.');
    } else {
        console.log('  ⚠️  UNEXPECTED OUTCOME — check messages above.');
    }
    console.log('══════════════════════════════════════════\n');
}
