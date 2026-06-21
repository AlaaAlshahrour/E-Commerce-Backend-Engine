# K6 Test Commands Reference

> Run these in order: `migrate:fresh` → seed → `k6 run`
> Prepend `sudo` to sudo docker/k6 commands if your machine requires it.
```bash
sudo docker compose up -d
```
---
### PDF GENERATE
```bash
 sudo mv storage/app/private/public/invoices/invoice-1.pdf ~/Desktop/
```
```bash
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceDoubleCheckoutSeeder
```

```bash
k6 run tests/k6-final/checkout-pdf.js
```
## Race Conditions — Checkout

### Double Checkout

```bash
# unsafe
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceDoubleCheckoutSeeder
```

```bash
k6 run tests/k6-final/double-checkout-unsafe.js
```

```bash
# safe
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceDoubleCheckoutSeeder
```

```bash
k6 run tests/k6-final/double-checkout-safe.js
```

---

### Same Product — 2 Buyers, 1 Unit

```bash
# unsafe
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceSameProductSeeder
```

```bash
k6 run tests/k6-final/same-product-unsafe.js
```
```bash
# safe
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceSameProductSeeder
```

```bash
k6 run tests/k6-final/same-product-safe.js
```

---

### 5-User Race + Retry — 3 Units in Stock

```bash
# unsafe (overselling expected)
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceRetrySeeder
```

```bash
k6 run tests/k6-final/retry-race-unsafe.js
```
```bash
# safe (optimistic lock + retry)
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceRetrySeeder
```

```bash
k6 run tests/k6-final/retry-race-safe.js
```

---

## ACID — Atomicity

### Simulated Mid-Crash — Ghost Order Test

```bash
# unsafe (partial write — incoherent DB)
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=AcidTestSeeder
```

```bash
k6 run tests/k6-final/acid-unsafe.js
```
```bash
# safe (full rollback — pristine DB)
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=AcidTestSeeder
```

```bash
k6 run tests/k6-final/acid-safe.js
```

---

## Multi-Node — Distributed Lock

### 3-Node Double Checkout — File vs Redis Lock

> ⚠️ Uses port **8080** (load balancer). All other tests use port 80.
> Requires changing `CACHE_DRIVER` in `sudo docker-compose.yml` between runs.

```bash
# broken: isolated file cache per node
# 1. Set CACHE_DRIVER: file + isolated volumes per node in sudo docker-compose.yml
sudo docker compose down && sudo docker compose up -d
sudo docker compose exec app1 php artisan config:clear
sudo docker compose exec app2 php artisan config:clear
sudo docker compose exec app3 php artisan config:clear
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceDoubleCheckoutSeeder
k6 run tests/k6-final/multinode-double-checkout.js
```
```bash
# fixed: shared redis cache
# 2. Set CACHE_DRIVER: redis in sudo docker-compose.yml
sudo docker compose down && sudo docker compose up -d
sudo docker compose exec app1 php artisan config:clear
sudo docker compose exec app2 php artisan config:clear
sudo docker compose exec app3 php artisan config:clear
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceDoubleCheckoutSeeder
k6 run tests/k6-final/multinode-double-checkout.js
```

---

## Cart Races

### Add to Cart — Duplicate Insert Race

```bash
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceAddToCartSeeder
```

```bash
k6 run tests/k6-final/add-to-cart.js
```

---

### Cart Update — Lost Update Race

```bash
# unsafe
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceCartUpdateSeeder
```

```bash
k6 run tests/k6-final/cart-update-unsafe.js
```
```bash
# safe
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceCartUpdateSeeder
```

```bash
k6 run tests/k6-final/cart-update-safe.js
```

---

## Admin Inventory Race

### Admin Restock vs Customer Checkout

```bash
# unsafe (admin write silently overwrites checkout deduction)
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceInventoryAdminCustomerSeeder
```

```bash
k6 run tests/k6-final/admin-inventory-race-unsafe.js
```
```bash
# safe (admin blocked while checkout in progress)
sudo docker compose exec app1 php artisan migrate:fresh --seed --seeder=RaceInventoryAdminCustomerSeeder
```

```bash
k6 run tests/k6-final/admin-inventory-race-safe.js
```

---

