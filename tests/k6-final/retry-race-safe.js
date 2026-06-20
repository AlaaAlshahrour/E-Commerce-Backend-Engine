import http from 'k6/http';
import { sleep } from 'k6';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: 5-User Race — SAFE (Optimistic Lock + Pessimistic Lock + Retry)
 * ═══════════════════════════════════════════════════════════════════
 *
 *  WHAT WE ARE TESTING:
 *    5 users simultaneously race to buy a product with only 3 units
 *    in stock. The safe endpoint uses:
 *      1. Optimistic locking  — captures updated_at version before writing
 *      2. Pessimistic locking — lockForUpdate() inside the short transaction
 *      3. Retry logic         — up to 3 attempts on version conflict
 *
 *  WHAT YOU SHOULD SEE (✅ CORRECT STATE):
 *    • Exactly 3 "success" responses           → no overselling
 *    • Exactly 2 "failed after retries" or
 *      "out of stock" responses                → rejected cleanly
 *    • Final inventory quantity = 0            → exactly right
 *    • Wallet balances only decremented for    → financial integrity
 *      successful orders
 *    • Some requests will show retry attempts  → retry logic is working
 *
 *  RETRY BEHAVIOUR TO LOOK FOR:
 *    The server logs (storage/logs/laravel.log) will show:
 *      "Checkout attempt N failed" with reason "version conflict"
 *    This means the optimistic check caught a concurrent write and
 *    retried. After retries exhaust, the user gets a clean failure.
 *
 *  DB COHERENCE CHECK (run after test):
 *    SELECT quantity FROM inventories WHERE product_id = 201;
 *    → Must be exactly 0
 *
 *    SELECT COUNT(*) FROM orders o
 *    JOIN order_items oi ON oi.order_id = o.id
 *    WHERE oi.product_id = 201;
 *    → Must be exactly 3
 *
 *    SELECT u.email, w.balance FROM users u
 *    JOIN wallets w ON w.user_id = u.id
 *    WHERE u.email LIKE 'racer%@example.com';
 *    → 3 wallets at $440.01 (500 - 59.99), 2 wallets at $500.00
 *
 *  SEEDER:   php artisan db:seed --class=RaceRetrySeeder
 *  ENDPOINT: POST /api/orders/checkout?safe=1  (checkoutSafeOptimized)
 *
 *  Run: k6 run retry-race-safe.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        race_safe: {
            executor: 'shared-iterations',
            vus: 5,        // one VU per user — all fire simultaneously
            iterations: 5,
            maxDuration: '60s', // longer to account for retry delays
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

// ── Login all 5 users and capture initial state ──────────────────────
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

    // Capture wallet balances before the race
    const walletsBefore = tokens.map((token, i) => {
        const res  = http.get(`${BASE_URL}/api/wallet`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        });
        const body = JSON.parse(res.body);
        return body.data?.balance ?? '?';
    });

    const productRes  = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);
    const initialQty  = productBody.data?.inventory?.quantity ?? '?';

    console.log('\n╔══════════════════════════════════════════════════════════╗');
    console.log('║             SAFE RACE — PRE-TEST STATE                  ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log(`║  Product ${PRODUCT_ID} initial quantity : ${String(initialQty).padEnd(27)}║`);
    console.log(`║  Competing users           : ${String(USERS.length).padEnd(27)}║`);
    console.log(`║  Expected successes        : ${'3 (exactly — no more)'.padEnd(27)}║`);
    console.log(`║  Expected failures         : ${'2 (rejected after retries)'.padEnd(27)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  WALLET BALANCES BEFORE RACE:                          ║');
    USERS.forEach((u, i) => {
        const label = `  ${u.email}`.padEnd(42);
        console.log(`║  ${label}: $${String(walletsBefore[i]).padEnd(10)}          ║`);
    });
    console.log('╚══════════════════════════════════════════════════════════╝\n');

    return { tokens, initialQty, walletsBefore };
}

// ── Each VU fires checkout immediately — race begins ─────────────────
export default function (data) {
    const idx   = (__VU - 1) % data.tokens.length;
    const token = data.tokens[idx];
    const user  = USERS[idx].email;

    const startMs = Date.now();

    const res = http.post(
        `${BASE_URL}/api/orders/checkout?safe=1`,
        JSON.stringify({ shipping_address: 'Damascus' }),
        {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
            timeout: '30s', // allow time for up to 3 retry attempts
        }
    );

    const elapsed = ((Date.now() - startMs) / 1000).toFixed(2);
    const body    = JSON.parse(res.body);
    const ok      = body.success === true;
    const icon    = ok ? '✅' : '❌';
    const result  = ok ? 'SUCCESS' : 'REJECTED';

    // Detect if this was a retry case (server took longer than a clean first-try would)
    const likelyRetried = !ok && body.message?.includes('attempt');

    console.log(`\n── VU${__VU} (${user}) ─────────────────────────────────────`);
    console.log(`  ${icon} Result     : ${result}`);
    console.log(`  ⏱  Time taken  : ${elapsed}s${elapsed > 0.5 ? ' ← likely retried' : ''}`);
    console.log(`  💬 Message     : ${body.message ?? '-'}`);

    if (ok) {
        console.log(`  📦 Order ID   : ${body.data?.order?.id}`);
        console.log(`  💰 Wallet     : $${body.data?.wallet_balance} (was $${data.walletsBefore[idx]})`);
        const charged = (parseFloat(data.walletsBefore[idx]) - parseFloat(body.data?.wallet_balance)).toFixed(2);
        console.log(`  💳 Charged    : $${charged}`);
    } else {
        console.log(`  🔁 Retry note : Server retried up to 3x before returning this failure`);
        console.log(`  👉 Check laravel.log for "Checkout attempt N failed" entries`);
    }
}

// ── Teardown: full coherence report ─────────────────────────────────
export function teardown(data) {
    // small pause so all VUs finish writing before we read
    sleep(1);

    const productRes  = http.get(`${BASE_URL}/api/products/${PRODUCT_ID}`);
    const productBody = JSON.parse(productRes.body);
    const finalQty    = productBody.data?.inventory?.quantity ?? '?';
    const initialQty  = data.initialQty;

    const sold     = (typeof initialQty === 'number' && typeof finalQty === 'number')
                     ? initialQty - finalQty
                     : '?';

    // Fetch final wallet balances
    const walletsAfter = data.tokens.map((token) => {
        const res  = http.get(`${BASE_URL}/api/wallet`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        });
        const body = JSON.parse(res.body);
        return body.data?.balance ?? '?';
    });

    // Coherence verdicts
    const qtyOk         = finalQty === 0;
    const noNegative    = typeof finalQty === 'number' && finalQty >= 0;
    const soldExact     = sold === initialQty;

    // Count how many wallets were charged (balance decreased)
    const chargedCount  = USERS.reduce((acc, _, i) => {
        const before = parseFloat(data.walletsBefore[i]);
        const after  = parseFloat(walletsAfter[i]);
        return acc + (after < before ? 1 : 0);
    }, 0);

    const walletCoherent = chargedCount === sold;

    console.log('\n╔══════════════════════════════════════════════════════════╗');
    console.log('║            SAFE RACE — POST-TEST COHERENCE REPORT       ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  INVENTORY                                              ║');
    console.log(`║    Quantity before : ${String(initialQty).padEnd(34)}║`);
    console.log(`║    Quantity after  : ${String(finalQty).padEnd(34)}║`);
    console.log(`║    Units sold      : ${String(sold).padEnd(34)}║`);
    console.log(`║    ${noNegative ? '✅' : '❌'} No negative quantity   ${noNegative ? '(PASS)'.padEnd(30) : '(FAIL — oversold!)'.padEnd(30)}║`);
    console.log(`║    ${qtyOk      ? '✅' : '⚠️ '} Exactly zero remaining ${qtyOk ? '(PASS)'.padEnd(29) : `(qty=${finalQty})`.padEnd(29)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  WALLET BALANCES                                        ║');
    USERS.forEach((u, i) => {
        const before  = data.walletsBefore[i];
        const after   = walletsAfter[i];
        const charged = (parseFloat(before) - parseFloat(after)).toFixed(2);
        const tag     = parseFloat(charged) > 0 ? '💳 CHARGED' : '🔒 UNTOUCHED';
        const label   = u.email.padEnd(28);
        console.log(`║    ${label} $${String(before).padEnd(8)} → $${String(after).padEnd(8)} ${tag}  ║`);
    });
    console.log(`║    ${walletCoherent ? '✅' : '❌'} Charged wallets match sold units: ${String(chargedCount).padEnd(20)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  OVERALL VERDICT                                        ║');

    const allPass = noNegative && soldExact && walletCoherent;
    if (allPass) {
        console.log('║  ✅ DATA IS COHERENT                                    ║');
        console.log('║     Optimistic lock + retry prevented all race bugs.   ║');
        console.log('║     Exactly 3 sold, 2 rejected, wallets are correct.   ║');
    } else {
        console.log('║  ❌ DATA INCOHERENCE DETECTED                          ║');
        if (!noNegative)    console.log('║     → Inventory went negative (oversold)               ║');
        if (!soldExact)     console.log('║     → Sold units do not match stock delta              ║');
        if (!walletCoherent)console.log('║     → Wallet charges do not match successful orders    ║');
    }

    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  MANUAL DB VERIFICATION QUERIES:                        ║');
    console.log('║                                                          ║');
    console.log('║  -- Order count (must be 3):                            ║');
    console.log('║  SELECT COUNT(*) FROM orders o                          ║');
    console.log('║  JOIN order_items oi ON oi.order_id = o.id              ║');
    console.log('║  WHERE oi.product_id = 201;                             ║');
    console.log('║                                                          ║');
    console.log('║  -- Final inventory (must be 0):                        ║');
    console.log('║  SELECT quantity FROM inventories                        ║');
    console.log('║  WHERE product_id = 201;                                ║');
    console.log('║                                                          ║');
    console.log('║  -- Wallet breakdown:                                    ║');
    console.log('║  SELECT u.email, w.balance FROM users u                 ║');
    console.log('║  JOIN wallets w ON w.user_id = u.id                     ║');
    console.log("║  WHERE u.email LIKE 'racer%@example.com';               ║");
    console.log('║  → 3 at $440.01, 2 at $500.00                          ║');
    console.log('║                                                          ║');
    console.log('║  -- Retry evidence in logs:                             ║');
    console.log("║  grep 'Checkout attempt' storage/logs/laravel.log       ║");
    console.log('╚══════════════════════════════════════════════════════════╝\n');
}
