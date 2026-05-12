import http from 'k6/http';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: Cart Update — Safe (Cache Lock + Optimistic Lock)
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    Same race as cart-update-unsafe.js but against the protected
 *    endpoint. The safe endpoint uses two layers of protection:
 *
 *
 *  HOW TO READ THE RESULTS:
 *    ✅ SAFE — exactly one request succeeds, the other is rejected.
 *              No update is silently lost. The user is informed their
 *              device lost the race and should retry.
 *    ❌ UNSAFE — both succeed (the lock is broken or missing).
 *
 *  SEEDER:   php artisan db:seed --class=RaceCartUpdateSeeder
 *            (sets initial cart item quantity = 5, stock = 50)
 *
 *  Run: k6 run cart-update-safe.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        cart_update_safe: {
            executor: 'shared-iterations',
            vus: 1,
            iterations: 1,
            maxDuration: '30s',
        },
    },
};

const BASE_URL = 'http://localhost';
const PRODUCT_ID = 1;   // matches RaceCartUpdateSeeder

export function setup() {
    const res = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify({email: 'double@example.com', password: 'password'}),
        {headers: {'Content-Type': 'application/json', 'Accept': 'application/json'}}
    );

    const body = JSON.parse(res.body);
    if (!body.data?.token) throw new Error(`Login failed: ${res.body}`);
    const cartRes = http.get(`${BASE_URL}/api/cart`, {
        headers: {
            'Authorization': `Bearer ${body.data.token}`,
            'Accept': 'application/json',
        },
    });

    let quantityBefore = '?';

    try {
        const cart = cartRes.json();

        const item = cart.data?.products?.find(
            p => p.product_id === PRODUCT_ID
        );

        if (item) {
            quantityBefore = item.order_quantity ?? '?';
        }

    } catch (e) {
        console.log('Parse error:', e);
    }

    console.log(
        `📦 Cart item product_id=${PRODUCT_ID} quantity BEFORE race: ${quantityBefore}`
    );

    return {
        token: body.data.token,
        quantityBefore,
    };
}

export default function (data) {
    const headers = {
        'Authorization': `Bearer ${data.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };

    const qty1 = Math.floor(Math.random() * 10) + 1;
    const qty2 = Math.floor(Math.random() * 10) + 1;

   console.log('\n═════════════════════════════════════════════════');
    console.log('  Firing 2 cart-update/SAFE requests simultaneously');
    console.log(`  Request 1 wants quantity = ${qty1}`);
    console.log(`  Request 2 wants quantity = ${qty2}`);
    console.log(`  Initial quantity in DB   = ${data.quantityBefore}`);
    console.log('═════════════════════════════════════════════════');

    const [res1, res2] = http.batch([
        {
            method: 'POST',
            url: `${BASE_URL}/api/cart/update/${PRODUCT_ID}?safe=1`,
            body: JSON.stringify({quantity: qty1}),
            params: {headers},
        },
        {
            method: 'POST',
            url: `${BASE_URL}/api/cart/update/${PRODUCT_ID}?safe=1`,
            body: JSON.stringify({quantity: qty2}),
            params: {headers},
        },
    ]);

    const b1 = JSON.parse(res1.body);
    const b2 = JSON.parse(res2.body);

    console.log('\n── Request 1 ────────────────────────────────────');
    console.log(`  Wanted qty  : ${qty1}`);
    console.log(`  HTTP Status : ${res1.status}`);
    console.log(`  Success     : ${b1.successful ?? 'N/A'}`);
    console.log(`  Message     : ${b1.message ?? '-'}`);

    console.log('\n── Request 2 ────────────────────────────────────');
    console.log(`  Wanted qty  : ${qty2}`);
    console.log(`  HTTP Status : ${res2.status}`);
    console.log(`  Success     : ${b2.successful ?? 'N/A'}`);
    console.log(`  Message     : ${b2.message ?? '-'}`);

    // Fetch final DB state
    const cartRes = http.get(`${BASE_URL}/api/cart`, {headers});
    let quantityAfter = '?';
    try {
        const cart = cartRes.json();

        const item = cart.data?.products?.find(
            p => p.product_id === PRODUCT_ID
        );

        if (item) {
            quantityAfter = item.order_quantity ?? '?';
        }

    } catch (e) {
        console.log('Parse error:', e);
    }


    console.log('\n── Final State ──────────────────────────────────');
    console.log(`  Quantity BEFORE race : ${data.quantityBefore}`);
    console.log(`  Request 1 wanted     : ${qty1}`);
    console.log(`  Request 2 wanted     : ${qty2}`);
    console.log(`  Quantity AFTER race  : ${quantityAfter}`);

    console.log('═════════════════════════════════════════════════\n');
}
