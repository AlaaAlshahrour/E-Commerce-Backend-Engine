import http from 'k6/http';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: Same Product Race — Safe (Optimistic Lock)
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    Two different users (buyer1 and buyer2) race to buy the ONLY unit
 *    of a product. The safe endpoint uses an optimistic lock (updated_at
 *    check) to prevent overselling.
 *
 *  HOW TO READ THE RESULTS:
 *    ✅ SAFE — exactly one request succeeds, the other is rejected with
 *              "Some products are out of stock".
 *    ❌ UNSAFE — both succeed (overselling occurs).
 *
 *  SEEDER:   php artisan db:seed --class=RaceSameProductSeeder
 *            (sets product 101 quantity = 1)
 *  ENDPOINT: POST /api/orders/checkout/safe
 *
 *  Run: k6 run same-product-safe.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        same_product_race: {
            executor: 'shared-iterations',
            vus: 2,        // 2 VUs — one for each buyer
            iterations: 2, // Each VU runs once
            maxDuration: '30s',
        },
    },
};

const BASE_URL    = 'http://localhost';
const PRODUCT_ID  = 101; // must match RaceSameProductSeeder

const USERS = [
    { email: 'buyer1@example.com', password: 'password' },
    { email: 'buyer2@example.com', password: 'password' },
];

// Login both users once before any VU starts
export function setup() {
    const tokens = USERS.map(u => {
        const res = http.post(
            `${BASE_URL}/api/login`,
            JSON.stringify({ email: u.email, password: u.password }),
            { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
        );
        const body = JSON.parse(res.body);
        if (!body.data?.token) throw new Error(`Login failed for ${u.email}: ${res.body}`);
        return body.data.token;
    });

    // Fetch initial product quantity
    const productRes = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);

    const initialQuantity = productBody.data?.inventory?.quantity ?? '?';

    console.log(`📦 Product ${PRODUCT_ID} quantity BEFORE race: ${initialQuantity}`);

    return { tokens, initialQuantity };
}

export default function (data) {
    // __VU is 1-based; map to 0-based token index
    const idx   = (__VU - 1) % data.tokens.length;
    const token = data.tokens[idx];
    const user  = USERS[idx].email;

    console.log(`\n═════════════════════════════════════════════════`);

    const res = http.post(
        `${BASE_URL}/api/orders/checkout?safe=1`,
        JSON.stringify({ shipping_address: 'Damascus' }),
        {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        }
    );

    const body = JSON.parse(res.body);

    console.log(`\n── VU ${__VU} (${user}) ────────────────────────────`);
    console.log(`  Body     : ${body ?? '-'}`);
    console.log(`  HTTP Status : ${res.status}`);
    console.log(`  Success     : ${body.successful ?? 'N/A'}`);
    console.log(`  Message     : ${body.message ?? '-'}`);
    if (body.data?.order) console.log(`  Order ID    : ${body.data.order.id}`);

    // Fetch final product quantity
    const productRes = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);
    const finalQuantity = productBody.data?.inventory?.quantity ?? '?';

    console.log(`\n── Final State ──────────────────────────────────`);
    console.log(`  Quantity BEFORE race : ${data.initialQuantity}`);
    console.log(`  Quantity AFTER race  : ${finalQuantity}`);

    console.log(`\n═════════════════════════════════════════════════`);

}
