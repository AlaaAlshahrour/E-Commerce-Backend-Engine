# E-Commerce Backend Engine
### Parallel Programming & Concurrent Systems Project

> A scalable E-Commerce Backend API built with **Laravel**, focusing on parallel programming concepts, thread safety, load balancing, batch processing, and asynchronous job execution.

The project demonstrates how concurrent systems behave under heavy load and how synchronization mechanisms can prevent race conditions and inconsistent data states.

---

## 🚀 Project Overview

This project simulates a real-world e-commerce backend system with advanced concurrency handling techniques and distributed processing concepts.

The system includes:

- User authentication & authorization
- Product & category management
- Shopping cart system
- Wallet & payment processing
- Order checkout workflow
- Inventory management
- Invoice PDF generation
- Daily sales report processing
- Load balancing between multiple nodes
- Queue-based asynchronous processing
- Batch processing for large datasets

The implementation was designed to compare **unsafe concurrent execution** versus **safe synchronized execution** using database locks, cache locks, and queued background jobs.

---

## 🧠 Main Parallel Programming Concepts

### 1. Concurrent Access & Thread Safety

The project demonstrates several race condition scenarios and their solutions:

**✔ Safe vs Unsafe Checkout**

Implemented using:
- `DB::transaction()`
- `lockForUpdate()`
- `Cache::lock()`

This prevents:
- Double checkout
- Overselling products
- Wallet balance corruption
- Simultaneous inventory modification

**Example from the project:**

```php
$wallet = $user->wallet()->lockForUpdate()->first();
```

```php
$perUserLock = Cache::lock("checkout:user:{$user->id}");
```

---

### 2. Messaging Queues & Asynchronous Processing

The system uses **Laravel Queues** to execute heavy tasks asynchronously.

**Background Jobs:**
- Invoice PDF generation
- Daily sales report processing

Implemented using:

```php
class GenerateInvoicePdfJob implements ShouldQueue
```

```php
class ProcessDailySalesJob implements ShouldQueue
```

This improves:
- API responsiveness
- User experience
- Scalability under load

---

### 3. Batch Processing

The project compares:
- Normal processing
- Chunked batch processing

**Chunked Processing** — implemented using:

```php
chunkById(1000, function($orders) {
    ...
});
```

The batch processor reduces:
- Peak memory consumption
- Large dataset loading overhead

It also measures:
- Execution time
- Memory usage
- Batch statistics

---

### 4. Load Balancing & Scaling

The project simulates multiple application nodes:
- Node-1
- Node-2
- Node-3

A custom controller manages node health and recovery:

```php
docker start
docker stop
```

This demonstrates:
- Distributed system behavior
- Fault tolerance
- Node recovery
- Basic load balancing strategies

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8+, Laravel 12 |
| **Database** | MySQL |
| **Queue & Async** | Laravel Queue System |
| **Authentication** | Laravel Sanctum |
| **PDF Generation** | barryvdh/laravel-dompdf |
| **Containers** | Docker |

---

## 📦 Main Features

### Authentication
- Register / Login / Logout
- Token authentication

### Products & Categories
- CRUD operations
- Pagination
- Search & filtering

### Shopping Cart
- Add products
- Update quantities
- Remove products
- Prevent duplicate entries

### Wallet System
- Top-up balance
- Transaction history
- Payment handling

### Orders
- Checkout flow
- Payment processing
- Invoice generation

### Inventory Management
- Safe stock updates
- Concurrent inventory protection

### Reporting
- Daily sales report generation
- Batch vs normal processing comparison
- PDF export

---

## ⚙️ Installation

### 1. Clone Repository
```bash
git clone https://github.com/your-username/e-commerce-backend-engine.git
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment
```bash
cp .env.example .env
```
> Update database credentials inside `.env`

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Run Migrations & Seeders
```bash
php artisan migrate:fresh --seed
```

### 6. Create Storage Link
```bash
php artisan storage:link
```

### 7. Start Queue Worker
```bash
php artisan queue:work
```

### 8. Run Application
```bash
php artisan serve
```

---

## 🧪 Testing Concurrent Scenarios

The project contains special seeders for race condition demonstrations:

| Seeder | Purpose |
|---|---|
| `RaceSameProductSeeder` | Overselling simulation |
| `RaceDoubleCheckoutSeeder` | Double checkout race |
| `RaceInventoryAdminCustomerSeeder` | Admin/customer inventory conflict |
| `RaceAddToCartSeeder` | Duplicate cart insertion |
| `RaceCartUpdateSeeder` | Concurrent cart updates |

Run a specific seeder:

```bash
php artisan db:seed --class=RaceSameProductSeeder
```

---

## 📊 Performance Monitoring

The batch processing system measures:
- Peak memory usage
- Execution time
- Batch count
- Revenue statistics
- Order statistics

This allows direct comparison between:
- Traditional processing
- Parallel / chunked processing

---

## 🔒 Security & Protection

The system includes:
- Rate limiting
- Request validation
- Role-based authorization
- Transaction safety
- Database locking
- Cache locking

**Example rate limiters:**

```php
RateLimiter::for('checkout', function(Request $request) {
    return Limit::perMinute(5);
});
```

---

## 📁 Project Architecture

The project follows Laravel's layered architecture:

```
Controllers
│
├── Services
│   └── Business Logic
│
├── Repositories
│
├── Jobs
│
├── Processors
│
└── Models
```

This separation improves:
- Scalability
- Maintainability
- Testability

---

## 👥 Team Members

- عبد الرحمن مالك الجمعات
- محمد علاء عبد الرحمن الشحرور
- منار ماهر عجاج الكردي
- عبد الله محمد خير الكسم
- محمد علاء الدين عايدي

**Supervisor:** 

م. حذيفة عقيل

---

## 📚 Educational Purpose

This project was developed as part of the **Parallel Programming** course to demonstrate practical applications of:

- Concurrent programming
- Synchronization
- Distributed systems
- Queue systems
- Batch processing
- Load balancing
- Thread safety
- Performance optimization

---

## 📄 License

This project is intended for **educational and academic purposes only**.
