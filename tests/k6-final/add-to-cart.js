import http from 'k6/http';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: Add to Cart — Duplicate Race Condition
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    The same user adds the same product to their cart from two devices
 *    at exactly the same time. The service checks `exists()` before
 *    inserting — but both requests read FALSE simultaneously, both
 *    pass the check, and both try to INSERT.
 *
 *  SEEDER:   php artisan db:seed --class=RaceAddToCartSeeder
 *
 *  Run: k6 run add-to-cart.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        add_to_cart_race: {
            executor: 'shared-iterations',
            vus: 1,
            iterations: 1,
            maxDuration: '30s',
        },
    },
};

const BASE_URL   = 'http://localhost';
const PRODUCT_ID = 201;
const QUANTITY   = 1;

//  Login
export function setup() {
    const res = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify({ email: 'cart@example.com', password: 'password' }),
        { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
    );

    const body = JSON.parse(res.body);
    if (!body.data?.token) throw new Error(`Login failed: ${res.body}`);

    console.log('🔑 Logged in as cart@example.com');
    return { token: body.data.token };
}

// ── Fire add-to-cart requests───────────────
export default function (data) {
    const req = {
        method: 'POST',
        url:    `${BASE_URL}/api/cart/add/${PRODUCT_ID}`,
        body:   JSON.stringify({ quantity: QUANTITY }),
        params: {
            headers: {
                'Authorization': `Bearer ${data.token}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
        },
    };

    console.log('\n═══════════════════════════════════════════════════════');
    console.log('  Firing 2 add-to-cart requests simultaneously');
    console.log(`  Both targeting product_id=${PRODUCT_ID}, quantity=${QUANTITY}`);
    console.log(`  Cart is empty — both will race to be first to insert`);
    console.log('═══════════════════════════════════════════════════════');

    const [res1, res2] = http.batch([req, req]);

    // Parse bodies — handle 500 which may not be valid JSON
    let b1 = {}, b2 = {};
    try { b1 = JSON.parse(res1.body); } catch (_) { b1 = { message: res1.body }; }
    try { b2 = JSON.parse(res2.body); } catch (_) { b2 = { message: res2.body }; }

    // ── Report ────────────────────────────────────────────────────────
    console.log('\n── Request 1 (Device A) ────────────────────────────────');
    console.log(`  HTTP Status : ${res1.status}`);
    console.log(`  Success     : ${b1.successful ?? (res1.status === 200 ? 'true' : 'false')}`);
    console.log(`  Message     : ${b1.message ?? '-'}`);

    console.log('\n── Request 2 (Device B) ────────────────────────────────');
    console.log(`  HTTP Status : ${res2.status}`);
    console.log(`  Success     : ${b2.successful ?? (res2.status === 200 ? 'true' : 'false')}`);
    console.log(`  Message     : ${b2.message ?? '-'}`);

    console.log('═══════════════════════════════════════════════════════\n');
}
