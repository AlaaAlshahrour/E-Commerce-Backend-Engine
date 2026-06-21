import http from 'k6/http';
import { sleep } from 'k6';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: ACID — SAFE (DB::transaction wraps all writes)
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    Same deliberate crash as acid-unsafe.js (break-trans=true),
 *    but now ALL writes are wrapped in DB::transaction().
 *
 *    When the RuntimeException fires after createOrder() but before
 *    makeTransaction(), Laravel's DB::transaction catches the exception
 *    and issues a ROLLBACK — undoing every write in that transaction:
 *      • Order row   → rolled back (never committed)
 *      • Inventory   → rolled back (quantity restored)
 *      • Wallet      → never touched (rollback got there first)
 *
 *    The database ends up in EXACTLY the state it was before the
 *    request arrived. This is Atomicity: all or nothing.
 *
 *  WHAT YOU SHOULD SEE (✅ CORRECT STATE):
 *    • HTTP 500 / error response  (exception still bubbles up)
 *    • inventory quantity UNCHANGED  (rolled back)
 *    • wallet balance UNCHANGED      (rolled back / never written)
 *    • ZERO new order rows for user 200
 *    • ZERO new order_items rows
 *    → DB is PRISTINE — as if the request never happened
 *
 *  SEEDER:   php artisan db:seed --class=AcidTestSeeder
 *  ENDPOINT: POST /api/orders/checkout?safe=1  (checkoutSafeOptimized)
 *
 *  Run: k6 run acid-safe.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        acid_safe: {
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

    const productRes  = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);
    const initQty     = productBody.data?.inventory?.quantity ?? '?';

    const walletRes  = http.get(`${BASE_URL}/api/wallet`, {
        headers: { 'Authorization': `Bearer ${body.data.token}`, 'Accept': 'application/json' },
    });
    const walletBody = JSON.parse(walletRes.body);
    const initBal    = walletBody.data?.balance ?? '?';

    // Count orders before
    const ordersRes  = http.get(`${BASE_URL}/api/orders`, {
        headers: { 'Authorization': `Bearer ${body.data.token}`, 'Accept': 'application/json' },
    });
    const ordersBody = JSON.parse(ordersRes.body);
    const initOrders = ordersBody.data?.length ?? 0;

    console.log('\n╔══════════════════════════════════════════════════════════╗');
    console.log('║          ACID TEST — SAFE — PRE-CRASH SNAPSHOT          ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log(`║  Product 301 quantity  : ${String(initQty).padEnd(32)}║`);
    console.log(`║  Wallet balance        : $${String(initBal).padEnd(31)}║`);
    console.log(`║  Existing orders       : ${String(initOrders).padEnd(32)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  About to fire checkout with break-trans=true           ║');
    console.log('║  Crash point: inside DB::transaction, AFTER createOrder ║');
    console.log('║  Expected: Laravel issues ROLLBACK, DB stays pristine   ║');
    console.log('╚══════════════════════════════════════════════════════════╝\n');

    return { token: body.data.token, initQty, initBal, initOrders };
}

export default function (data) {
    const res = http.post(
        `${BASE_URL}/api/orders/checkout?safe=1`,
        JSON.stringify({
            shipping_address: 'Damascus',
            'break-trans': true,
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
    const body = JSON.parse(res.body);

    console.log(`\n  Request handled by: ${node}`);
    console.log(`  HTTP Status : ${res.status}`);
    console.log(`  Message     : ${body.message ?? res.body}`);
    console.log(`  ⚠️  Error is expected — DB::transaction should have rolled back`);
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

    const ordersRes  = http.get(`${BASE_URL}/api/orders`, {
        headers: { 'Authorization': `Bearer ${data.token}`, 'Accept': 'application/json' },
    });
    const ordersBody = JSON.parse(ordersRes.body);
    const finalOrders = ordersBody.data?.length ?? 0;

    const qtyIntact    = finalQty    === data.initQty;
    const balIntact    = finalBal    === data.initBal;
    const orderIntact  = finalOrders === data.initOrders;
    const fullyRolledBack = qtyIntact && balIntact && orderIntact;

    console.log('\n╔══════════════════════════════════════════════════════════╗');
    console.log('║         ACID TEST — SAFE — POST-CRASH DB STATE          ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  INVENTORY                                              ║');
    console.log(`║    Before : ${String(data.initQty).padEnd(45)}║`);
    console.log(`║    After  : ${String(finalQty).padEnd(45)}║`);
    console.log(`║    ${qtyIntact ? '✅ UNCHANGED' : '❌ CHANGED'} — ${qtyIntact ? 'inventory rollback confirmed' : 'rollback FAILED'}${' '.repeat(qtyIntact ? 18 : 21)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  WALLET                                                 ║');
    console.log(`║    Before : $${String(data.initBal).padEnd(44)}║`);
    console.log(`║    After  : $${String(finalBal).padEnd(44)}║`);
    console.log(`║    ${balIntact ? '✅ UNCHANGED' : '❌ CHANGED'} — ${balIntact ? 'wallet rollback confirmed  ' : 'rollback FAILED           '}               ║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  ORDERS                                                 ║');
    console.log(`║    Before : ${String(data.initOrders).padEnd(45)}║`);
    console.log(`║    After  : ${String(finalOrders).padEnd(45)}║`);
    console.log(`║    ${orderIntact ? '✅ NO NEW ORDER' : '❌ GHOST ORDER CREATED'} — ${orderIntact ? 'order rollback confirmed' : 'rollback FAILED'}${' '.repeat(orderIntact ? 13 : 11)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  VERDICT                                                ║');
    if (fullyRolledBack) {
        console.log('║  ✅ FULL ROLLBACK — ACID ATOMICITY CONFIRMED           ║');
        console.log('║     The crash triggered a complete ROLLBACK.           ║');
        console.log('║     DB is in exactly the state before the request.     ║');
        console.log('║     No ghost orders. No phantom stock loss.            ║');
        console.log('║     No wallet discrepancy. All or nothing. ✓           ║');
    } else {
        console.log('║  ❌ PARTIAL WRITE DETECTED — ROLLBACK DID NOT WORK    ║');
        if (!qtyIntact)   console.log('║     → Inventory was not rolled back                   ║');
        if (!balIntact)   console.log('║     → Wallet was not rolled back                      ║');
        if (!orderIntact) console.log('║     → Ghost order was not rolled back                 ║');
    }
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  MANUAL DB VERIFICATION:                                ║');
    console.log('║                                                          ║');
    console.log('║  -- Should return 0 rows (no ghost orders):             ║');
    console.log('║  SELECT id, payment_status FROM orders                  ║');
    console.log('║  WHERE user_id = 200;                                   ║');
    console.log('║                                                          ║');
    console.log('║  -- Should still be 10:                                 ║');
    console.log('║  SELECT quantity FROM inventories WHERE product_id=301; ║');
    console.log('║                                                          ║');
    console.log('║  -- Should still be $300.00:                            ║');
    console.log('║  SELECT balance FROM wallets WHERE user_id = 200;       ║');
    console.log('╚══════════════════════════════════════════════════════════╝\n');
}
