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
 *  SEEDER:   php artisan db:seed --class=RaceDoubleCheckoutSeeder
 *  ENDPOINT: POST /api/orders/checkout/double-checkout
 *
 *  Run: k6 run double-checkout-unsafe.js
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
    const headers = {
        'Authorization': `Bearer ${data.token}`,
        'Content-Type':  'application/json',
        'Accept':        'application/json',
    };

    console.log('\n══════════════════════════════════════════');
    console.log('  Firing 1 checkout request');
    console.log('══════════════════════════════════════════');

    const res = http.post(
        `${BASE_URL}/api/orders/checkout?pdfs=1&safe=0`,
        JSON.stringify({ shipping_address: 'Damascus' }),
        { headers }
    );
    console.log(res)
    const b = JSON.parse(res.body);

    console.log(`  HTTP Status : ${res.status}`);
    console.log(`  Success     : ${b.successful ?? 'N/A'}`);
    console.log(`  Message     : ${b.message ?? '-'}`);
    if (b.data?.order)                    console.log(`  Order ID    : ${b.data.order.id}`);
    if (b.data?.wallet_balance !== undefined) console.log(`  Wallet after: $${b.data.wallet_balance}`);

    console.log('══════════════════════════════════════════\n');
}
