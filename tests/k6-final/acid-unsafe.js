import http from 'k6/http';
import { sleep } from 'k6';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: ACID — UNSAFE (No DB Transaction, Simulated Mid-Crash)
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    Atomicity — "all or nothing". We trigger a deliberate crash
 *    AFTER the order is created and inventory is decremented, but
 *    BEFORE the wallet is charged (break-trans=true).
 *
 *    Without a DB transaction wrapping all writes, the crash leaves
 *    the database in a PARTIAL, INCOHERENT state:
 *      • Order row EXISTS     ← created before crash
 *      • Inventory DECREASED  ← decremented before crash
 *      • Wallet UNCHANGED     ← crash happened before makeTransaction()
 *      • payment_status = 'pending' forever (never set to 'paid')
 *
 *    This is a "ghost order" — it exists in the DB but was never paid.
 *    The product stock is permanently lost.
 *
 *  WHAT YOU SHOULD SEE (❌ BROKEN STATE):
 *    • HTTP 500 (exception bubbles up)
 *    • inventory quantity went from 10 → 8   (decremented, not rolled back)
 *    • wallet balance stays at $300.00        (charge never happened)
 *    • An order row exists with payment_status = 'pending'
 *    • order_items rows exist for that order
 *    → DB is now INCOHERENT: stock gone, no payment, ghost order
 *
 *  SEEDER:   php artisan db:seed --class=AcidTestSeeder
 *  ENDPOINT: POST /api/orders/checkout?safe=0  (checkoutUnsafe)
 *
 *  Run: k6 run acid-unsafe.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        acid_unsafe: {
            executor: 'shared-iterations',
            vus: 1,
            iterations: 1,
            maxDuration: '30s',
        },
    },
};

const BASE_URL   = 'http://localhost:8080';
const PRODUCT_ID = 301;
const USER       = { email: 'acid@example.com', password: 'password' };

export function setup() {
    const res  = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify({ email: USER.email, password: USER.password }),
        { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
    );
    const body = JSON.parse(res.body);
    if (!body.data?.token) throw new Error(`Login failed: ${res.body}`);

    // Snapshot state before the crash
    const productRes  = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);
    const initQty     = productBody.data?.inventory?.quantity ?? '?';

    const walletRes  = http.get(`${BASE_URL}/api/wallet`, {
        headers: { 'Authorization': `Bearer ${body.data.token}`, 'Accept': 'application/json' },
    });
    const walletBody = JSON.parse(walletRes.body);
    const initBal    = walletBody.data?.balance ?? '?';

    console.log('\n╔══════════════════════════════════════════════════════════╗');
    console.log('║         ACID TEST — UNSAFE — PRE-CRASH SNAPSHOT         ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log(`║  Product 301 quantity  : ${String(initQty).padEnd(32)}║`);
    console.log(`║  Wallet balance        : $${String(initBal).padEnd(31)}║`);
    console.log(`║  Orders for product    : check manually (should be 0)   ║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  About to fire checkout with break-trans=true           ║');
    console.log('║  Crash point: AFTER order+inventory write,              ║');
    console.log('║               BEFORE wallet charge                      ║');
    console.log('╚══════════════════════════════════════════════════════════╝\n');

    return { token: body.data.token, initQty, initBal };
}

export default function (data) {
    // break-trans=true → server crashes mid-checkout intentionally
    const res = http.post(
        `${BASE_URL}/api/orders/checkout?safe=0`,
        JSON.stringify({
            shipping_address: 'Damascus',
            'break-trans': true,   // triggers the RuntimeException mid-write
        }),
        {
            headers: {
                'Authorization': `Bearer ${data.token}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
        }
    );

    const node = res.headers['X-Node'] ?? 'unknown';
    console.log(`\n  Request handled by: ${node}`);
    console.log(`  HTTP Status: ${res.status}`);
    console.log(`  Response: ${res.body}`);
    console.log(`  ⚠️  Expected a 500/error — crash was intentional`);
}

export function teardown(data) {
    sleep(1);

    const productRes  = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);
    const finalQty    = productBody.data?.inventory?.quantity ?? '?';

    const walletRes  = http.get(`${BASE_URL}/api/wallet`, {
        headers: { 'Authorization': `Bearer ${data.token}`, 'Accept': 'application/json' },
    });
    const walletBody = JSON.parse(walletRes.body);
    const finalBal   = walletBody.data?.balance ?? '?';

    const qtyChanged = finalQty !== data.initQty;
    const balChanged = finalBal !== data.initBal;

    // The incoherence: stock went down, but wallet was NOT charged
    const incoherent = qtyChanged && !balChanged;

    console.log('\n╔══════════════════════════════════════════════════════════╗');
    console.log('║         ACID TEST — UNSAFE — POST-CRASH DB STATE        ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  INVENTORY                                              ║');
    console.log(`║    Before : ${String(data.initQty).padEnd(45)}║`);
    console.log(`║    After  : ${String(finalQty).padEnd(45)}║`);
    console.log(`║    ${qtyChanged ? '❌ CHANGED' : '✅ Unchanged'} — inventory was ${qtyChanged ? 'decremented but NOT rolled back' : 'untouched'}${' '.repeat(qtyChanged ? 2 : 10)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  WALLET                                                 ║');
    console.log(`║    Before : $${String(data.initBal).padEnd(44)}║`);
    console.log(`║    After  : $${String(finalBal).padEnd(44)}║`);
    console.log(`║    ${balChanged ? '❌ CHANGED' : '✅ Unchanged'} — wallet was ${balChanged ? 'charged' : 'NOT charged (crash before makeTransaction)'}${' '.repeat(balChanged ? 25 : 0)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  VERDICT                                                ║');
    if (incoherent) {
        console.log('║  ❌ DATABASE IS INCOHERENT (ACID VIOLATED)             ║');
        console.log('║     Stock was consumed but no payment was recorded.    ║');
        console.log('║     A ghost order exists in an unpaid state.           ║');
    } else if (!qtyChanged && !balChanged) {
        console.log('║  ✅ Both unchanged — crash happened before any write   ║');
        console.log('║     (race timing may have differed — retry the test)   ║');
    } else {
        console.log('║  ⚠️  Unexpected state — inspect DB manually            ║');
    }
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  MANUAL DB VERIFICATION:                                ║');
    console.log('║                                                          ║');
    console.log('║  -- Ghost order (paid=pending but stock was consumed):  ║');
    console.log('║  SELECT id, payment_status, total_amount                ║');
    console.log('║  FROM orders WHERE user_id = 200                        ║');
    console.log('║  ORDER BY created_at DESC LIMIT 5;                      ║');
    console.log('║                                                          ║');
    console.log('║  -- Was inventory decremented?                          ║');
    console.log('║  SELECT quantity FROM inventories WHERE product_id=301; ║');
    console.log('║  → If < 10: stock was consumed with no payment          ║');
    console.log('║                                                          ║');
    console.log('║  -- Was wallet charged?                                 ║');
    console.log('║  SELECT balance FROM wallets WHERE user_id = 200;       ║');
    console.log('║  → Should still be $300 if crash worked correctly       ║');
    console.log('╚══════════════════════════════════════════════════════════╝\n');
}
