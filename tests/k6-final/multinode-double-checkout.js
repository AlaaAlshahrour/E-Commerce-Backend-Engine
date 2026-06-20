import http from 'k6/http';
import { sleep } from 'k6';

/**
 * ═══════════════════════════════════════════════════════════════════
 *  SCENARIO: Multi-Node Double Checkout
 * ═══════════════════════════════════════════════════════════════════
 *
 *  SETUP:
 *    3 Laravel nodes behind an Nginx least_conn load balancer.
 *    Same user fires 6 checkout requests simultaneously via
 *    http.batch() — the LB distributes them across all 3 nodes.
 *    Each node adds an X-Node header so we can see which node
 *    served each request.
 *
 *  THE CRITICAL QUESTION — CACHE DRIVER:
 *
 *    CACHE_DRIVER=file  (broken across nodes):
 *      Each node keeps its own lock file on its own filesystem.
 *      Node-1 acquires  "checkout:user:4" on its own disk.
 *      Node-2 also acquires "checkout:user:4" on its own disk.
 *      Both think they hold the lock. Double checkout succeeds.
 *      ❌ Cache::lock() gives a FALSE sense of safety.
 *
 *    CACHE_DRIVER=redis (shared lock store):
 *      All nodes talk to the same Redis. Only ONE node can hold
 *      the lock at a time. Concurrent requests on other nodes
 *      get "Checkout already in progress".
 *      ✅ Cache::lock() works correctly.
 *
 *  WHAT YOU SHOULD SEE:
 *    FILE DRIVER  → multiple SUCCESS responses from different nodes
 *                   wallet overcharged, multiple orders created
 *    REDIS DRIVER → exactly 1 SUCCESS, others "already in progress"
 *
 *  HOW TO SWITCH DRIVERS (to compare):
 *    In your .env:
 *      CACHE_DRIVER=file   → run test → see broken behaviour
 *      CACHE_DRIVER=redis  → run test → see correct behaviour
 *    Then restart containers: docker compose restart app1 app2 app3
 *
 *  SEEDER:   php artisan db:seed --class=RaceDoubleCheckoutSeeder
 *  ENDPOINT: POST /api/orders/checkout?safe=1
 *
 *  Run: k6 run multinode-double-checkout.js
 * ═══════════════════════════════════════════════════════════════════
 */

export const options = {
    scenarios: {
        multinode_double_checkout: {
            executor: 'shared-iterations',
            vus: 1,        // 1 VU using http.batch — fires all 6 in parallel
            iterations: 1,
            maxDuration: '30s',
        },
    },
};

const BASE_URL = 'http://localhost:8080';   // hits the load balancer port

