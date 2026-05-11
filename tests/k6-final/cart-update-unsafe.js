import http from 'k6/http';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: Cart Update — Unsafe (Lost Update Race Condition)
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    The same user updates the quantity of the same cart item from
 *    two devices at the exact same moment. The unsafe endpoint reads
 *    the current quantity, does some validation, then writes the new
 *    value — with NO locking in between.
 *
 *  RACE CONDITION (Lost Update):
 *    Both requests read quantity=5 at the same time.
 *    Request A decides to write qty=3.
 *    Request B decides to write qty=8.
 *    Both writes succeed — whichever lands last wins.
 *    The other update is silently discarded (lost update).
 *    Both return 200 success even though one change was thrown away.
 *
 *  HOW TO READ THE RESULTS:
 *    ❌ UNSAFE — both return success with DIFFERENT quantities.
 *               The final DB value is whichever wrote last.
 *               One user's intent was silently ignored.
 *
 *  SEEDER:   php artisan db:seed --class=CartRaceSeeder
 *            (sets initial cart item quantity = 5, stock = 50)
 *  ENDPOINT: POST /api/cart/update/unsafe/{product_id}
 *            product_id = 1
 *
 *  Run: k6 run cart-update-unsafe.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        cart_update_unsafe: {
            executor: 'shared-iterations',
            vus: 1,
            iterations: 1,
            maxDuration: '30s',
        },
    },
};

const BASE_URL    = 'http://localhost';
const PRODUCT_ID  = 1;   // must match CartRaceSeeder

export function setup() {
    const res = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify({ email: 'double@example.com', password: 'password' }),
        { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
    );

    const body = JSON.parse(res.body);
    if (!body.data?.token) throw new Error(`Login failed: ${res.body}`);

    // Snapshot the quantity before the race so we can compare after
    const cartRes  = http.get(`${BASE_URL}/api/cart`, {
        headers: {
            'Authorization': `Bearer ${body.data.token}`,
            'Accept':        'application/json',
        },
    });

    let quantityBefore = '?';
    try {
        const cart = JSON.parse(cartRes.body);
        console.log(cart);
        const item = cart.data?.find(p => p.product_id === PRODUCT_ID || p.id === PRODUCT_ID);
        if (item) quantityBefore = item.order_quantity ?? item.pivot?.order_quantity ?? '?';
    } catch (_) {}

    console.log(`📦 Cart item product_id=${PRODUCT_ID} quantity BEFORE race: ${quantityBefore}`);
    return { token: body.data.token, quantityBefore };
}

export default function (data) {
    const headers = {
        'Authorization': `Bearer ${data.token}`,
        'Content-Type':  'application/json',
        'Accept':        'application/json',
    };

    // Two intentionally different quantities so we can see which one "won"
    const qty1 = Math.floor(Math.random() * 10) + 1;   // 1-10
    const qty2 = Math.floor(Math.random() * 10) + 1;   // 1-10

    console.log('\n══════════════════════════════════════════════════');
    console.log('  Firing 2 cart-update/UNSAFE requests simultaneously');
    console.log(`  Request 1 wants quantity = ${qty1}`);
    console.log(`  Request 2 wants quantity = ${qty2}`);
    console.log(`  Initial quantity in DB   = ${data.quantityBefore}`);
    console.log('══════════════════════════════════════════════════');

    const [res1, res2] = http.batch([
        {
            method: 'POST',
            url:    `${BASE_URL}/api/cart/update/${PRODUCT_ID}?safe=0`,
            body:   JSON.stringify({ quantity: qty1 }),
            params: { headers },
        },
        {
            method: 'POST',
            url:    `${BASE_URL}/api/cart/update/unsafe/${PRODUCT_ID}?safe=0`,
            body:   JSON.stringify({ quantity: qty2 }),
            params: { headers },
        },
    ]);

    const b1 = JSON.parse(res1.body);
    const b2 = JSON.parse(res2.body);

    console.log('\n── Request 1 ─────────────────────────────────────');
    console.log(`  Wanted qty  : ${qty1}`);
    console.log(`  HTTP Status : ${res1.status}`);
    console.log(`  Success     : ${b1.success ?? 'N/A'}`);
    console.log(`  Message     : ${b1.message ?? '-'}`);

    console.log('\n── Request 2 ─────────────────────────────────────');
    console.log(`  Wanted qty  : ${qty2}`);
    console.log(`  HTTP Status : ${res2.status}`);
    console.log(`  Success     : ${b2.success ?? 'N/A'}`);
    console.log(`  Message     : ${b2.message ?? '-'}`);

    // Fetch final DB value to prove which one "won" the race
    const cartRes = http.get(`${BASE_URL}/api/cart`, { headers });
    let quantityAfter = '?';
    try {
        const cart = JSON.parse(cartRes.body);
        const item = cart.data?.find(p => p.product_id === PRODUCT_ID || p.id === PRODUCT_ID);
        if (item) quantityAfter = item.order_quantity ?? item.pivot?.order_quantity ?? '?';
    } catch (_) {}

    console.log('\n── Final State ───────────────────────────────────');
    console.log(`  Quantity BEFORE race : ${data.quantityBefore}`);
    console.log(`  Request 1 wanted     : ${qty1}`);
    console.log(`  Request 2 wanted     : ${qty2}`);
    console.log(`  Quantity AFTER race  : ${quantityAfter}`);

    // ── Verdict ────────────────────────────────────────────────────────
    const bothSucceeded = b1.success !== false && b2.success !== false;
    console.log('\n══════════════════════════════════════════════════');
    if (bothSucceeded && qty1 !== qty2) {
        const winner = String(quantityAfter) === String(qty1) ? 1 : 2;
        const loser  = winner === 1 ? 2 : 1;
        console.log('  ❌ LOST UPDATE — UNSAFE BEHAVIOUR CONFIRMED');
        console.log(`     Both requests returned success.`);
        console.log(`     Request ${winner} (qty=${winner === 1 ? qty1 : qty2}) won the race.`);
        console.log(`     Request ${loser}  (qty=${loser  === 1 ? qty1 : qty2}) was silently overwritten.`);
        console.log(`     The user on device ${loser} has no idea their update was lost.`);
    } else if (!bothSucceeded) {
        console.log('  ✅ ONE REQUEST WAS REJECTED (unexpected for unsafe endpoint)');
    } else {
        console.log('  ⚠️  Both quantities were the same — rerun to get a meaningful race.');
    }
    console.log('══════════════════════════════════════════════════\n');
}
