import http from 'k6/http';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: Admin Inventory Update vs Customer Checkout Race
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    Admin updates product qty from 40 → 60 (restocking +20).
 *    At the same time, a customer checks out 10 units of the same product.
 *    If not handled, the admin write silently overwrites the checkout
 *    deduction — 10 sold units disappear from inventory records.
 *
 *  THE RACE (step by step):
 *    Initial qty = 40
 *    Checkout  → registers cache: active_purchases:301 = 10
 *    Checkout  → lockForUpdate on inventory row
 *    Checkout  → decrements qty: 40 → 30
 *    Admin     → PUT /inventory/301  { quantity: 60 }
 *
 *    UNSAFE endpoint (no cache check, no lock):
 *      Admin writes 60 on top → final qty = 60
 *      The 10 sold units are gone from the record. Overselling risk.
 *
 *    SAFE endpoint (lockForUpdate + cache check):
 *      Admin sees active_purchases:301 > 0 → blocked
 *      Returns: "Cannot update now — Product is being purchased."
 *      After checkout commits, admin retries → sets correct value.
 *
 *  HOW TO READ THE RESULTS:
 *    ❌ UNSAFE: both return success, final qty=60 (deduction lost)
 *    ✅ SAFE:   checkout succeeds, admin blocked with clear message
 *
 *  SEEDER:        php artisan db:seed --class=AdminInventoryRaceSeeder
 *  ENDPOINTS:
 *    Buyer  → POST /api/orders/checkout/safe
 *    Admin  → PUT  /api/inventory/{productId}
 *             (controller calls updateQuantityUnsafe or updateQuantitySafe)
 *
 *  Run unsafe: k6 run admin-inventory-race-unsafe.js
 *  Run safe:   k6 run admin-inventory-race-unsafe.js -e MODE=safe
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        inventory_race: {
            executor: 'shared-iterations',
            vus: 2,        // VU1 = buyer checkout, VU2 = admin update
            iterations: 2,
            maxDuration: '30s',
        },
    },
};

const BASE_URL   = 'http://localhost';
const PRODUCT_ID = 301;
const MODE       =  'safe'; // pass -e MODE=safe to test safe endpoint

const ADMIN_NEW_QTY = 60;

function login(email) {
    const res = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify({ email, password: 'password' }),
        { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
    );
    const body = JSON.parse(res.body);
    if (!body.data?.token) throw new Error(`Login failed for ${email}: ${res.body}`);
    return body.data.token;
}

function getInventory(token) {
    const res = http.get(
        `${BASE_URL}/api/inventory/${PRODUCT_ID}`,
        { headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } }
    );
    try {
        return JSON.parse(res.body).data?.quantity ?? '?';
    } catch (_) { return '?'; }
}

// ── Login both users once before any VU starts ───────────────────────
export function setup() {
    const buyerToken = login('buyer@example.com');
    const adminToken = login('admin@example.com');

    const qtyBefore = getInventory(adminToken);

    console.log('\n═══════════════════════════════════════════════════════');
    console.log(`  MODE: ${MODE.toUpperCase()}`);
    console.log(`  Product ${PRODUCT_ID} qty BEFORE race: ${qtyBefore}`);
    console.log(`  Buyer will checkout 10 units`);
    console.log(`  Admin will set qty = ${ADMIN_NEW_QTY}`);
    console.log(`  Correct final qty if safe = ${qtyBefore - 10} (admin blocked)`);
    console.log(`  Wrong final qty if unsafe  = ${ADMIN_NEW_QTY} (admin overwrites deduction)`);
    console.log('═══════════════════════════════════════════════════════\n');

    return { buyerToken, adminToken, qtyBefore };
}

export default function (data) {
    // VU1 = buyer, VU2 = admin — deterministic, not random
    if (__VU === 1) {
        runBuyer(data);
    } else {
        runAdmin(data);
    }
}