export function setup() {
    const res  = http.post(
        `${BASE_URL}/api/login`,
        JSON.stringify({ email: 'double@example.com', password: 'password' }),
        { headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' } }
    );
    const body = JSON.parse(res.body);
    if (!body.data?.token) throw new Error(`Login failed: ${res.body}`);

    const walletRes  = http.get(`${BASE_URL}/api/wallet`, {
        headers: { 'Authorization': `Bearer ${body.data.token}`, 'Accept': 'application/json' },
    });
    const walletBody = JSON.parse(walletRes.body);
    const initBal    = walletBody.data?.balance ?? '?';

    const ordersRes  = http.get(`${BASE_URL}/api/orders`, {
        headers: { 'Authorization': `Bearer ${body.data.token}`, 'Accept': 'application/json' },
    });
    const ordersBody = JSON.parse(ordersRes.body);
    const initOrders = Array.isArray(ordersBody.data) ? ordersBody.data.length : 0;

    console.log('\n╔══════════════════════════════════════════════════════════╗');
    console.log('║       MULTI-NODE DOUBLE CHECKOUT — PRE-TEST STATE       ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  Infrastructure:                                        ║');
    console.log('║    • 3 Laravel nodes (app1, app2, app3)                 ║');
    console.log('║    • Nginx least_conn load balancer                     ║');
    console.log('║    • X-Node header shows which node served each request ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  The lock problem:                                      ║');
    console.log('║    CACHE_DRIVER=file  → each node has its OWN lock file ║');
    console.log('║                         Lock on Node-1 ≠ Lock on Node-2 ║');
    console.log('║                         Both acquire "the lock" → RACE  ║');
    console.log('║    CACHE_DRIVER=redis → all nodes share ONE lock store  ║');
    console.log('║                         Only one can hold the lock → OK ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log(`║  Wallet balance (before)  : $${String(initBal).padEnd(29)}║`);
    console.log(`║  Orders (before)          : ${String(initOrders).padEnd(30)}║`);
    console.log('║  Firing 6 simultaneous requests via http.batch()...     ║');
    console.log('╚══════════════════════════════════════════════════════════╝\n');

    return { token: body.data.token, initBal, initOrders };
}

export default function (data) {
    const req = {
        method: 'POST',
        url:    `${BASE_URL}/api/orders/checkout?safe=1`,
        body:   JSON.stringify({ shipping_address: 'Damascus', 'break-trans': false }),
        params: {
            headers: {
                'Authorization': `Bearer ${data.token}`,
                'Content-Type':  'application/json',
                'Accept':        'application/json',
            },
        },
    };

    // Fire 6 requests simultaneously — LB distributes across 3 nodes
    // With least_conn and 3 nodes, we expect ~2 requests per node
    const responses = http.batch([req, req, req, req, req, req]);

    console.log('\n── Results per request ─────────────────────────────────────');

    let successCount = 0;
    const nodeHits   = {};   // track which nodes were hit

    responses.forEach((res, i) => {
        const body   = JSON.parse(res.body);
        const ok     = body.success === true;
        const node = res.headers['X-Node'] ?? body.node ?? 'unknown';
        const icon   = ok ? '✅' : '❌';

        nodeHits[node] = (nodeHits[node] ?? 0) + 1;
        if (ok) successCount++;

        console.log(`  ${icon} Request ${i + 1} → Node: ${node.padEnd(8)} | ${ok ? 'SUCCESS' : 'FAILED '} | ${body.message ?? '-'}${ok ? ` | Order #${body.data?.order?.id} | Wallet: $${body.data?.wallet_balance}` : ''}`);
    });

    console.log('\n── Node distribution ───────────────────────────────────────');
    Object.entries(nodeHits).forEach(([node, count]) => {
        console.log(`  ${node}: ${count} request(s)`);
    });

    const multipleNodes = Object.keys(nodeHits).length > 1;
    console.log(`\n  Requests spread across multiple nodes: ${multipleNodes ? '✅ YES (good for demonstrating the lock problem)' : '⚠️  NO (all hit same node — try again or check LB config)'}`);
    console.log(`  Successful checkouts: ${successCount}`);

    if (successCount > 1) {
        console.log(`  ❌ DOUBLE CHECKOUT OCCURRED — ${successCount} orders created for same cart`);
        console.log(`     This means Cache::lock() is NOT working across nodes`);
        console.log(`     → Likely CACHE_DRIVER=file (each node has its own lock)`);
    } else if (successCount === 1) {
        console.log(`  ✅ EXACTLY ONE SUCCESS — lock is working correctly`);
        console.log(`     → Likely CACHE_DRIVER=redis (shared lock across nodes)`);
    } else {
        console.log(`  ⚠️  ZERO SUCCESSES — check if cart was already empty or re-seed`);
    }
}

export function teardown(data) {
    sleep(1);

    const walletRes  = http.get(`${BASE_URL}/api/wallet`, {
        headers: { 'Authorization': `Bearer ${data.token}`, 'Accept': 'application/json' },
    });
    const walletBody = JSON.parse(walletRes.body);
    const finalBal   = walletBody.data?.balance ?? '?';

    const ordersRes  = http.get(`${BASE_URL}/api/orders`, {
        headers: { 'Authorization': `Bearer ${data.token}`, 'Accept': 'application/json' },
    });
    const ordersBody = JSON.parse(ordersRes.body);
    const finalOrders = Array.isArray(ordersBody.data) ? ordersBody.data.length : 0;
    const newOrders   = finalOrders - data.initOrders;

    const initBalNum  = parseFloat(data.initBal);
    const finalBalNum = parseFloat(finalBal);
    const totalCharged = (initBalNum - finalBalNum).toFixed(2);

    // cart total is $154.97 (products 1+2+3)
    const expectedCharge = 154.97;
    const overcharged    = parseFloat(totalCharged) > expectedCharge + 0.01;

    console.log('\n╔══════════════════════════════════════════════════════════╗');
    console.log('║       MULTI-NODE DOUBLE CHECKOUT — POST-TEST STATE      ║');
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  WALLET                                                 ║');
    console.log(`║    Before      : $${String(data.initBal).padEnd(40)}║`);
    console.log(`║    After       : $${String(finalBal).padEnd(40)}║`);
    console.log(`║    Total charged: $${String(totalCharged).padEnd(39)}║`);
    console.log(`║    Expected    : $${String(expectedCharge).padEnd(40)}║`);
    console.log(`║    ${overcharged ? '❌ OVERCHARGED' : '✅ Correct charge'} — ${overcharged ? 'wallet debited more than one order' : 'only one order worth charged'}${' '.repeat(overcharged ? 1 : 4)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  ORDERS                                                 ║');
    console.log(`║    Before      : ${String(data.initOrders).padEnd(39)}║`);
    console.log(`║    After       : ${String(finalOrders).padEnd(39)}║`);
    console.log(`║    New orders  : ${String(newOrders).padEnd(39)}║`);
    console.log(`║    ${newOrders > 1 ? '❌ DOUBLE ORDER' : newOrders === 1 ? '✅ Exactly one order' : '⚠️  No new orders'} created${' '.repeat(newOrders > 1 ? 30 : newOrders === 1 ? 26 : 28)}║`);
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  VERDICT                                                ║');
    if (newOrders > 1 || overcharged) {
        console.log('║  ❌ MULTI-NODE RACE CONDITION CONFIRMED                 ║');
        console.log('║     Cache::lock() did NOT protect across nodes.        ║');
        console.log('║     Fix: set CACHE_DRIVER=redis in all .env files      ║');
        console.log('║     and ensure all nodes share the same Redis instance.║');
    } else if (newOrders === 1 && !overcharged) {
        console.log('║  ✅ MULTI-NODE LOCK WORKING CORRECTLY                  ║');
        console.log('║     Cache::lock() via Redis is protecting across nodes.║');
    } else {
        console.log('║  ⚠️  Inconclusive — re-seed and retry                  ║');
    }
    console.log('╠══════════════════════════════════════════════════════════╣');
    console.log('║  TO COMPARE BEHAVIOURS:                                 ║');
    console.log('║                                                          ║');
    console.log('║  1. Set CACHE_DRIVER=file in .env on all nodes          ║');
    console.log('║     docker compose restart app1 app2 app3               ║');
    console.log('║     php artisan db:seed --class=RaceDoubleCheckoutSeeder ║');
    console.log('║     k6 run multinode-double-checkout.js                 ║');
    console.log('║     → Expect: multiple successes (broken)               ║');
    console.log('║                                                          ║');
    console.log('║  2. Set CACHE_DRIVER=redis in .env on all nodes         ║');
    console.log('║     docker compose restart app1 app2 app3               ║');
    console.log('║     php artisan db:seed --class=RaceDoubleCheckoutSeeder ║');
    console.log('║     k6 run multinode-double-checkout.js                 ║');
    console.log('║     → Expect: exactly 1 success (correct)              ║');
    console.log('╚══════════════════════════════════════════════════════════╝\n');
}
