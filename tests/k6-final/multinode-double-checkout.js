import http from 'k6/http';
import { sleep } from 'k6';

/**

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
        const ok     = body.successful === true;
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

    } else if (successCount === 1) {
        console.log(`  ✅ EXACTLY ONE SUCCESS — lock is working correctly`);
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

    console.log('╚══════════════════════════════════════════════════════════╝\n');
}