function runBuyer(data) {
    const res = http.post(
        `${BASE_URL}/api/orders/checkout/safe`,
        JSON.stringify({ shipping_address: 'Damascus' }),
        {
            headers: {
                'Authorization': `Bearer ${data.buyerToken}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
        }
    );

    let body = {};
    try { body = JSON.parse(res.body); } catch (_) { body = { message: res.body }; }

    console.log('\n── VU1 BUYER (checkout 10 units) ───────────────────────');
    console.log(`  HTTP Status : ${res.status}`);
    console.log(`  Success     : ${body.success ?? 'N/A'}`);
    console.log(`  Message     : ${body.message ?? '-'}`);
    if (body.data?.wallet_balance !== undefined)
        console.log(`  Wallet after: $${body.data.wallet_balance}`);
    if (body.data?.order?.id)
        console.log(`  Order ID    : ${body.data.order.id}`);
}

function runAdmin(data) {
    // Determine endpoint based on MODE
    // unsafe → controller calls updateQuantityUnsafe (no cache check)
    // safe   → controller calls updateQuantitySafe   (cache check + lockForUpdate)
    // NOTE: both use PUT /api/inventory/{id} — switch the controller method
    //       or add a separate route for the safe version during testing
    const res = http.put(
        `${BASE_URL}/api/inventory/${PRODUCT_ID}?safe=1`,
        JSON.stringify({ quantity: ADMIN_NEW_QTY }),
        {
            headers: {
                'Authorization': `Bearer ${data.adminToken}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
        }
    );

    let body = {};
    try { body = JSON.parse(res.body); } catch (_) { body = { message: res.body }; }

    console.log('\n── VU2 ADMIN (set qty=60) ──────────────────────────────');
    console.log(`  HTTP Status : ${res.status}`);
    console.log(`  Success     : ${body.success ?? 'N/A'}`);
    console.log(`  Message     : ${body.message ?? '-'}`);
    if (body.data?.pending_purchases !== undefined)
        console.log(`  Pending purchases detected: ${body.data.pending_purchases} units`);
    if (body.data?.suggested !== undefined)
        console.log(`  Suggested qty for admin   : ${body.data.suggested}`);
}

// ── Teardown: fetch final qty and print verdict ───────────────────────
export function teardown(data) {
    const finalQty = getInventory(data.adminToken);
    const expected = data.qtyBefore - 10; // 40 - 10 = 30 (admin was blocked)
    const unsafe   = ADMIN_NEW_QTY;       // 60 (admin overwrote deduction)

    console.log('\n── Final State ──────────────────────────────────────────');
    console.log(`  Qty BEFORE race          : ${data.qtyBefore}`);
    console.log(`  Buyer purchased          : 10 units`);
    console.log(`  Admin tried to set       : ${ADMIN_NEW_QTY}`);
    console.log(`  Expected safe final qty if admin blocked: ${expected}`);
    console.log(`  Expected safe final qty if admin reached first: ${ADMIN_NEW_QTY-10} `);
    console.log(`  Expected unsafe final qty: ${unsafe}   (admin overwrote deduction)`);
    console.log(`  Actual final qty         : ${finalQty}`);

    console.log('\n═══════════════════════════════════════════════════════');
    if (String(finalQty) === String(expected)||String(finalQty) === String(ADMIN_NEW_QTY-10)) {
        console.log('  ✅ SAFE BEHAVIOUR CONFIRMED');
        console.log('     Admin was blocked while checkout was in progress.');
        console.log('     Inventory correctly reflects the 10-unit sale.');
        console.log('     Admin should retry and set 50 (or their intended +20 restock).');
    } else if (String(finalQty) === String(unsafe)) {
        console.log('  ❌ UNSAFE BEHAVIOUR — Hidden Update Detected');
        console.log(`     Final qty = ${finalQty} — admin write overwrote the checkout deduction.`);
        console.log('     10 sold units are no longer reflected in inventory.');
        console.log('     This will cause overselling on the next purchase attempt.');
    } else {
        console.log(`  ⚠️  Unexpected final qty: ${finalQty} — check server logs.`);
    }
    console.log('═══════════════════════════════════════════════════════\n');
}
