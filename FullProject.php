// === FULL PROJECT COMPACT EXPORT ===

// === [Controllers] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\Api\AuthController.php =====
namespace App\Http\Controllers\Api;class AuthController extends Controller{public function register(RegisterRequest $request): JsonResponse{$data = $request->validated();$user = User::create([
'name' => $data['name'],'email' => $data['email'],'password' => Hash::make($request->password),]);$token = $user->createToken('auth_token')->plainTextToken;return ResponseHelper::jsonResponse([
'user' => $user,'token' => $token,],'User registered successfully',201);}public function login(LoginRequest $request): JsonResponse{$data = $request->validated();if(! Auth::attempt($data)){return ResponseHelper::jsonResponse(null,'Invalid credentials',401,false);}$user = Auth::user();$token = $user->createToken('auth_token')->plainTextToken;return ResponseHelper::jsonResponse([
'user' => $user,'token' => $token,],'Login successful');}public function logout(Request $request): JsonResponse{$request->user()->currentAccessToken()->delete();return ResponseHelper::jsonResponse(null,'Logged out successfully');}public function me(Request $request): JsonResponse{return ResponseHelper::jsonResponse($request->user(),'User profile retrieved');}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\Api\CartController.php =====
namespace App\Http\Controllers\Api;class CartController extends Controller{protected CartService $cartService;public function __construct(CartService $cartService){$this->cartService = $cartService;}public function add(int $product_id,Request $request){$request->validate([
'quantity' => 'required|integer|min:1',]);$result = $this->cartService->addProductToCart($product_id,$request->input('quantity'));if(!$result['success']){return ResponseHelper::jsonResponse('',$result['message'],422,false);}return ResponseHelper::jsonResponse('',$result['message'],200);}public function getCartProducts(){$user = Auth::user();$result = $this->cartService->getAllProductsInCart($user);if(!$result['success']){return ResponseHelper::jsonResponse([],$result['message'],404,false);}return ResponseHelper::jsonResponse($result['data'],$result['message']);}public function deleteAll(){$this->cartService->deleteAll();return ResponseHelper::jsonResponse(null,'Cart cleared successfully');}public function deleteProducts(Request $request){$request->validate([
'product_ids' => 'required|array|min:1','product_ids.*' => 'integer',]);$result = $this->cartService->deleteProducts($request->input('product_ids'));if(!$result['success']){ResponseHelper::jsonResponse(null,$result['message'],422,false);}$data = ['deleted_ids' => $result['deleted_ids'],'not_found_ids' => $result['not_found_ids']];return ResponseHelper::jsonResponse($data,$result['message']);}public function update(Request $request,int $productId){$request->validate([
'quantity' => 'required|integer|min:1','safe' => 'sometimes'
]);$safe = $request->query('safe')== "1";Log::info($safe);$result = $safe ? $this->cartService
->updateProductQuantitySafe($productId,$request->quantity):
$this->cartService->updateProductQuantityUnsafe($productId,$request->quantity);if(!$result['success']){return ResponseHelper::jsonResponse(null,$result['message'],422,false);}return ResponseHelper::jsonResponse(null,$result['message']);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\Api\CategoryController.php =====
namespace App\Http\Controllers\Api;class CategoryController extends Controller{public function index(){$categories = Category::all();return ResponseHelper::jsonResponse($categories,'Categories retrieved successfully');}public function store(StoreCategoryRequest $request){$category = Category::create($request->validated());return ResponseHelper::jsonResponse($category,'Category created successfully',201);}public function show(Category $category){return ResponseHelper::jsonResponse($category,'Category retrieved successfully');}public function update(UpdateCategoryRequest $request,Category $category){$category->update($request->validated());return ResponseHelper::jsonResponse($category,'Category updated successfully');}public function destroy(Category $category){$category->delete();return ResponseHelper::jsonResponse(null,'Category deleted successfully');}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\Controller.php =====
namespace App\Http\Controllers;abstract class Controller{}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\DailySalesReportController.php =====
namespace App\Http\Controllers;class DailySalesReportController extends Controller{public function show(string $date){$report = DailySalesReport::where('date',$date)->first();if(! $report){return response()->json(['message' => 'Report not found for the given date.'],404);}return response()->json([
'date' => $report->date,'total_orders' => $report->total_orders,'total_revenue' => $report->total_revenue,'export_start_time' => $report->export_start_time,'export_end_time' => $report->export_end_time,'export_duration_seconds' => $report->export_end_time && $report->export_start_time
? $report->export_end_time->diffInSeconds($report->export_start_time): null,'pdf_url' => Storage::url($report->pdf_path),]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\Api\InventoryController.php =====
namespace App\Http\Controllers\Api;class InventoryController extends Controller{protected $inventoryService;public function __construct(InventoryService $inventoryService){$this->inventoryService = $inventoryService;}public function index(){$result = $this->inventoryService->getAll();if(isset($result['message'])){return ResponseHelper::jsonResponse(null,$result['message'],404,false);}return ResponseHelper::jsonResponse($result['data'],'',201);}public function show(int $productId){$result = $this->inventoryService->getByProductId($productId);if(isset($result['message'])){return ResponseHelper::jsonResponse(null,$result['message'],404,false);}return ResponseHelper::jsonResponse($result['data']);}public function update(int $productId,Request $request){$request->validate([
'quantity' => 'required|integer|min:0',]);$safe = $request->query('safe')== "1";$result = $safe ? $this->inventoryService->updateQuantitySafe($productId,$request->input('quantity')):
$this->inventoryService->updateQuantityUnsafe($productId,$request->input('quantity'));if(!$result['success']){return ResponseHelper::jsonResponse(null,$result['message'],404);}return ResponseHelper::jsonResponse([],$result['message']);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\NodeController.php =====
namespace App\Http\Controllers;class NodeController extends Controller{private array $nodes = [
'Node-1' => [
'fpm' => 'app-node-1','web' => 'app-node-1-web',],'Node-2' => [
'fpm' => 'app-node-2','web' => 'app-node-2-web',],'Node-3' => [
'fpm' => 'app-node-3','web' => 'app-node-3-web',],];public function status(): JsonResponse{$result = [];foreach($this->nodes as $nodeName => $containers){$webRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}'{$containers['web']}2>/dev/null"))=== 'true';$fpmRunning = trim(shell_exec("docker inspect -f '{{.State.Running}}'{$containers['fpm']}2>/dev/null"))=== 'true';$result[$nodeName] = [
'running' => $webRunning && $fpmRunning,'web_running' => $webRunning,'fpm_running' => $fpmRunning,'web_container' => $containers['web'],'fpm_container' => $containers['fpm'],];}return response()->json([
'successful' => true,'node' => env('NODE_NAME',gethostname()),'nodes' => $result,]);}public function stop(string $node): JsonResponse{if(!isset($this->nodes[$node])){return response()->json([
'successful' => false,'message' => 'Node not found',],404);}shell_exec("docker stop{$this->nodes[$node]['web']}2>/dev/null");shell_exec("docker stop{$this->nodes[$node]['fpm']}2>/dev/null");return response()->json([
'successful' => true,'message' => "{$node}stopped",'node' => env('NODE_NAME',gethostname()),]);}public function start(string $node): JsonResponse{if(!isset($this->nodes[$node])){return response()->json([
'successful' => false,'message' => 'Node not found',],404);}shell_exec("docker start{$this->nodes[$node]['fpm']}2>/dev/null");sleep(2);shell_exec("docker start{$this->nodes[$node]['web']}2>/dev/null");return response()->json([
'successful' => true,'message' => "{$node}started",'node' => env('NODE_NAME',gethostname()),]);}public function restoreAll(): JsonResponse{foreach($this->nodes as $containers){shell_exec("docker start{$containers['fpm']}2>/dev/null");}sleep(2);foreach($this->nodes as $containers){shell_exec("docker start{$containers['web']}2>/dev/null");}return response()->json([
'successful' => true,'message' => 'All nodes restored','node' => env('NODE_NAME',gethostname()),]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\Api\OrderController.php =====
namespace App\Http\Controllers\Api;class OrderController extends Controller{public function __construct(protected OrderService $orderService){}public function index(){$result = $this->orderService->getUserOrders();if(!$result['success']){return ResponseHelper::jsonResponse(null,$result['message'],404,false);}return ResponseHelper::jsonResponse($result['data'],$result['message']);}public function show(int $id){$result = $this->orderService->getOrderById($id);if(!$result['success']){return ResponseHelper::jsonResponse(null,$result['message'],404,false);}return ResponseHelper::jsonResponse($result['data'],$result['message']);}public function updateStatus(int $id,Request $request){$request->validate([
'status' => 'required|in:Processing,Canceled,Completed,pending',]);$result = $this->orderService->updateStatus($id,$request->input('status'));if(!$result['success']){ResponseHelper::jsonResponse(null,$result['message'],422,false);}return ResponseHelper::jsonResponse($result['data'],$result['message']);}public function checkout(CheckoutRequest $request){$data = $request->validated();$safe = $request->query('safe')== "1";$result = $safe ? $this->orderService->checkoutSafe($data): $this->orderService->checkoutUnsafe($data);if(!$result['success']){$message = isset($result['message'])? $result['message'] : 'An error occurred';return ResponseHelper::jsonResponse(null,$message,422,false);}return ResponseHelper::jsonResponse($result['data'],$result['message'],201);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\Api\ProductController.php =====
namespace App\Http\Controllers\Api;class ProductController extends Controller{public function index(Request $request){$query = Product::with('category');if($request->category_id){$query->where('category_id',$request->category_id);}if($request->min_price){$query->where('price','>=',$request->min_price);}if($request->max_price){$query->where('price','<=',$request->max_price);}if($request->search){$query->where('name','like',"%{$request->search}%");}$products = $query->paginate(10);return ResponseHelper::jsonResponse($products,'Products retrieved successfully');}public function store(StoreProductRequest $request){$data = $request->validated();if($request->hasFile('image')){$path = $request->file('image')->store('products','public');$data['photo_url'] = Storage::url($path);}unset($data['image']);$product = Product::create($data);Inventory::create([
'product_id' => $product->id,'quantity' => 0,]);return ResponseHelper::jsonResponse($product,'Product created successfully',201);}public function show(Product $product){return ResponseHelper::jsonResponse($product->load('category'),'Product retrieved successfully');}public function update(UpdateProductRequest $request,Product $product){$data = $request->validated();if($request->hasFile('image')){if($product->photo_url){$oldPath = str_replace('/storage/','',$product->photo_url);Storage::disk('public')->delete($oldPath);}$path = $request->file('image')->store('products','public');$data['photo_url'] = Storage::url($path);}unset($data['image']);$product->update($data);return ResponseHelper::jsonResponse($product,'Product updated successfully');}public function destroy(Product $product){if($product->photo_url){$oldPath = str_replace('/storage/','',$product->photo_url);Storage::disk('public')->delete($oldPath);}$product->delete();return ResponseHelper::jsonResponse(null,'Product deleted successfully');}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Controllers\Api\WalletController.php =====
namespace App\Http\Controllers\Api;class WalletController extends Controller{public function show(Request $request){$wallet = $request->user()->wallet;if(! $wallet){return ResponseHelper::jsonResponse([],'Wallet not found',404);}$transactions = Transaction::where('wallet_id',$wallet->id)->orderBy('created_at','desc')->limit(10)->get();return ResponseHelper::jsonResponse([
'balance' => $wallet->balance,'transactions' => $transactions,]);}public function topUp(TopUpRequest $request){$data = $request->validated();$user = $request->user();$wallet = $user->wallet;if(! $wallet){$wallet = Wallet::create([
'user_id' => $user->id,'balance' => 0,'is_active' => true,]);}if(! $wallet || ! $wallet->is_active){return ResponseHelper::jsonResponse([],'Wallet not found or inactive',422);}DB::transaction(function()use($wallet,$request,$data){$amount = $data['amount'];$balanceBefore = $wallet->balance;$wallet->increment('balance',$amount);Transaction::create([
'wallet_id' => $wallet->id,'order_id' => null,'amount' => $amount,'balance_before' => $balanceBefore,'balance_after' => $balanceBefore + $amount,'type' => 'deposit','status' => 'completed',]);});return ResponseHelper::jsonResponse(['balance' => $wallet->fresh()->balance],'Top up successful');}}

// === [Services] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Services\CartService.php =====
namespace App\Services;class CartService{protected $cartRepository;public function __construct(CartRepository $cartRepository){$this->cartRepository = $cartRepository;}public function addProductToCart(int $product_id,int $quantity): array{$user = Auth::user();$cart = $user->cart ?? $this->cartRepository->createCart($user->id);$product = $this->cartRepository->getProduct($product_id);if(!$product){return ['success' => false,'message' => 'Product not found'];}$availableStock = $product->inventory?->quantity ?? 0;if($availableStock === 0){return ['success' => false,'message' => 'No stock available'];}if($availableStock < $quantity){return ['success' => false,'message' => "Only{$availableStock}available"];}$alreadyInCart = $cart->cartItems()->where('product_id',$product_id)->exists();if($alreadyInCart){return ['success' => false,'message' => 'Product already in cart'];}try{$this->cartRepository->addProductToCart($cart,$product_id,$quantity);}catch(QueryException $e){if($e->getCode()=== '23000'){return ['success' => false,'message' => 'Product already in cart'];}return ['success' => false,'message' => 'An Error Occurred'];}return ['success' => true,'message' => 'Product added to cart'];}public function getAllProductsInCart(User $user): array{$cart = $user->cart;if(!$cart){return ['success' => false,'message' => 'Cart is empty'];}$cartData = $this->cartRepository->getCartProducts($cart);if($cartData['products']->isEmpty()){return ['success' => false,'message' => 'Cart is empty'];}return ['success' => true,'message' => 'Products Retrieved','data' => $cartData];}public function deleteAll(): void{$user = Auth::user();$this->cartRepository->deleteAll($user);}public function deleteProducts(array $productIds): array{$user = Auth::user();$cart = $user->cart;if(!$cart){return ['success' => false,'message' => 'Cart is empty'];}$existingIds = $cart->cartItems()->whereIn('product_id',$productIds)->pluck('product_id')->toArray();$notFoundIds = array_values(array_diff($productIds,$existingIds));if(!empty($existingIds)){$this->cartRepository->deleteProducts($cart,$existingIds);}return [
'success' => true,'message' => 'Operation completed','deleted_ids' => $existingIds,'not_found_ids' => $notFoundIds,];}public function updateProductQuantityUnsafe(int $productId,int $quantity): array{$user = Auth::user();$cart = $user->cart;if(!$cart){return ['success' => false,'message' => 'Cart is empty'];}$cartItem = $cart->cartItems()->where('product_id',$productId)->first();if(!$cartItem){return ['success' => false,'message' => 'Product not found in cart'];}$product = $this->cartRepository->getProduct($productId);$availableStock = $product->inventory?->quantity ?? 0;if($availableStock === 0){return ['success' => false,'message' => 'No stock available'];}if($quantity > $availableStock){return [
'success' => false,'message' => "Only{$availableStock}available",];}$success = $this->cartRepository->updateProductQuantity($cart,$productId,$quantity);return ['success' => true,'message' => 'Quantity updated successfully'];}public function updateProductQuantitySafe(int $productId,int $quantity): array{$user = Auth::user();$cart = $user->cart;$lock = Cache::lock("update_cart:$cart->id:$productId");try{if(!$lock->get())return ['success' => false,'message' => 'Quantity updated From Another DeviceL'];if(!$cart){return ['success' => false,'message' => 'Cart is empty'];}$cartItem = CartItem::where('product_id',$productId)->where('cart_id',$cart->id)->lockForUpdate()->first();if(!$cartItem){return ['success' => false,'message' => 'Product not found in cart'];}$product = $this->cartRepository->getProduct($productId);$availableStock = $product->inventory?->quantity ?? 0;if($availableStock === 0){return ['success' => false,'message' => 'No stock available'];}if($quantity > $availableStock){return [
'success' => false,'message' => "Only{$availableStock}available",];}$success = $this->cartRepository->updateProductQuantity($cart,$productId,$quantity);$lock->release();if(!$success){return ['success' => false,'message' => 'Quantity updated From Another Device'];}return ['success' => true,'message' => 'Quantity updated successfully'];}finally{$lock->release();}}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Services\DailySalesProcessingService.php =====
namespace App\Services;class DailySalesProcessingService{public function __construct(private ChunkedSalesProcessor $chunkedProcessor,private NormalSalesProcessor $normalProcessor,){}public function process(string $date,ProcessingMode $mode): array{return match($mode){ProcessingMode::Batch => $this->processBatch($date),ProcessingMode::Normal => $this->processNormal($date),ProcessingMode::Compare => $this->processCompare($date),};}private function processBatch(string $date): array{$result = $this->chunkedProcessor->process($date);return [
'mode' => ProcessingMode::Batch->value,'date' => $date,'batch_result' => $result,];}private function processNormal(string $date): array{$result = $this->normalProcessor->process($date);return [
'mode' => ProcessingMode::Normal->value,'date' => $date,'normal_result' => $result,];}private function processCompare(string $date): array{$batchResult = $this->chunkedProcessor->process($date);$normalResult = $this->normalProcessor->process($date);if($normalResult['skipped'] ?? false){return [
'mode' => ProcessingMode::Compare->value,'date' => $date,'normal_result' => $normalResult,'batch_result' => null,'comparison' => null,'normal_skipped' => true,];}$comparison = [
'memory_reduction_percent' => round((($normalResult['peak_memory'] - $batchResult['peak_memory'])/ $normalResult['peak_memory'])* 100,2),'speed_improvement_percent' => round((($normalResult['execution_time'] - $batchResult['execution_time'])/ $normalResult['execution_time'])* 100,2),'normal_execution_time' => $normalResult['execution_time'],'batch_execution_time' => $batchResult['execution_time'],'normal_peak_memory' => $normalResult['peak_memory'],'batch_peak_memory' => $batchResult['peak_memory'],];$batchStats = $this->calculateBatchStatistics($batchResult);return [
'mode' => ProcessingMode::Compare->value,'date' => $date,'normal_result' => $normalResult,'batch_result' => $batchResult,'comparison' => $comparison,'batch_stats' => $batchStats,'normal_skipped' => false,];}private function calculateBatchStatistics(array $batchResult): array{$batchesMetrics = $batchResult['batches_metrics'] ?? [];if(empty($batchesMetrics)){return [
'average_batch_memory' => 0,'largest_batch_memory' => 0,'smallest_batch_memory' => 0,'batch_count' => 0,'batch_size' => 0,];}$memoryDeltas = array_column($batchesMetrics,'memory_delta_real_mb');return [
'average_batch_memory' => round(array_sum($memoryDeltas)/ count($memoryDeltas),4),'largest_batch_memory' => round(max($memoryDeltas),4),'smallest_batch_memory' => round(min($memoryDeltas),4),'batch_count' => count($batchesMetrics),'batch_size' => $batchesMetrics[0]['orders_count'] ?? 0,];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Services\InventoryService.php =====
namespace App\Services;class InventoryService{protected $inventoryRepository;public function __construct(InventoryRepository $inventoryRepository){$this->inventoryRepository = $inventoryRepository;}public function getAll(): array{$inventories = $this->inventoryRepository->getAll();if($inventories->isEmpty()){return ['message' => 'No inventory found'];}return ['data' => $inventories];}public function getByProductId(int $productId): array{$inventory = $this->inventoryRepository->getByProductId($productId);if(!$inventory){return ['message' => 'Product not found in inventory'];}return [
'data' => [
'product_id' => $inventory->product->id,'product_name' => $inventory->product->name,'price' => $inventory->product->price,'photo_url' => $inventory->product->photo_url,'quantity' => $inventory->quantity,]
];}public function updateQuantityUnsafe(int $productId,int $quantity): array{$inventory = $this->inventoryRepository->getByProductId($productId);sleep(1);if(!$inventory){return ['success' => false,'message' => 'Product not found in inventory'];}$this->inventoryRepository->incrementQuantity($productId,$quantity);return ['success' => true,'message' => 'Inventory updated successfully'];}public function updateQuantitySafe(int $productId,int $quantity): array{return DB::transaction(function()use($productId,$quantity){$inventory = Inventory::where('product_id',$productId)->lockForUpdate()->first();if(!$inventory){return ['success' => false,'message' => 'Product not found'];}$this->inventoryRepository->incrementQuantity($productId,$quantity);return ['success' => true,'message' => 'Inventory updated successfully'];});}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Services\OrderService.php =====
namespace App\Services;class OrderService{public function __construct(protected OrderRepository $orderRepository){}public function getUserOrders(): array{$orders = $this->orderRepository->getUserOrders(Auth::id());if($orders->isEmpty()){return ['success' => false,'message' => 'No orders found'];}return ['success' => true,'data' => $orders];}public function getOrderById(int $orderId): array{$order = $this->orderRepository->getOrderById($orderId,Auth::id());if(!$order){return ['success' => false,'message' => 'Order not found'];}return ['success' => true,'data' => $order];}public function updateStatus(int $orderId,string $status): array{$order = $this->orderRepository->getOrderById($orderId,Auth::id());if(!$order){return ['success' => false,'message' => 'Order not found'];}$allowedTransitions = [
'pending' => ['Processing','Canceled'],'Processing' => ['Completed','Canceled'],'Completed' => [],'Canceled' => [],];if(!in_array($status,$allowedTransitions[$order->status] ?? [])){return [
'success' => false,'message' => "Cannot transition from{$order->status}to{$status}",];}$updated = $this->orderRepository->updateStatus($order,$status);return ['success' => true,'message' => 'Status updated successfully','data' => $updated];}public function checkoutUnsafe(array $data): array{$user = Auth::user();$cart = $user->cart;if($this->isCartEmpty($cart)){return ['success' => false,'message' => 'Cart is empty'];}$wallet = $user->wallet;if($this->isWalletUnvalid($wallet)){return ['success' => false,'message' => 'Wallet not found or inactive'];}$cartItems = $cart->cartItems()->with('product.inventory')->get();$productIds = $cartItems->pluck('product_id')->sort()->values();$inventories = Inventory::whereIn('product_id',$productIds)->orderBy('product_id')->get()->keyBy('product_id');sleep(1);$unavailable = collect();foreach($cartItems as $item){$inventory = $inventories->get($item->product_id);$stock = $inventory ? $inventory->quantity : 0;if($stock < $item->quantity){$unavailable->push([
'product_id' => $item->product_id,'product_name' => $item->product->name,'requested' => $item->quantity,'available' => $stock,]);}}if($unavailable->isNotEmpty()){return [
'success' => false,'message' => 'Some products are out of stock','data' => $unavailable->map(fn($item)=> [
'product_id' => $item->product_id,'product_name' => $item->product->name,'requested' => $item->quantity,'available' => $item->product->inventory?->quantity ?? 0,]),];}$amount = $cartItems->sum(fn($item)=> $item->quantity * $item->product->price);if($wallet->balance < $amount){return [
'success' => false,'message' => 'Insufficient wallet balance','data' => [
'required' => $amount,'available' => $wallet->balance,'shortage' => $amount - $wallet->balance,],];}$order = $this->orderRepository->createOrder($user,$cart,$amount,$data,$cartItems,$inventories);$transaction = $this->makeTransaction($wallet,$amount,$order);$order->update(['payment_status' => 'paid']);return [
'success' => true,'message' => 'Payment completed successfully','data' => [
'order' => $order,'transaction' => $transaction,'wallet_balance' => $wallet->fresh()->balance,],];}public function checkoutSafe(array $data): array{$user = Auth::user();$perUserLock = Cache::lock("checkout:user:{$user->id}");$inventoryLock = Cache::lock('inventory',2);if(!$perUserLock->get()){return ['success' => false,'message' => 'Checkout in progress'];}try{$res = DB::transaction(function()use($data,$perUserLock,$user,$inventoryLock){$cart = $user->cart;if($this->isCartEmpty($cart)){return ['success' => false,'message' => 'Cart is empty'];}$cartItems = $cart->cartItems()->with('product.inventory')->get();$wallet = $user->wallet()->lockForUpdate()->first();if($this->isWalletUnvalid($wallet)){return ['success' => false,'message' => 'Wallet not found or inactive'];}$productIds = $cartItems->pluck('product_id')->sort()->values();while(!$inventoryLock->get()){sleep(1);}$inventories = Inventory::whereIn('product_id',$productIds)->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');$inventoryLock->release();$unavailable = collect();foreach($cartItems as $item){$inventory = $inventories->get($item->product_id);$stock = $inventory ? $inventory->quantity : 0;if($stock < $item->quantity){$unavailable->push([
'product_id' => $item->product_id,'product_name' => $item->product->name,'requested' => $item->quantity,'available' => $stock,]);}}if($unavailable->isNotEmpty()){return [
'success' => false,'message' => 'Some products are out of stock','data' => $unavailable,];}$amount = $cartItems->sum(fn($item)=> $item->quantity * $item->product->price);if($wallet->balance < $amount){return [
'success' => false,'message' => 'Insufficient wallet balance','data' => [
'required' => $amount,'available' => $wallet->balance,'shortage' => $amount - $wallet->balance,],];}$order = $this->orderRepository->createOrder($user,$cart,$amount,$data,$cartItems,$inventories);$perUserLock->release();$transaction = $this->makeTransaction($wallet,$amount,$order);$order->update(['payment_status' => 'paid']);return [
'success' => true,'message' => 'Payment completed successfully','data' => [
'order' => $order,'transaction' => $transaction,'wallet_balance' => $wallet->fresh()->balance,],];});return $res;}finally{$perUserLock->release();$inventoryLock->release();}}function makeTransaction(Wallet $wallet,$amount,Order $order): \Illuminate\Database\Eloquent\Model{$balanceBefore = $wallet->balance;$wallet->decrement('balance',$amount);$balanceAfter = $wallet->balance;$transaction = $wallet->transactions()->create([
'order_id' => $order->id,'amount' => $amount,'balance_before' => $balanceBefore,'balance_after' => $balanceAfter,'type' => 'payment','status' => 'completed',]);return $transaction;}public function isCartEmpty(mixed $cart): bool{return !$cart || !$cart->cartItems()->exists();}function isWalletUnvalid(Wallet $wallet): bool{return !$wallet || !$wallet->is_active;}}

// === [Models] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\Cart.php =====
namespace App\Models;class Cart extends Model{use HasFactory;protected $guarded = [];public function user(){return $this->belongsTo(User::class);}public function cartItems(){return $this->hasMany(CartItem::class);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\CartItem.php =====
namespace App\Models;class CartItem extends Model{use HasFactory;protected $guarded = [];public function cart(){return $this->belongsTo(Cart::class);}public function product(){return $this->belongsTo(Product::class);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\Category.php =====
namespace App\Models;class Category extends Model{use HasFactory;protected $guarded = [];public function products(){return $this->hasMany(Product::class);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\DailySalesReport.php =====
namespace App\Models;class DailySalesReport extends Model{protected $fillable = [
'date','total_orders','total_revenue','pdf_path','processing_mode','export_start_time','export_end_time',];protected $casts = [
'date' => 'date','export_start_time' => 'datetime','export_end_time' => 'datetime','total_revenue' => 'decimal:2',];}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\Inventory.php =====
namespace App\Models;class Inventory extends Model{use HasFactory;protected $guarded = [];public function product(){return $this->belongsTo(Product::class);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\Order.php =====
namespace App\Models;class Order extends Model{use HasFactory;protected $guarded = [];public function user(){return $this->belongsTo(User::class);}public function orderItems(){return $this->hasMany(OrderItem::class);}public function transaction(){return $this->hasOne(Transaction::class);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\OrderItem.php =====
namespace App\Models;class OrderItem extends Model{use HasFactory;protected $guarded = [];public function order(){return $this->belongsTo(Order::class);}public function product(){return $this->belongsTo(Product::class);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\Product.php =====
namespace App\Models;class Product extends Model{use HasFactory;protected $guarded = [];protected $with = ['inventory'];public function category(){return $this->belongsTo(Category::class);}public function inventory(){return $this->hasOne(Inventory::class);}public function getPhotoUrlAttribute($value){return asset($value);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\Transaction.php =====
namespace App\Models;class Transaction extends Model{use HasFactory;protected $guarded = [];public function wallet(): BelongsTo{return $this->belongsTo(Wallet::class);}public function order(): BelongsTo{return $this->belongsTo(Order::class);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\User.php =====
namespace App\Models;class User extends Authenticatable{use HasApiTokens,HasFactory,Notifiable;protected function casts(): array{return [
'email_verified_at' => 'datetime','password' => 'hashed',];}public function cart(){return $this->hasOne(Cart::class);}public function orders(){return $this->hasMany(Order::class);}public function wallet(){return $this->hasOne(Wallet::class);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Models\Wallet.php =====
namespace App\Models;class Wallet extends Model{use HasFactory;protected $guarded = [];public function user(){return $this->belongsTo(User::class);}public function transactions(){return $this->hasMany(Transaction::class);}}

// === [Providers] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Providers\AppServiceProvider.php =====
namespace App\Providers;class AppServiceProvider extends ServiceProvider{public function register(): void{}public function boot(): void{Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);RateLimiter::for('public-api',function(Request $request){return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());});RateLimiter::for('authenticated-api',function(Request $request){return Limit::perMinute(120)->by($request->user()->id);});RateLimiter::for('login',function(Request $request){return Limit::perMinute(5)->by($request->email.$request->ip());});RateLimiter::for('register',function(Request $request){return Limit::perMinute(3)->by($request->ip());});RateLimiter::for('cart',function(Request $request){return Limit::perMinute(60)->by($request->user()->id);});RateLimiter::for('checkout',function(Request $request){return Limit::perMinute(5)->by($request->user()->id);});RateLimiter::for('wallet',function(Request $request){return Limit::perMinute(3)->by($request->user()->id);});RateLimiter::for('inventory-update',function(Request $request){return Limit::perMinute(20)->by($request->user()->id);});RateLimiter::for('admin-actions',function(Request $request){return Limit::perMinute(10)->by($request->user()->id);});}}

// === [Requests] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Requests\CheckoutRequest.php =====
namespace App\Http\Requests;class CheckoutRequest extends FormRequest{public function authorize(): bool{return true;}public function rules(): array{return [
'shipping_address' => 'required|string|min:5|max:500','safe'=>'sometimes',];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Requests\LoginRequest.php =====
namespace App\Http\Requests;class LoginRequest extends FormRequest{public function authorize(): bool{return true;}public function rules(): array{return [
'email' => ['required','email'],'password' => ['required','string','min:6'],];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Requests\RegisterRequest.php =====
namespace App\Http\Requests;class RegisterRequest extends FormRequest{public function authorize(): bool{return true;}public function rules(): array{return [
'name' => ['required','string','max:255'],'email' => ['required','email','unique:users,email'],'password' => ['required','min:6','confirmed'],];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Requests\StoreCategoryRequest.php =====
namespace App\Http\Requests;class StoreCategoryRequest extends FormRequest{public function authorize(): bool{return true;}public function rules(): array{return [
'name' => 'required|string|max:255|unique:categories,name',];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Requests\StoreProductRequest.php =====
namespace App\Http\Requests;class StoreProductRequest extends FormRequest{public function authorize(): bool{return true;}public function rules(): array{return [
'name' => 'required|string|max:255','description' => 'nullable|string','price' => 'required|numeric|min:0','category_id' => 'required|exists:categories,id','image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Requests\TopUpRequest.php =====
namespace App\Http\Requests;class TopUpRequest extends FormRequest{public function authorize(): bool{return true;}public function rules(): array{return [
'amount' => 'required|numeric|min:1',];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Requests\UpdateCategoryRequest.php =====
namespace App\Http\Requests;class UpdateCategoryRequest extends FormRequest{public function authorize(): bool{return true;}public function rules(): array{return [
'name' => 'sometimes|string|max:255|unique:categories,name,'.$this->route('category')->id,];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Requests\UpdateProductRequest.php =====
namespace App\Http\Requests;class UpdateProductRequest extends FormRequest{public function authorize(): bool{return true;}public function rules(): array{return [
'name' => 'sometimes|string|max:255','description' => 'sometimes|string','price' => 'sometimes|numeric|min:0','category_id' => 'sometimes|exists:categories,id','image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',];}}

// === [Helpers] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Helpers\ResponseHelper.php =====
namespace App\Helpers;class ResponseHelper{public static function jsonResponse($data = null,string $message = '',int $statusCode = 200,bool $successful = true,int $pageCount = null,int $userCount = null): JsonResponse{$responseData = [
'successful' => $successful,'message' => $message,'node' => env('NODE_NAME',gethostname()),'data' => $data,'page_count' => $pageCount,'user_count' => $userCount,'status_code' => $statusCode,];if(is_null($data)||(is_array($data)&& empty($data))){unset($responseData['data']);}if(is_null($pageCount)){unset($responseData['page_count']);}if(is_null($userCount)){unset($responseData['user_count']);}return response()->json($responseData,$statusCode);}}

// === [Middleware] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Http\Middleware\EnsureUserIsAdmin.php =====
namespace App\Http\Middleware;class EnsureUserIsAdmin{public function handle(Request $request,Closure $next): Response{if($request->user()->role !== 'Admin'){return response()->json(['message' => 'Forbidden'],403);}return $next($request);}}

// === [Jobs] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Jobs\GenerateInvoicePdfJob.php =====
namespace App\Jobs;class GenerateInvoicePdfJob implements ShouldQueue{use Queueable;public function __construct(public int $orderId){}public function handle(): void{$order = Order::with([
'user','orderItems.product',])->find($this->orderId);if(! $order){return;}$invoiceData = [
'invoice_number' => 'INV-'.str_pad($order->id,5,'0',STR_PAD_LEFT),'purchase_date' => $order->created_at->format('Y-m-d'),'customer_name' => $order->user->name,'shipping_address' => $order->shipping_address,'payment_status' => $order->payment_status,'total_amount' => $order->total_amount,'items' => $order->orderItems->map(function($item){return [
'name' => $item->product->name,'quantity' => $item->quantity,'unit_price' => $item->unit_price,'subtotal' => $item->quantity * $item->unit_price,];})->toArray(),];$pdf = app('dompdf.wrapper')->loadView('pdf.invoice',$invoiceData);Storage::put("public/invoices/invoice-{$order->id}.pdf",$pdf->output());}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Jobs\ProcessDailySalesJob.php =====
namespace App\Jobs;class ProcessDailySalesJob implements ShouldQueue{use Queueable;protected string $date;protected ProcessingMode $mode;public function __construct(?string $date = null,?ProcessingMode $mode = null){$this->date = $date
? Carbon::parse($date)->toDateString(): Carbon::yesterday()->toDateString();$this->mode = $mode ?? ProcessingMode::Batch;}public function handle(DailySalesProcessingService $processingService): void{$exportStartTime = Carbon::now();Log::info('========== Daily Sales Job Started ==========');Log::info("Processing date:{$this->date}");Log::info("Mode:{$this->mode->value}");Log::info("Started at:{$exportStartTime}");$existingReport = DailySalesReport::where('date',$this->date)->first();if($existingReport){Log::warning("Report already exists for{$this->date}");return;}$result = $processingService->process($this->date,$this->mode);$this->generateAndSaveReport($result,$exportStartTime);Log::info('Daily sales report created successfully');Log::info('========== Job Finished ==========');}private function generateAndSaveReport(array $result,Carbon $exportStartTime): void{$pdfData = $this->preparePdfData($result);$pdf = Pdf::loadView('pdf.daily-sales-report',$pdfData);$pdfPath = "public/daily-reports/daily-sales-{$this->date}.pdf";Storage::put($pdfPath,$pdf->output());$exportEndTime = Carbon::now();$reportData = [
'date' => $this->date,'processing_mode' => $this->mode->value,'total_orders' => $pdfData['total_orders'],'total_revenue' => $pdfData['total_revenue'],'pdf_path' => $pdfPath,'export_start_time' => $exportStartTime,'export_end_time' => $exportEndTime,];DailySalesReport::create($reportData);Log::info("Report exported at:{$exportEndTime}");}private function preparePdfData(array $result): array{return match($result['mode']){ProcessingMode::Batch->value => $this->prepareBatchPdfData($result),ProcessingMode::Normal->value => $this->prepareNormalPdfData($result),ProcessingMode::Compare->value => $this->prepareComparePdfData($result),};}private function prepareBatchPdfData(array $result): array{$batchResult = $result['batch_result'];return [
'date' => $this->date,'processing_mode' => ProcessingMode::Batch->value,'total_orders' => $batchResult['total_orders'],'total_revenue' => $batchResult['total_revenue'],'orders' => $batchResult['orders_data'],'performance_metrics' => [
'execution_time' => $batchResult['execution_time'],'peak_memory' => $batchResult['peak_memory'],'memory_used' => $batchResult['memory_used'],'batches_count' => $batchResult['batches_count'],],'batches_metrics' => $batchResult['batches_metrics'],'batch_timeline' => true,'order_stats' => $this->calculateOrderStats($batchResult['orders_data']),];}private function prepareNormalPdfData(array $result): array{$normalResult = $result['normal_result'];return [
'date' => $this->date,'processing_mode' => ProcessingMode::Normal->value,'total_orders' => $normalResult['total_orders'],'total_revenue' => $normalResult['total_revenue'],'orders' => $normalResult['orders_data'],'performance_metrics' => [
'execution_time' => $normalResult['execution_time'],'peak_memory' => $normalResult['peak_memory'],'memory_used' => $normalResult['memory_used'],],'batch_timeline' => false,'order_stats' => $this->calculateOrderStats($normalResult['orders_data']),];}private function prepareComparePdfData(array $result): array{if($result['normal_skipped']){return $this->prepareBatchPdfData(['batch_result' => $result['batch_result']]);}$normalResult = $result['normal_result'];$batchResult = $result['batch_result'];$comparison = $result['comparison'];$batchStats = $result['batch_stats'];$normalStats = [
'orders_processed' => $normalResult['total_orders'],'execution_time' => $normalResult['execution_time'],'peak_memory_real' => $normalResult['memory_stats']['peak_real_mb'] ?? $normalResult['peak_memory'],'peak_memory_allocated' => $normalResult['memory_stats']['peak_alloc_mb'] ?? $normalResult['peak_memory'],'memory_delta' => $normalResult['memory_stats']['delta_total_real_mb'] ?? $normalResult['memory_used'],'start_memory_real' => $normalResult['memory_stats']['start_real_mb'] ?? 0,'end_memory_real' => $normalResult['memory_stats']['end_real_mb'] ?? 0,'orders_loaded' => $normalResult['total_orders'],'status' => $normalResult['skipped'] ? 'Skipped' : 'Completed','failed' => $normalResult['skipped'],];$batchStatsFull = [
'orders_processed' => $batchResult['total_orders'],'execution_time' => $batchResult['execution_time'],'peak_memory_real' => $batchResult['memory_stats']['peak_real_mb'] ?? $batchResult['peak_memory'],'peak_memory_allocated' => $batchResult['memory_stats']['peak_alloc_mb'] ?? $batchResult['peak_memory'],'memory_delta' => $batchResult['memory_stats']['delta_real_mb'] ?? $batchResult['memory_used'],'start_memory_real' => $batchResult['memory_stats']['start_real_mb'] ?? 0,'end_memory_real' => $batchResult['memory_stats']['end_real_mb'] ?? 0,'orders_loaded' => $batchResult['total_orders'],'batch_count' => $batchStats['batch_count'],'batch_size' => $batchStats['batch_size'],'average_batch_memory' => $batchStats['average_batch_memory'],'largest_batch_memory' => $batchStats['largest_batch_memory'],'smallest_batch_memory' => $batchStats['smallest_batch_memory'],'status' => 'Completed','failed' => false,];return [
'date' => $this->date,'processing_mode' => ProcessingMode::Compare->value,'total_orders' => $batchResult['total_orders'],'total_revenue' => $batchResult['total_revenue'],'orders' => $batchResult['orders_data'],'performance_metrics' => [
'execution_time' => $batchResult['execution_time'],'peak_memory' => $batchResult['peak_memory'],'memory_used' => $batchResult['memory_used'],'batches_count' => $batchResult['batches_count'],],'batches_metrics' => $batchResult['batches_metrics'],'batch_timeline' => true,'benchmark_comparison' => [
'normal_execution_time' => $comparison['normal_execution_time'],'batch_execution_time' => $comparison['batch_execution_time'],'speed_improvement_percent' => $comparison['speed_improvement_percent'],'normal_peak_memory' => $comparison['normal_peak_memory'],'batch_peak_memory' => $comparison['batch_peak_memory'],'memory_reduction_percent' => $comparison['memory_reduction_percent'],],'normal_stats' => $normalStats,'batch_stats' => $batchStatsFull,'comparison' => $comparison,'batch_details' => $batchResult['batches_metrics'],'order_stats' => $this->calculateOrderStats($batchResult['orders_data']),];}private function calculateOrderStats(array $ordersData): array{$statuses = array_column($ordersData,'status');$amounts = array_column($ordersData,'total_amount');$completed = count(array_filter($statuses,fn($s)=> $s === 'Completed'));$canceled = count(array_filter($statuses,fn($s)=> $s === 'Canceled'));$pending = count(array_filter($statuses,fn($s)=> $s === 'pending'));$processing = count(array_filter($statuses,fn($s)=> $s === 'Processing'));$total = count($ordersData);return [
'completed_orders' => $completed,'canceled_orders' => $canceled,'pending_orders' => $pending,'processing_orders' => $processing,'total_cost' => round(array_sum($amounts),2),'average_order' => $total > 0 ? round(array_sum($amounts)/ $total,2): 0,];}}

// === [Processors] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Processors\DailySales\ChunkedSalesProcessor.php =====
namespace App\Processors\DailySales;class ChunkedSalesProcessor{private int $totalOrders = 0;private float $totalRevenue = 0;private array $ordersData = [];private array $batchesMetrics = [];private int $batchCounter = 0;private int $completedOrders = 0;private int $canceledOrders = 0;private int $pendingOrders = 0;private int $processingOrders = 0;public function process(string $date): array{$executionStartTime = microtime(true);$mem_start_real = memory_get_usage(false);$mem_start_alloc = memory_get_usage(true);Log::info('========== Chunked Sales Processing Started ==========');Log::info("Processing date:{$date}");Log::info(sprintf('Memory at START — Real: %.4f MB | Allocated: %.4f MB',$mem_start_real / 1024 / 1024,$mem_start_alloc / 1024 / 1024));$dayStart = Carbon::parse($date)->startOfDay();$dayEnd = Carbon::parse($date)->endOfDay();Order::where('created_at','>=',$dayStart)->where('created_at','<',$dayEnd)->with('orderItems.product')->chunkById(1000,function($orders){$this->processBatch($orders);});$mem_end_real = memory_get_usage(false);$mem_end_alloc = memory_get_usage(true);$peak_real = memory_get_peak_usage(false);$peak_alloc = memory_get_peak_usage(true);$delta_real = $mem_end_real - $mem_start_real;$delta_alloc = $mem_end_alloc - $mem_start_alloc;$executionTime = round(microtime(true)- $executionStartTime,4);Log::info('========== Chunked Processing Completed ==========');Log::info("Total Orders:{$this->totalOrders}");Log::info("Total Revenue:{$this->totalRevenue}");Log::info("Execution Time:{$executionTime}s");Log::info('--- Memory Statistics(Comprehensive)---');Log::info(sprintf('START — Real: %.4f MB | Allocated: %.4f MB',$mem_start_real / 1024 / 1024,$mem_start_alloc / 1024 / 1024));Log::info(sprintf('END — Real: %.4f MB | Allocated: %.4f MB',$mem_end_real / 1024 / 1024,$mem_end_alloc / 1024 / 1024));Log::info(sprintf('PEAK — Real: %.4f MB | Allocated: %.4f MB ← الأهم للتقرير',$peak_real / 1024 / 1024,$peak_alloc / 1024 / 1024));Log::info(sprintf('DELTA — Real: %.4f MB | Allocated: %.4f MB',$delta_real / 1024 / 1024,$delta_alloc / 1024 / 1024));Log::info("Batches Processed:{$this->batchCounter}");return [
'total_orders' => $this->totalOrders,'total_revenue' => $this->totalRevenue,'execution_time' => $executionTime,'batches_count' => $this->batchCounter,'batches_metrics' => $this->batchesMetrics,'orders_data' => $this->ordersData,'memory_stats' => [
'start_real_mb' => round($mem_start_real / 1024 / 1024,4),'start_alloc_mb' => round($mem_start_alloc / 1024 / 1024,4),'end_real_mb' => round($mem_end_real / 1024 / 1024,4),'end_alloc_mb' => round($mem_end_alloc / 1024 / 1024,4),'peak_real_mb' => round($peak_real / 1024 / 1024,4),'peak_alloc_mb' => round($peak_alloc / 1024 / 1024,4),'delta_real_mb' => round($delta_real / 1024 / 1024,4),'delta_alloc_mb' => round($delta_alloc / 1024 / 1024,4),],'peak_memory' => round($peak_alloc / 1024 / 1024,4),'memory_used' => round($delta_real / 1024 / 1024,4),];}private function processBatch($orders): void{$this->batchCounter++;$mem_before_real = memory_get_usage(false);$mem_before_alloc = memory_get_usage(true);$batchStartTime = microtime(true);Log::info("Processing Batch 
foreach($orders as $order){$this->totalOrders++;$this->totalRevenue += $order->total_amount;$this->ordersData[] = $this->formatOrder($order);}$batchExecutionTime = round(microtime(true)- $batchStartTime,4);$mem_after_real = memory_get_usage(false);$mem_after_alloc = memory_get_usage(true);$this->batchesMetrics[] = [
'batch_number' => $this->batchCounter,'orders_count' => $orders->count(),'execution_time' => $batchExecutionTime,'memory_before' => round($mem_before_alloc / 1024 / 1024,4),'memory_after' => round($mem_after_alloc / 1024 / 1024,4),'memory_before_real_mb' => round($mem_before_real / 1024 / 1024,4),'memory_after_real_mb' => round($mem_after_real / 1024 / 1024,4),'memory_delta_real_mb' => round(($mem_after_real - $mem_before_real)/ 1024 / 1024,4),];Log::info(sprintf('Batch 
$this->batchCounter,$batchExecutionTime,$mem_before_real / 1024 / 1024,$mem_after_real / 1024 / 1024,($mem_after_real - $mem_before_real)/ 1024 / 1024,$mem_before_alloc / 1024 / 1024,$mem_after_alloc / 1024 / 1024));}private function formatOrder($order): array{$items = [];foreach($order->orderItems as $item){$items[] = [
'product_name' => $item->product->name,'quantity' => $item->quantity,'unit_price' => $item->unit_price,'subtotal' => $item->quantity * $item->unit_price,];}return [
'id' => $order->id,'status' => $order->status,'payment_status' => $order->payment_status,'total_amount' => $order->total_amount,'created_at' => $order->created_at->format('Y-m-d H:i:s'),'items' => $items,];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Processors\DailySales\NormalSalesProcessor.php =====
namespace App\Processors\DailySales;class NormalSalesProcessor{private float $executionStartTime;private int $totalOrders = 0;private float $totalRevenue = 0;private array $ordersData = [];public function process(string $date): array{$this->executionStartTime = microtime(true);$mem_start_real = memory_get_usage(false);$mem_start_alloc = memory_get_usage(true);Log::info('========== Normal Sales Processing Started ==========');Log::info("Processing date:{$date}");Log::info(sprintf('Memory at START — Real: %.4f MB | Allocated: %.4f MB',$mem_start_real / 1024 / 1024,$mem_start_alloc / 1024 / 1024));$dayStart = Carbon::parse($date)->startOfDay();$dayEnd = Carbon::parse($date)->endOfDay();$mem_before_get_real = memory_get_usage(false);$mem_before_get_alloc = memory_get_usage(true);Log::info(sprintf('Memory BEFORE get()— Real: %.4f MB | Allocated: %.4f MB',$mem_before_get_real / 1024 / 1024,$mem_before_get_alloc / 1024 / 1024));$orders = Order::where('created_at','>=',$dayStart)->where('created_at','<',$dayEnd)->with('orderItems.product')->get();$mem_after_get_real = memory_get_usage(false);$mem_after_get_alloc = memory_get_usage(true);Log::info(sprintf('Memory AFTER get()— Real: %.4f MB | Allocated: %.4f MB | Delta-Real: %.4f MB',$mem_after_get_real / 1024 / 1024,$mem_after_get_alloc / 1024 / 1024,($mem_after_get_real - $mem_before_get_real)/ 1024 / 1024));Log::info("Orders loaded into memory:{$orders->count()}");if($orders->count()> 100000){Log::warning('Normal processing skipped: count > 100000');return [
'total_orders' => 0,'total_revenue' => 0,'execution_time' => 0,'peak_memory' => 0,'memory_used' => 0,'skipped' => true,'reason' => 'Orders count exceeds 100000','orders_data' => [],];}$mem_before_loop_real = memory_get_usage(false);$mem_before_loop_alloc = memory_get_usage(true);foreach($orders as $order){$this->totalOrders++;$this->totalRevenue += $order->total_amount;$this->ordersData[] = $this->formatOrder($order);}$mem_end_real = memory_get_usage(false);$mem_end_alloc = memory_get_usage(true);$peak_real = memory_get_peak_usage(false);$peak_alloc = memory_get_peak_usage(true);$delta_total_real = $mem_end_real - $mem_start_real;$delta_get_real = $mem_after_get_real - $mem_before_get_real;$delta_loop_real = $mem_end_real - $mem_before_loop_real;$executionTime = round(microtime(true)- $this->executionStartTime,4);Log::info('========== Normal Processing Completed ==========');Log::info("Total Orders:{$this->totalOrders}");Log::info("Total Revenue:{$this->totalRevenue}");Log::info("Execution Time:{$executionTime}s");Log::info('--- Memory Statistics(Comprehensive)---');Log::info(sprintf('START — Real: %.4f MB | Alloc: %.4f MB',$mem_start_real / 1024 / 1024,$mem_start_alloc / 1024 / 1024));Log::info(sprintf('BEFORE get()— Real: %.4f MB | Alloc: %.4f MB',$mem_before_get_real / 1024 / 1024,$mem_before_get_alloc / 1024 / 1024));Log::info(sprintf('AFTER get()— Real: %.4f MB | Alloc: %.4f MB | Δ(get): %.4f MB ← تكلفة تحميل البيانات',$mem_after_get_real / 1024 / 1024,$mem_after_get_alloc / 1024 / 1024,$delta_get_real / 1024 / 1024));Log::info(sprintf('AFTER loop — Real: %.4f MB | Alloc: %.4f MB | Δ(loop): %.4f MB',$mem_end_real / 1024 / 1024,$mem_end_alloc / 1024 / 1024,$delta_loop_real / 1024 / 1024));Log::info(sprintf('PEAK(real)— %.4f MB ← الرقم الحقيقي للمقارنة',$peak_real / 1024 / 1024));Log::info(sprintf('PEAK(alloc)— %.4f MB ← ما يظهر في OS',$peak_alloc / 1024 / 1024));Log::info(sprintf('DELTA total — Real: %.4f MB | Alloc: %.4f MB',$delta_total_real / 1024 / 1024,($mem_end_alloc - $mem_start_alloc)/ 1024 / 1024));return [
'total_orders' => $this->totalOrders,'total_revenue' => $this->totalRevenue,'execution_time' => $executionTime,'orders_data' => $this->ordersData,'skipped' => false,'memory_stats' => [
'start_real_mb' => round($mem_start_real / 1024 / 1024,4),'start_alloc_mb' => round($mem_start_alloc / 1024 / 1024,4),'before_get_real_mb' => round($mem_before_get_real / 1024 / 1024,4),'before_get_alloc_mb' => round($mem_before_get_alloc / 1024 / 1024,4),'after_get_real_mb' => round($mem_after_get_real / 1024 / 1024,4),'after_get_alloc_mb' => round($mem_after_get_alloc / 1024 / 1024,4),'get_cost_real_mb' => round($delta_get_real / 1024 / 1024,4),'end_real_mb' => round($mem_end_real / 1024 / 1024,4),'end_alloc_mb' => round($mem_end_alloc / 1024 / 1024,4),'peak_real_mb' => round($peak_real / 1024 / 1024,4),'peak_alloc_mb' => round($peak_alloc / 1024 / 1024,4),'delta_total_real_mb' => round($delta_total_real / 1024 / 1024,4),'delta_get_real_mb' => round($delta_get_real / 1024 / 1024,4),'delta_loop_real_mb' => round($delta_loop_real / 1024 / 1024,4),],'peak_memory' => round($peak_alloc / 1024 / 1024,4),'memory_used' => round($delta_total_real / 1024 / 1024,4),];}private function formatOrder($order): array{$items = [];foreach($order->orderItems as $item){$items[] = [
'product_name' => $item->product->name,'quantity' => $item->quantity,'unit_price' => $item->unit_price,'subtotal' => $item->quantity * $item->unit_price,];}return [
'id' => $order->id,'status' => $order->status,'payment_status' => $order->payment_status,'total_amount' => $order->total_amount,'created_at' => $order->created_at->format('Y-m-d H:i:s'),'items' => $items,];}}

// === [Migrations] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\0001_01_01_000000_create_users_table.php =====
return new class extends Migration{public function up(): void{Schema::create('users',function(Blueprint $table){$table->id();$table->string('name');$table->string('email')->unique();$table->timestamp('email_verified_at')->nullable();$table->enum('role',['Admin','User'])->default('User');$table->string('password');$table->rememberToken();$table->timestamps();});Schema::create('password_reset_tokens',function(Blueprint $table){$table->string('email')->primary();$table->string('token');$table->timestamp('created_at')->nullable();});Schema::create('sessions',function(Blueprint $table){$table->string('id')->primary();$table->foreignId('user_id')->nullable()->index();$table->string('ip_address',45)->nullable();$table->text('user_agent')->nullable();$table->longText('payload');$table->integer('last_activity')->index();});}public function down(): void{Schema::dropIfExists('users');Schema::dropIfExists('password_reset_tokens');Schema::dropIfExists('sessions');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\0001_01_01_000001_create_cache_table.php =====
return new class extends Migration{public function up(): void{Schema::create('cache',function(Blueprint $table){$table->string('key')->primary();$table->mediumText('value');$table->bigInteger('expiration')->index();});Schema::create('cache_locks',function(Blueprint $table){$table->string('key')->primary();$table->string('owner');$table->bigInteger('expiration')->index();});}public function down(): void{Schema::dropIfExists('cache');Schema::dropIfExists('cache_locks');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\0001_01_01_000002_create_jobs_table.php =====
return new class extends Migration{public function up(): void{Schema::create('jobs',function(Blueprint $table){$table->id();$table->string('queue')->index();$table->longText('payload');$table->unsignedSmallInteger('attempts');$table->unsignedInteger('reserved_at')->nullable();$table->unsignedInteger('available_at');$table->unsignedInteger('created_at');});Schema::create('job_batches',function(Blueprint $table){$table->string('id')->primary();$table->string('name');$table->integer('total_jobs');$table->integer('pending_jobs');$table->integer('failed_jobs');$table->longText('failed_job_ids');$table->mediumText('options')->nullable();$table->integer('cancelled_at')->nullable();$table->integer('created_at');$table->integer('finished_at')->nullable();});Schema::create('failed_jobs',function(Blueprint $table){$table->id();$table->string('uuid')->unique();$table->text('connection');$table->text('queue');$table->longText('payload');$table->longText('exception');$table->timestamp('failed_at')->useCurrent();});}public function down(): void{Schema::dropIfExists('jobs');Schema::dropIfExists('job_batches');Schema::dropIfExists('failed_jobs');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045209_create_categories_table.php =====
return new class extends Migration{public function up(): void{Schema::create('categories',function(Blueprint $table){$table->id();$table->string('name');$table->timestamps();});}public function down(): void{Schema::dropIfExists('categories');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045210_create_products_table.php =====
return new class extends Migration{public function up(): void{Schema::create('products',function(Blueprint $table){$table->id();$table->string('name');$table->text('description');$table->decimal('price',10,2);$table->string('photo_url')->nullable();$table->foreignIdFor(Category::class)->constrained();$table->timestamps();});}public function down(): void{Schema::dropIfExists('products');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045211_create_inventories_table.php =====
return new class extends Migration{public function up(): void{Schema::create('inventories',function(Blueprint $table){$table->id();$table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();$table->integer('quantity')->default(0);$table->timestamps();});}public function down(): void{Schema::dropIfExists('inventories');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045212_create_carts_table.php =====
return new class extends Migration{public function up(): void{Schema::create('carts',function(Blueprint $table){$table->id();$table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();$table->timestamps();});}public function down(): void{Schema::dropIfExists('carts');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045213_create_cart_items_table.php =====
return new class extends Migration{public function up(): void{Schema::create('cart_items',function(Blueprint $table){$table->id();$table->foreignIdFor(Cart::class)->constrained()->cascadeOnDelete();$table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();$table->integer('quantity');$table->unique(['cart_id','product_id']);$table->timestamps();});}public function down(): void{Schema::dropIfExists('cart_items');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045214_create_orders_table.php =====
return new class extends Migration{public function up(): void{Schema::create('orders',function(Blueprint $table){$table->id();$table->foreignIdFor(User::class)->constrained();$table->enum('status',['Processing','Canceled','Completed','pending']);$table->decimal('total_amount',10,2);$table->text('shipping_address');$table->enum('payment_status',['pending','paid','failed','refunded']);$table->timestamps();});}public function down(): void{Schema::dropIfExists('orders');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045215_create_order_items_table.php =====
return new class extends Migration{public function up(): void{Schema::create('order_items',function(Blueprint $table){$table->id();$table->foreignIdFor(Product::class)->constrained();$table->foreignIdFor(Order::class)->constrained();$table->integer('quantity');$table->decimal('unit_price');$table->timestamps();});}public function down(): void{Schema::dropIfExists('order_items');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045216_create_wallets_table.php =====
return new class extends Migration{public function up(): void{Schema::create('wallets',function(Blueprint $table){$table->id();$table->foreignIdFor(User::class)->constrained();$table->decimal('balance',10,2);$table->boolean('is_active');$table->timestamps();});}public function down(): void{Schema::dropIfExists('wallets');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_03_045217_create_transactions_table.php =====
return new class extends Migration{public function up(): void{Schema::create('transactions',function(Blueprint $table){$table->id();$table->foreignIdFor(Wallet::class)->constrained();$table->foreignIdFor(Order::class)->nullable()->constrained()->nullOnDelete();$table->decimal('amount',10,2);$table->decimal('balance_before',10,2);$table->decimal('balance_after',10,2);$table->enum('type',['deposit','withdraw','payment','refund']);$table->enum('status',['pending','completed','failed']);$table->timestamps();});}public function down(): void{Schema::dropIfExists('transactions');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_04_111058_create_personal_access_tokens_table.php =====
return new class extends Migration{public function up(): void{Schema::create('personal_access_tokens',function(Blueprint $table){$table->id();$table->morphs('tokenable');$table->text('name');$table->string('token',64)->unique();$table->text('abilities')->nullable();$table->timestamp('last_used_at')->nullable();$table->timestamp('expires_at')->nullable()->index();$table->timestamps();});}public function down(): void{Schema::dropIfExists('personal_access_tokens');}};
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\migrations\2026_05_10_121209_create_daily_sales_reports_table.php =====
return new class extends Migration{public function up(): void{Schema::create('daily_sales_reports',function(Blueprint $table){$table->id();$table->date('date')->unique();$table->integer('total_orders')->default(0);$table->decimal('total_revenue',15,2)->default(0);$table->string('pdf_path')->nullable();$table->string('processing_mode')->default('batch');$table->dateTime('export_start_time')->nullable();$table->dateTime('export_end_time')->nullable();$table->timestamps();});}public function down(): void{Schema::dropIfExists('daily_sales_reports');}};

// === [Seeders] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\CartItemSeeder.php =====
namespace Database\Seeders;class CartItemSeeder extends Seeder{public function run(): void{$rows = [];$carts = Cart::pluck('id');$products = Product::pluck('id');foreach($carts as $cartId){$randomProducts = $products->random(rand(1,5));foreach($randomProducts as $productId){$rows[] = [
'cart_id' => $cartId,'product_id' => $productId,'quantity' => rand(1,5),'created_at' => now(),'updated_at' => now(),];}}DB::table('cart_items')->insert($rows);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\CartSeeder.php =====
namespace Database\Seeders;class CartSeeder extends Seeder{public function run(): void{Cart::factory(20)->create();}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\CategorySeeder.php =====
namespace Database\Seeders;class CategorySeeder extends Seeder{public function run(): void{Category::factory(10)->create();}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\DatabaseSeeder.php =====
namespace Database\Seeders;class DatabaseSeeder extends Seeder{use WithoutModelEvents;public function run(): void{User::updateOrCreate(['email' => 'test@test.com'],[
'name' => 'Test','password' => bcrypt('password')]);DB::disableQueryLog();$this->call([
UserSeeder::class,CategorySeeder::class,ProductSeeder::class,InventorySeeder::class,WalletSeeder::class,CartSeeder::class,CartItemSeeder::class,OrderSeeder::class,OrderItemSeeder::class,TransactionSeeder::class,]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\InventorySeeder.php =====
namespace Database\Seeders;class InventorySeeder extends Seeder{public function run(): void{Inventory::factory(10)->create();}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\OrderItemSeeder.php =====
namespace Database\Seeders;class OrderItemSeeder extends Seeder{public function run(): void{$orders = Order::pluck('id');$products = Product::all();$rows = [];foreach($orders as $orderId){$itemsCount = rand(1,3);$randomProducts = $products->random($itemsCount);foreach($randomProducts as $product){$quantity = rand(1,5);$rows[] = [
'order_id' => $orderId,'product_id' => $product->id,'quantity' => $quantity,'unit_price' => $product->price,'created_at' => now(),'updated_at' => now(),];if(count($rows)>= 5000){DB::table('order_items')->insert($rows);$rows = [];}}}if(!empty($rows)){DB::table('order_items')->insert($rows);}}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\OrderSeeder.php =====
namespace Database\Seeders;class OrderSeeder extends Seeder{public function run(): void{$rows = [];function getRandomStatus(){$rand = rand(1,100);if($rand <= 50){return 'Completed';}elseif($rand <= 75){return 'Processing';}elseif($rand <= 90){return 'Pending';}else{return 'Canceled';}}for($i = 0;$i < 50000;$i++){$rows[] = [
'user_id' => rand(1,1000),'status' => getRandomStatus(),'total_amount' => rand(50,500),'shipping_address' => 'Damascus','created_at' => now(),'updated_at' => now(),];if(count($rows)>= 5000){DB::table('orders')->insert($rows);$rows = [];}}DB::table('orders')->insert($rows);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\ProductSeeder.php =====
namespace Database\Seeders;class ProductSeeder extends Seeder{public function run(): void{Product::factory(200)->create();}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\RaceAddToCartSeeder.php =====
namespace Database\Seeders;class RaceAddToCartSeeder extends Seeder{const PRODUCT_ID = 201;const USER_ID = 20;const WALLET_ID = 20;const CART_ID = 20;public function run(): void{DB::table('categories')->insertOrIgnore([
['id' => 1,'name' => 'Electronics','created_at' => now(),'updated_at' => now()],]);DB::table('products')->insertOrIgnore([[
'id' => self::PRODUCT_ID,'name' => 'Race Condition Gadget','description' => 'Used to demonstrate the add-to-cart duplicate race condition.','price' => 49.99,'photo_url' => null,'category_id' => 1,'created_at' => now(),'updated_at' => now(),]]);DB::table('inventories')->insertOrIgnore([[
'product_id' => self::PRODUCT_ID,'quantity' => 50,'created_at' => now(),'updated_at' => now(),]]);DB::table('users')->insertOrIgnore([[
'id' => self::USER_ID,'name' => 'Cart Race Tester','email' => 'cart@example.com','email_verified_at' => now(),'role' => 'User','password' => Hash::make('password'),'remember_token' => null,'created_at' => now(),'updated_at' => now(),]]);DB::table('wallets')->insertOrIgnore([[
'id' => self::WALLET_ID,'user_id' => self::USER_ID,'balance' => 500.00,'is_active' => true,'created_at' => now(),'updated_at' => now(),]]);DB::table('transactions')->insert([[
'wallet_id' => self::WALLET_ID,'order_id' => null,'amount' => 500.00,'balance_before' => 0.00,'balance_after' => 500.00,'type' => 'deposit','status' => 'completed','created_at' => now(),'updated_at' => now(),]]);DB::table('carts')->insertOrIgnore([[
'id' => self::CART_ID,'user_id' => self::USER_ID,'created_at' => now(),'updated_at' => now(),]]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\RaceCartUpdateSeeder.php =====
namespace Database\Seeders;class RaceCartUpdateSeeder extends Seeder{public function run(): void{DB::table('categories')->insertOrIgnore([
['id' => 1,'name' => 'Electronics','created_at' => now(),'updated_at' => now()],]);DB::table('products')->insertOrIgnore([
['id' => 1,'name' => 'Wireless Headphones','description' => 'Over-ear BT headphones.','price' => 99.99,'photo_url' => null,'category_id' => 1,'created_at' => now(),'updated_at' => now()],]);DB::table('inventories')->insertOrIgnore([
['product_id' => 1,'quantity' => 50,'created_at' => now(),'updated_at' => now()],]);DB::table('users')->insertOrIgnore([[
'id' => 4,'name' => 'Double Checkout Tester','email' => 'double@example.com','email_verified_at' => now(),'role' => 'User','password' => Hash::make('password'),'remember_token' => null,'created_at' => now(),'updated_at' => now(),]]);DB::table('wallets')->insertOrIgnore([[
'id' => 4,'user_id' => 4,'balance' => 500.00,'is_active' => true,'created_at' => now(),'updated_at' => now(),]]);DB::table('carts')->insertOrIgnore([[
'id' => 4,'user_id' => 4,'created_at' => now(),'updated_at' => now(),]]);DB::table('cart_items')->insertOrIgnore([
['cart_id' => 4,'product_id' => 1,'quantity' => 5,'created_at' => now(),'updated_at' => now()],]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\RaceDoubleCheckoutSeeder.php =====
namespace Database\Seeders;class RaceDoubleCheckoutSeeder extends Seeder{public function run(): void{DB::table('categories')->insertOrIgnore([
['id' => 1,'name' => 'Electronics','created_at' => now(),'updated_at' => now()],]);DB::table('products')->insertOrIgnore([
['id' => 1,'name' => 'Wireless Headphones','description' => 'Over-ear BT headphones.','price' => 99.99,'photo_url' => null,'category_id' => 1,'created_at' => now(),'updated_at' => now()],['id' => 2,'name' => 'USB-C Charger 65W','description' => 'GaN fast charger.','price' => 34.99,'photo_url' => null,'category_id' => 1,'created_at' => now(),'updated_at' => now()],['id' => 3,'name' => 'Cotton T-Shirt','description' => 'Organic cotton tee.','price' => 19.99,'photo_url' => null,'category_id' => 1,'created_at' => now(),'updated_at' => now()],]);DB::table('inventories')->insertOrIgnore([
['product_id' => 1,'quantity' => 100,'created_at' => now(),'updated_at' => now()],['product_id' => 2,'quantity' => 100,'created_at' => now(),'updated_at' => now()],['product_id' => 3,'quantity' => 100,'created_at' => now(),'updated_at' => now()],]);DB::table('users')->insertOrIgnore([[
'id' => 4,'name' => 'Double Checkout Tester','email' => 'double@example.com','email_verified_at' => now(),'role' => 'User','password' => Hash::make('password'),'remember_token' => null,'created_at' => now(),'updated_at' => now(),]]);DB::table('wallets')->insertOrIgnore([[
'id' => 4,'user_id' => 4,'balance' => 999.00,'is_active' => true,'created_at' => now(),'updated_at' => now(),]]);DB::table('transactions')->insert([[
'wallet_id' => 4,'order_id' => null,'amount' => 999.00,'balance_before' => 0.00,'balance_after' => 999.00,'type' => 'deposit','status' => 'completed','created_at' => now(),'updated_at' => now(),]]);DB::table('carts')->insertOrIgnore([[
'id' => 4,'user_id' => 4,'created_at' => now(),'updated_at' => now(),]]);DB::table('cart_items')->insertOrIgnore([
['cart_id' => 4,'product_id' => 1,'quantity' => 1,'created_at' => now(),'updated_at' => now()],['cart_id' => 4,'product_id' => 2,'quantity' => 1,'created_at' => now(),'updated_at' => now()],['cart_id' => 4,'product_id' => 3,'quantity' => 1,'created_at' => now(),'updated_at' => now()],]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\RaceInventoryAdminCustomerSeeder.php =====
namespace Database\Seeders;class RaceInventoryAdminCustomerSeeder extends Seeder{const PRODUCT_ID = 301;const BUYER_ID = 30;const ADMIN_ID = 31;const BUYER_WALLET = 30;const BUYER_CART = 30;const INITIAL_QTY = 40;public function run(): void{DB::table('categories')->insertOrIgnore([
['id' => 1,'name' => 'Electronics','created_at' => now(),'updated_at' => now()],]);DB::table('products')->insertOrIgnore([[
'id' => self::PRODUCT_ID,'name' => 'Admin Race Product','description' => 'Used to demonstrate admin inventory update vs checkout race.','price' => 29.99,'photo_url' => null,'category_id' => 1,'created_at' => now(),'updated_at' => now(),]]);DB::table('inventories')->insertOrIgnore([[
'product_id' => self::PRODUCT_ID,'quantity' => self::INITIAL_QTY,'created_at' => now(),'updated_at' => now(),]]);DB::table('users')->insertOrIgnore([
[
'id' => self::BUYER_ID,'name' => 'Race Buyer','email' => 'buyer@example.com','email_verified_at' => now(),'role' => 'User','password' => Hash::make('password'),'remember_token' => null,'created_at' => now(),'updated_at' => now(),],[
'id' => self::ADMIN_ID,'name' => 'Inventory Admin','email' => 'admin@example.com','email_verified_at' => now(),'role' => 'Admin','password' => Hash::make('password'),'remember_token' => null,'created_at' => now(),'updated_at' => now(),],]);DB::table('wallets')->insertOrIgnore([[
'id' => self::BUYER_WALLET,'user_id' => self::BUYER_ID,'balance' => 500.00,'is_active' => true,'created_at' => now(),'updated_at' => now(),]]);DB::table('transactions')->insert([[
'wallet_id' => self::BUYER_WALLET,'order_id' => null,'amount' => 500.00,'balance_before' => 0.00,'balance_after' => 500.00,'type' => 'deposit','status' => 'completed','created_at' => now(),'updated_at' => now(),]]);DB::table('carts')->insertOrIgnore([[
'id' => self::BUYER_CART,'user_id' => self::BUYER_ID,'created_at' => now(),'updated_at' => now(),]]);DB::table('cart_items')->insertOrIgnore([[
'cart_id' => self::BUYER_CART,'product_id' => self::PRODUCT_ID,'quantity' => 10,'created_at' => now(),'updated_at' => now(),]]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\RaceSameProductSeeder.php =====
namespace Database\Seeders;class RaceSameProductSeeder extends Seeder{public function run(): void{DB::table('categories')->insertOrIgnore([
['id' => 1,'name' => 'Electronics','created_at' => now(),'updated_at' => now()],]);DB::table('products')->insertOrIgnore([[
'id' => 101,'name' => 'Limited Edition Sneaker(Last Pair)','description' => 'Only 1 unit in stock. Used to demonstrate overselling.','price' => 199.99,'photo_url' => null,'category_id' => 1,'created_at' => now(),'updated_at' => now(),]]);DB::table('inventories')->insertOrIgnore([[
'product_id' => 101,'quantity' => 1,'created_at' => now(),'updated_at' => now(),]]);DB::table('users')->insertOrIgnore([
[
'id' => 5,'name' => 'Buyer One','email' => 'buyer1@example.com','email_verified_at' => now(),'role' => 'User','password' => Hash::make('password'),'remember_token' => null,'created_at' => now(),'updated_at' => now(),],[
'id' => 6,'name' => 'Buyer Two','email' => 'buyer2@example.com','email_verified_at' => now(),'role' => 'User','password' => Hash::make('password'),'remember_token' => null,'created_at' => now(),'updated_at' => now(),],]);DB::table('wallets')->insertOrIgnore([
['id' => 5,'user_id' => 5,'balance' => 500.00,'is_active' => true,'created_at' => now(),'updated_at' => now()],['id' => 6,'user_id' => 6,'balance' => 500.00,'is_active' => true,'created_at' => now(),'updated_at' => now()],]);DB::table('transactions')->insert([
['wallet_id' => 5,'order_id' => null,'amount' => 500.00,'balance_before' => 0.00,'balance_after' => 500.00,'type' => 'deposit','status' => 'completed','created_at' => now(),'updated_at' => now()],['wallet_id' => 6,'order_id' => null,'amount' => 500.00,'balance_before' => 0.00,'balance_after' => 500.00,'type' => 'deposit','status' => 'completed','created_at' => now(),'updated_at' => now()],]);DB::table('carts')->insertOrIgnore([
['id' => 5,'user_id' => 5,'created_at' => now(),'updated_at' => now()],['id' => 6,'user_id' => 6,'created_at' => now(),'updated_at' => now()],]);DB::table('cart_items')->insertOrIgnore([
['cart_id' => 5,'product_id' => 101,'quantity' => 1,'created_at' => now(),'updated_at' => now()],['cart_id' => 6,'product_id' => 101,'quantity' => 1,'created_at' => now(),'updated_at' => now()],]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\TransactionSeeder.php =====
namespace Database\Seeders;class TransactionSeeder extends Seeder{public function run(): void{Transaction::factory(500)->create();}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\UserSeeder.php =====
namespace Database\Seeders;class UserSeeder extends Seeder{public function run(): void{User::factory(998)->create();User::factory()->create([
'name' => 'Admin User','email' => 'admin@example.com','role' => 'Admin',]);User::factory()->create([
'name' => 'Normal User','email' => 'user@example.com','role' => 'User',]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\seeders\WalletSeeder.php =====
namespace Database\Seeders;class WalletSeeder extends Seeder{public function run(): void{User::all()->each(function($user){Wallet::factory()->create(['user_id' => $user->id]);});}}

// === [Factories] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\CartFactory.php =====
namespace Database\Factories;class CartFactory extends Factory{public function definition(): array{return [
'user_id' => User::factory(),];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\CartItemFactory.php =====
namespace Database\Factories;class CartItemFactory extends Factory{public function definition(): array{return [
'product_id' => Product::inRandomOrder()->first()->id ?? Product::factory(),'cart_id' => Cart::inRandomOrder()->first()->id ?? Cart::factory(),'quantity' => fake()->numberBetween(1,5),];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\CategoryFactory.php =====
namespace Database\Factories;class CategoryFactory extends Factory{public function definition(): array{return [
'name' => fake()->words(2,true),];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\InventoryFactory.php =====
namespace Database\Factories;class InventoryFactory extends Factory{public function definition(): array{return [
'product_id' => Product::factory(),'quantity' => fake()->numberBetween(0,100),];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\OrderFactory.php =====
namespace Database\Factories;class OrderFactory extends Factory{public function definition(): array{$date = Carbon::now()->subDays(rand(0,1));return [
'user_id' => User::factory(),'status' => 'Completed','payment_status' => 'paid','total_amount' => fake()->randomFloat(2,50,1000),'created_at' => $date->copy()->setTime(rand(0,23),rand(0,59),rand(0,59)),'shipping_address' => $this->faker->address(),'updated_at' => now(),];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\OrderItemFactory.php =====
namespace Database\Factories;class OrderItemFactory extends Factory{public function definition(): array{return [
'product_id' => Product::inRandomOrder()->first()->id ?? Product::factory(),'order_id' => Order::inRandomOrder()->first()->id ?? Order::factory(),'quantity' => fake()->numberBetween(1,5),'unit_price' => fake()->randomFloat(2,10,500),];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\ProductFactory.php =====
namespace Database\Factories;class ProductFactory extends Factory{public function definition(): array{return [
'name' => fake()->words(3,true),'description' => fake()->paragraph(),'price' => fake()->randomFloat(2,10,1000),'photo_url' => fake()->imageUrl(),'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\TransactionFactory.php =====
namespace Database\Factories;class TransactionFactory extends Factory{public function definition(): array{$amount = fake()->randomFloat(2,10,1000);$balanceBefore = fake()->randomFloat(2,1000,5000);return [
'wallet_id' => Wallet::inRandomOrder()->first()->id ?? Wallet::factory(),'amount' => $amount,'balance_before' => $balanceBefore,'balance_after' => $balanceBefore + $amount,'type' => fake()->randomElement(['deposit','withdraw','payment','refund']),'status' => fake()->randomElement(['pending','completed','failed']),'order_id' => null,];}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\UserFactory.php =====
namespace Database\Factories;class UserFactory extends Factory{protected static ?string $password;public function definition(): array{return [
'name' => fake()->name(),'email' => fake()->unique()->safeEmail(),'email_verified_at' => now(),'role' => fake()->randomElement(['admin','user']),'password' => static::$password ??= Hash::make('password'),'remember_token' => Str::random(10),];}public function unverified(): static{return $this->state(fn(array $attributes)=> [
'email_verified_at' => null,]);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\database\factories\WalletFactory.php =====
namespace Database\Factories;class WalletFactory extends Factory{public function definition(): array{return [
'user_id' => $this->faker->randomElement(User::pluck('id')->toArray())?? User::factory(),'balance' => fake()->randomFloat(2,0,10000),'is_active' => true,];}}

// === [Bootstrap] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\bootstrap\app.php =====
return Application::configure(basePath: dirname(__DIR__))->withRouting(web: __DIR__.'/../routes/web.php',api: __DIR__.'/../routes/api.php',commands: __DIR__.'/../routes/console.php',health: '/up',)->withMiddleware(function(Middleware $middleware): void{$middleware->alias([
'role' => EnsureUserIsAdmin::class,]);})->withExceptions(function(Exceptions $exceptions): void{$exceptions->render(function(AuthenticationException $e,Request $request){if($request->is('api/*')){return ResponseHelper::jsonResponse(null,'Unauthenticated.',401,false);}});$exceptions->render(function(NotFoundHttpException $e,Request $request){if($request->is('api/*')){return ResponseHelper::jsonResponse(null,'Resource not found.',404,false);}});})->create();
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\bootstrap\cache\packages.php =====
return array('barryvdh/laravel-dompdf' => 
array('aliases' => 
array('PDF' => 'Barryvdh\\DomPDF\\Facade\\Pdf','Pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',),'providers' => 
array(0 => 'Barryvdh\\DomPDF\\ServiceProvider',),),'laravel/boost' => 
array('providers' => 
array(0 => 'Laravel\\Boost\\BoostServiceProvider',),),'laravel/mcp' => 
array('aliases' => 
array('Mcp' => 'Laravel\\Mcp\\Server\\Facades\\Mcp',),'providers' => 
array(0 => 'Laravel\\Mcp\\Server\\McpServiceProvider',),),'laravel/pail' => 
array('providers' => 
array(0 => 'Laravel\\Pail\\PailServiceProvider',),),'laravel/pao' => 
array('providers' => 
array(0 => 'Laravel\\Pao\\Laravel\\ServiceProvider',),),'laravel/roster' => 
array('providers' => 
array(0 => 'Laravel\\Roster\\RosterServiceProvider',),),'laravel/sanctum' => 
array('providers' => 
array(0 => 'Laravel\\Sanctum\\SanctumServiceProvider',),),'laravel/tinker' => 
array('providers' => 
array(0 => 'Laravel\\Tinker\\TinkerServiceProvider',),),'nesbot/carbon' => 
array('providers' => 
array(0 => 'Carbon\\Laravel\\ServiceProvider',),),'nunomaduro/collision' => 
array('providers' => 
array(0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',),),'nunomaduro/termwind' => 
array('providers' => 
array(0 => 'Termwind\\Laravel\\TermwindServiceProvider',),),);
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\bootstrap\providers.php =====
return [
AppServiceProvider::class,];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\bootstrap\cache\services.php =====
return array('providers' => 
array(0 => 'Illuminate\\Auth\\AuthServiceProvider',1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',2 => 'Illuminate\\Bus\\BusServiceProvider',3 => 'Illuminate\\Cache\\CacheServiceProvider',4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',6 => 'Illuminate\\Cookie\\CookieServiceProvider',7 => 'Illuminate\\Database\\DatabaseServiceProvider',8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',11 => 'Illuminate\\Hashing\\HashServiceProvider',12 => 'Illuminate\\Mail\\MailServiceProvider',13 => 'Illuminate\\Notifications\\NotificationServiceProvider',14 => 'Illuminate\\Pagination\\PaginationServiceProvider',15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',17 => 'Illuminate\\Queue\\QueueServiceProvider',18 => 'Illuminate\\Redis\\RedisServiceProvider',19 => 'Illuminate\\Session\\SessionServiceProvider',20 => 'Illuminate\\Translation\\TranslationServiceProvider',21 => 'Illuminate\\Validation\\ValidationServiceProvider',22 => 'Illuminate\\View\\ViewServiceProvider',23 => 'Barryvdh\\DomPDF\\ServiceProvider',24 => 'Laravel\\Boost\\BoostServiceProvider',25 => 'Laravel\\Mcp\\Server\\McpServiceProvider',26 => 'Laravel\\Pail\\PailServiceProvider',27 => 'Laravel\\Pao\\Laravel\\ServiceProvider',28 => 'Laravel\\Roster\\RosterServiceProvider',29 => 'Laravel\\Sanctum\\SanctumServiceProvider',30 => 'Laravel\\Tinker\\TinkerServiceProvider',31 => 'Carbon\\Laravel\\ServiceProvider',32 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',33 => 'Termwind\\Laravel\\TermwindServiceProvider',34 => 'App\\Providers\\AppServiceProvider',),'eager' => 
array(0 => 'Illuminate\\Auth\\AuthServiceProvider',1 => 'Illuminate\\Cookie\\CookieServiceProvider',2 => 'Illuminate\\Database\\DatabaseServiceProvider',3 => 'Illuminate\\Encryption\\EncryptionServiceProvider',4 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',5 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',6 => 'Illuminate\\Notifications\\NotificationServiceProvider',7 => 'Illuminate\\Pagination\\PaginationServiceProvider',8 => 'Illuminate\\Session\\SessionServiceProvider',9 => 'Illuminate\\View\\ViewServiceProvider',10 => 'Barryvdh\\DomPDF\\ServiceProvider',11 => 'Laravel\\Boost\\BoostServiceProvider',12 => 'Laravel\\Mcp\\Server\\McpServiceProvider',13 => 'Laravel\\Pail\\PailServiceProvider',14 => 'Laravel\\Pao\\Laravel\\ServiceProvider',15 => 'Laravel\\Roster\\RosterServiceProvider',16 => 'Laravel\\Sanctum\\SanctumServiceProvider',17 => 'Carbon\\Laravel\\ServiceProvider',18 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',19 => 'Termwind\\Laravel\\TermwindServiceProvider',20 => 'App\\Providers\\AppServiceProvider',),'deferred' => 
array('Illuminate\\Broadcasting\\BroadcastManager' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider','Illuminate\\Contracts\\Broadcasting\\Factory' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider','Illuminate\\Contracts\\Broadcasting\\Broadcaster' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider','Illuminate\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider','Illuminate\\Contracts\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider','Illuminate\\Contracts\\Bus\\QueueingDispatcher' => 'Illuminate\\Bus\\BusServiceProvider','Illuminate\\Bus\\BatchRepository' => 'Illuminate\\Bus\\BusServiceProvider','Illuminate\\Bus\\DatabaseBatchRepository' => 'Illuminate\\Bus\\BusServiceProvider','cache' => 'Illuminate\\Cache\\CacheServiceProvider','cache.store' => 'Illuminate\\Cache\\CacheServiceProvider','cache.psr6' => 'Illuminate\\Cache\\CacheServiceProvider','memcached.connector' => 'Illuminate\\Cache\\CacheServiceProvider','Illuminate\\Cache\\RateLimiter' => 'Illuminate\\Cache\\CacheServiceProvider','Illuminate\\Foundation\\Console\\AboutCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Cache\\Console\\ClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Cache\\Console\\ForgetCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ClearCompiledCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Auth\\Console\\ClearResetsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ConfigCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ConfigClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ConfigShowCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\DbCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\MonitorCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\PruneCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\ShowCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\TableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\WipeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\DownCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EnvironmentCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EnvironmentDecryptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EnvironmentEncryptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EventCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EventClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EventListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Concurrency\\Console\\InvokeSerializedClosureCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\KeyGenerateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\OptimizeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\OptimizeClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\PackageDiscoverCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Cache\\Console\\PruneStaleTagsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\ClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\ListFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\FlushFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\ForgetFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\ListenCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\MonitorCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\PauseCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\PruneBatchesCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\PruneFailedJobsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\RestartCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\ResumeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\RetryCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\RetryBatchCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\WorkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ReloadCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\RouteCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\RouteClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\RouteListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\DumpCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Seeds\\SeedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\ScheduleFinishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\ScheduleListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\ScheduleRunCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\ScheduleClearCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\ScheduleTestCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\ScheduleWorkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\ScheduleInterruptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\SchedulePauseCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Console\\Scheduling\\ScheduleResumeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\ShowModelCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\StorageLinkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\StorageUnlinkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\UpCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ViewCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ViewClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ApiInstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\BroadcastingInstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Cache\\Console\\CacheTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\CastMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ChannelListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ChannelMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ClassMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ComponentMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ConfigMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ConfigPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ConsoleMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Routing\\Console\\ControllerMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\DocsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EnumMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EventGenerateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\EventMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ExceptionMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Factories\\FactoryMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\InterfaceMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\JobMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\JobMiddlewareMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\LangPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ListenerMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\MailMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Routing\\Console\\MiddlewareMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ModelMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\NotificationMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Notifications\\Console\\NotificationTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ObserverMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\PolicyMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ProviderMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\FailedTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\TableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Queue\\Console\\BatchesTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\RequestMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ResourceMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\RuleMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ScopeMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Seeds\\SeederMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Session\\Console\\SessionTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ServeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\StubPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\TestMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\TraitMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\VendorPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Foundation\\Console\\ViewMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','migrator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','migration.repository' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','migration.creator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Migrations\\Migrator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Migrations\\MigrateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Migrations\\FreshCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Migrations\\InstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Migrations\\RefreshCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Migrations\\ResetCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Migrations\\RollbackCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Migrations\\StatusCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Database\\Console\\Migrations\\MigrateMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','composer' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider','Illuminate\\Concurrency\\ConcurrencyManager' => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider','hash' => 'Illuminate\\Hashing\\HashServiceProvider','hash.driver' => 'Illuminate\\Hashing\\HashServiceProvider','mail.manager' => 'Illuminate\\Mail\\MailServiceProvider','mailer' => 'Illuminate\\Mail\\MailServiceProvider','Illuminate\\Mail\\Markdown' => 'Illuminate\\Mail\\MailServiceProvider','auth.password' => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider','auth.password.broker' => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider','Illuminate\\Contracts\\Pipeline\\Hub' => 'Illuminate\\Pipeline\\PipelineServiceProvider','pipeline' => 'Illuminate\\Pipeline\\PipelineServiceProvider','queue' => 'Illuminate\\Queue\\QueueServiceProvider','queue.connection' => 'Illuminate\\Queue\\QueueServiceProvider','queue.failer' => 'Illuminate\\Queue\\QueueServiceProvider','queue.listener' => 'Illuminate\\Queue\\QueueServiceProvider','queue.routes' => 'Illuminate\\Queue\\QueueServiceProvider','queue.worker' => 'Illuminate\\Queue\\QueueServiceProvider','redis' => 'Illuminate\\Redis\\RedisServiceProvider','redis.connection' => 'Illuminate\\Redis\\RedisServiceProvider','translator' => 'Illuminate\\Translation\\TranslationServiceProvider','translation.loader' => 'Illuminate\\Translation\\TranslationServiceProvider','validator' => 'Illuminate\\Validation\\ValidationServiceProvider','validation.presence' => 'Illuminate\\Validation\\ValidationServiceProvider','Illuminate\\Contracts\\Validation\\UncompromisedVerifier' => 'Illuminate\\Validation\\ValidationServiceProvider','command.tinker' => 'Laravel\\Tinker\\TinkerServiceProvider',),'when' => 
array('Illuminate\\Broadcasting\\BroadcastServiceProvider' => 
array(),'Illuminate\\Bus\\BusServiceProvider' => 
array(),'Illuminate\\Cache\\CacheServiceProvider' => 
array(),'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider' => 
array(),'Illuminate\\Concurrency\\ConcurrencyServiceProvider' => 
array(),'Illuminate\\Hashing\\HashServiceProvider' => 
array(),'Illuminate\\Mail\\MailServiceProvider' => 
array(),'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider' => 
array(),'Illuminate\\Pipeline\\PipelineServiceProvider' => 
array(),'Illuminate\\Queue\\QueueServiceProvider' => 
array(),'Illuminate\\Redis\\RedisServiceProvider' => 
array(),'Illuminate\\Translation\\TranslationServiceProvider' => 
array(),'Illuminate\\Validation\\ValidationServiceProvider' => 
array(),'Laravel\\Tinker\\TinkerServiceProvider' => 
array(),),);

// === [Config] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\app.php =====
return [
'name' => env('APP_NAME','Laravel'),'env' => env('APP_ENV','production'),'debug' =>(bool)env('APP_DEBUG',false),'url' => env('APP_URL','http:
'timezone' => 'UTC','locale' => env('APP_LOCALE','en'),'fallback_locale' => env('APP_FALLBACK_LOCALE','en'),'faker_locale' => env('APP_FAKER_LOCALE','en_US'),'cipher' => 'AES-256-CBC','key' => env('APP_KEY'),'previous_keys' => [
...array_filter(explode(',',(string)env('APP_PREVIOUS_KEYS',''))),],'maintenance' => [
'driver' => env('APP_MAINTENANCE_DRIVER','file'),'store' => env('APP_MAINTENANCE_STORE','database'),],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\auth.php =====
return [
'defaults' => [
'guard' => env('AUTH_GUARD','web'),'passwords' => env('AUTH_PASSWORD_BROKER','users'),],'guards' => [
'web' => [
'driver' => 'session','provider' => 'users',],],'providers' => [
'users' => [
'driver' => 'eloquent','model' => env('AUTH_MODEL',User::class),],],'passwords' => [
'users' => [
'provider' => 'users','table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE','password_reset_tokens'),'expire' => 60,'throttle' => 60,],],'password_timeout' => env('AUTH_PASSWORD_TIMEOUT',10800),];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\cache.php =====
return [
'default' => env('CACHE_STORE','database'),'stores' => [
'array' => [
'driver' => 'array','serialize' => false,],'database' => [
'driver' => 'database','connection' => env('DB_CACHE_CONNECTION'),'table' => env('DB_CACHE_TABLE','cache'),'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),'lock_table' => env('DB_CACHE_LOCK_TABLE'),],'file' => [
'driver' => 'file','path' => storage_path('framework/cache/data'),'lock_path' => storage_path('framework/cache/data'),],'memcached' => [
'driver' => 'memcached','persistent_id' => env('MEMCACHED_PERSISTENT_ID'),'sasl' => [
env('MEMCACHED_USERNAME'),env('MEMCACHED_PASSWORD'),],'options' => [
],'servers' => [
[
'host' => env('MEMCACHED_HOST','127.0.0.1'),'port' => env('MEMCACHED_PORT',11211),'weight' => 100,],],],'redis' => [
'driver' => 'redis','connection' => env('REDIS_CACHE_CONNECTION','cache'),'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION','default'),],'dynamodb' => [
'driver' => 'dynamodb','key' => env('AWS_ACCESS_KEY_ID'),'secret' => env('AWS_SECRET_ACCESS_KEY'),'region' => env('AWS_DEFAULT_REGION','us-east-1'),'table' => env('DYNAMODB_CACHE_TABLE','cache'),'endpoint' => env('DYNAMODB_ENDPOINT'),],'octane' => [
'driver' => 'octane',],'failover' => [
'driver' => 'failover','stores' => [
'database','array',],],],'prefix' => env('CACHE_PREFIX',Str::slug((string)env('APP_NAME','laravel')).'-cache-'),'serializable_classes' => false,];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\database.php =====
return [
'default' => env('DB_CONNECTION','sqlite'),'connections' => [
'sqlite' => [
'driver' => 'sqlite','url' => env('DB_URL'),'database' => env('DB_DATABASE',database_path('database.sqlite')),'prefix' => '','foreign_key_constraints' => env('DB_FOREIGN_KEYS',true),'busy_timeout' => null,'journal_mode' => null,'synchronous' => null,'transaction_mode' => 'DEFERRED',],'mysql' => [
'driver' => 'mysql','url' => env('DB_URL'),'host' => env('DB_HOST','127.0.0.1'),'port' => env('DB_PORT','3306'),'database' => env('DB_DATABASE','laravel'),'username' => env('DB_USERNAME','root'),'password' => env('DB_PASSWORD',''),'unix_socket' => env('DB_SOCKET',''),'charset' => env('DB_CHARSET','utf8mb4'),'collation' => env('DB_COLLATION','utf8mb4_unicode_ci'),'prefix' => '','prefix_indexes' => true,'strict' => true,'engine' => null,'options' => extension_loaded('pdo_mysql')? array_filter([(PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA)=> env('MYSQL_ATTR_SSL_CA'),]): [],],'mariadb' => [
'driver' => 'mariadb','url' => env('DB_URL'),'host' => env('DB_HOST','127.0.0.1'),'port' => env('DB_PORT','3306'),'database' => env('DB_DATABASE','laravel'),'username' => env('DB_USERNAME','root'),'password' => env('DB_PASSWORD',''),'unix_socket' => env('DB_SOCKET',''),'charset' => env('DB_CHARSET','utf8mb4'),'collation' => env('DB_COLLATION','utf8mb4_unicode_ci'),'prefix' => '','prefix_indexes' => true,'strict' => true,'engine' => null,'options' => extension_loaded('pdo_mysql')? array_filter([(PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA)=> env('MYSQL_ATTR_SSL_CA'),]): [],],'pgsql' => [
'driver' => 'pgsql','url' => env('DB_URL'),'host' => env('DB_HOST','127.0.0.1'),'port' => env('DB_PORT','5432'),'database' => env('DB_DATABASE','laravel'),'username' => env('DB_USERNAME','root'),'password' => env('DB_PASSWORD',''),'charset' => env('DB_CHARSET','utf8'),'prefix' => '','prefix_indexes' => true,'search_path' => 'public','sslmode' => env('DB_SSLMODE','prefer'),],'sqlsrv' => [
'driver' => 'sqlsrv','url' => env('DB_URL'),'host' => env('DB_HOST','localhost'),'port' => env('DB_PORT','1433'),'database' => env('DB_DATABASE','laravel'),'username' => env('DB_USERNAME','root'),'password' => env('DB_PASSWORD',''),'charset' => env('DB_CHARSET','utf8'),'prefix' => '','prefix_indexes' => true,],],'migrations' => [
'table' => 'migrations','update_date_on_publish' => true,],'redis' => [
'client' => env('REDIS_CLIENT','phpredis'),'options' => [
'cluster' => env('REDIS_CLUSTER','redis'),'prefix' => env('REDIS_PREFIX',Str::slug((string)env('APP_NAME','laravel')).'-database-'),'persistent' => env('REDIS_PERSISTENT',false),],'default' => [
'url' => env('REDIS_URL'),'host' => env('REDIS_HOST','127.0.0.1'),'username' => env('REDIS_USERNAME'),'password' => env('REDIS_PASSWORD'),'port' => env('REDIS_PORT','6379'),'database' => env('REDIS_DB','0'),'max_retries' => env('REDIS_MAX_RETRIES',3),'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM','decorrelated_jitter'),'backoff_base' => env('REDIS_BACKOFF_BASE',100),'backoff_cap' => env('REDIS_BACKOFF_CAP',1000),],'cache' => [
'url' => env('REDIS_URL'),'host' => env('REDIS_HOST','127.0.0.1'),'username' => env('REDIS_USERNAME'),'password' => env('REDIS_PASSWORD'),'port' => env('REDIS_PORT','6379'),'database' => env('REDIS_CACHE_DB','1'),'max_retries' => env('REDIS_MAX_RETRIES',3),'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM','decorrelated_jitter'),'backoff_base' => env('REDIS_BACKOFF_BASE',100),'backoff_cap' => env('REDIS_BACKOFF_CAP',1000),],],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\dompdf.php =====
return [
'show_warnings' => false,'public_path' => null,'convert_entities' => true,'options' => [
'font_dir' => storage_path('fonts'),'font_cache' => storage_path('fonts'),'temp_dir' => sys_get_temp_dir(),'chroot' => realpath(base_path()),'allowed_protocols' => [
'data:
'file:
'http:
'https:
],'artifactPathValidation' => null,'log_output_file' => null,'enable_font_subsetting' => false,'pdf_backend' => 'CPDF','default_media_type' => 'screen','default_paper_size' => 'a4','default_paper_orientation' => 'portrait','default_font' => 'serif','dpi' => 96,'enable_php' => false,'enable_javascript' => true,'enable_remote' => false,'allowed_remote_hosts' => null,'font_height_ratio' => 1.1,'enable_html5_parser' => true,],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\filesystems.php =====
return [
'default' => env('FILESYSTEM_DISK','local'),'disks' => [
'local' => [
'driver' => 'local','root' => storage_path('app/private'),'serve' => true,'throw' => false,'report' => false,],'public' => [
'driver' => 'local','root' => storage_path('app/public'),'url' => rtrim(env('APP_URL','http:
'visibility' => 'public','throw' => false,'report' => false,],'s3' => [
'driver' => 's3','key' => env('AWS_ACCESS_KEY_ID'),'secret' => env('AWS_SECRET_ACCESS_KEY'),'region' => env('AWS_DEFAULT_REGION'),'bucket' => env('AWS_BUCKET'),'url' => env('AWS_URL'),'endpoint' => env('AWS_ENDPOINT'),'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT',false),'throw' => false,'report' => false,],],'links' => [
public_path('storage')=> storage_path('app/public'),],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\logging.php =====
return [
'default' => env('LOG_CHANNEL','stack'),'deprecations' => [
'channel' => env('LOG_DEPRECATIONS_CHANNEL','null'),'trace' => env('LOG_DEPRECATIONS_TRACE',false),],'channels' => [
'stack' => [
'driver' => 'stack','channels' => explode(',',(string)env('LOG_STACK','single')),'ignore_exceptions' => false,],'single' => [
'driver' => 'single','path' => storage_path('logs/laravel.log'),'level' => env('LOG_LEVEL','debug'),'replace_placeholders' => true,],'daily' => [
'driver' => 'daily','path' => storage_path('logs/laravel.log'),'level' => env('LOG_LEVEL','debug'),'days' => env('LOG_DAILY_DAYS',14),'replace_placeholders' => true,],'slack' => [
'driver' => 'slack','url' => env('LOG_SLACK_WEBHOOK_URL'),'username' => env('LOG_SLACK_USERNAME',env('APP_NAME','Laravel')),'emoji' => env('LOG_SLACK_EMOJI',':boom:'),'level' => env('LOG_LEVEL','critical'),'replace_placeholders' => true,],'papertrail' => [
'driver' => 'monolog','level' => env('LOG_LEVEL','debug'),'handler' => env('LOG_PAPERTRAIL_HANDLER',SyslogUdpHandler::class),'handler_with' => [
'host' => env('PAPERTRAIL_URL'),'port' => env('PAPERTRAIL_PORT'),'connectionString' => 'tls:
],'processors' => [PsrLogMessageProcessor::class],],'stderr' => [
'driver' => 'monolog','level' => env('LOG_LEVEL','debug'),'handler' => StreamHandler::class,'handler_with' => [
'stream' => 'php:
],'formatter' => env('LOG_STDERR_FORMATTER'),'processors' => [PsrLogMessageProcessor::class],],'syslog' => [
'driver' => 'syslog','level' => env('LOG_LEVEL','debug'),'facility' => env('LOG_SYSLOG_FACILITY',LOG_USER),'replace_placeholders' => true,],'errorlog' => [
'driver' => 'errorlog','level' => env('LOG_LEVEL','debug'),'replace_placeholders' => true,],'null' => [
'driver' => 'monolog','handler' => NullHandler::class,],'emergency' => [
'path' => storage_path('logs/laravel.log'),],],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\mail.php =====
return [
'default' => env('MAIL_MAILER','log'),'mailers' => [
'smtp' => [
'transport' => 'smtp','scheme' => env('MAIL_SCHEME'),'url' => env('MAIL_URL'),'host' => env('MAIL_HOST','127.0.0.1'),'port' => env('MAIL_PORT',2525),'username' => env('MAIL_USERNAME'),'password' => env('MAIL_PASSWORD'),'timeout' => null,'local_domain' => env('MAIL_EHLO_DOMAIN',parse_url((string)env('APP_URL','http:
],'ses' => [
'transport' => 'ses',],'postmark' => [
'transport' => 'postmark',],'resend' => [
'transport' => 'resend',],'sendmail' => [
'transport' => 'sendmail','path' => env('MAIL_SENDMAIL_PATH','/usr/sbin/sendmail -bs -i'),],'log' => [
'transport' => 'log','channel' => env('MAIL_LOG_CHANNEL'),],'array' => [
'transport' => 'array',],'failover' => [
'transport' => 'failover','mailers' => [
'smtp','log',],'retry_after' => 60,],'roundrobin' => [
'transport' => 'roundrobin','mailers' => [
'ses','postmark',],'retry_after' => 60,],],'from' => [
'address' => env('MAIL_FROM_ADDRESS','hello@example.com'),'name' => env('MAIL_FROM_NAME',env('APP_NAME','Laravel')),],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\queue.php =====
return [
'default' => env('QUEUE_CONNECTION','database'),'connections' => [
'sync' => [
'driver' => 'sync',],'database' => [
'driver' => 'database','connection' => env('DB_QUEUE_CONNECTION'),'table' => env('DB_QUEUE_TABLE','jobs'),'queue' => env('DB_QUEUE','default'),'retry_after' =>(int)env('DB_QUEUE_RETRY_AFTER',90),'after_commit' => false,],'beanstalkd' => [
'driver' => 'beanstalkd','host' => env('BEANSTALKD_QUEUE_HOST','localhost'),'queue' => env('BEANSTALKD_QUEUE','default'),'retry_after' =>(int)env('BEANSTALKD_QUEUE_RETRY_AFTER',90),'block_for' => 0,'after_commit' => false,],'sqs' => [
'driver' => 'sqs','key' => env('AWS_ACCESS_KEY_ID'),'secret' => env('AWS_SECRET_ACCESS_KEY'),'prefix' => env('SQS_PREFIX','https:
'queue' => env('SQS_QUEUE','default'),'suffix' => env('SQS_SUFFIX'),'region' => env('AWS_DEFAULT_REGION','us-east-1'),'after_commit' => false,],'redis' => [
'driver' => 'redis','connection' => env('REDIS_QUEUE_CONNECTION','default'),'queue' => env('REDIS_QUEUE','default'),'retry_after' =>(int)env('REDIS_QUEUE_RETRY_AFTER',90),'block_for' => null,'after_commit' => false,],'deferred' => [
'driver' => 'deferred',],'background' => [
'driver' => 'background',],'failover' => [
'driver' => 'failover','connections' => [
'database','deferred',],],],'batching' => [
'database' => env('DB_CONNECTION','sqlite'),'table' => 'job_batches',],'failed' => [
'driver' => env('QUEUE_FAILED_DRIVER','database-uuids'),'database' => env('DB_CONNECTION','sqlite'),'table' => 'failed_jobs',],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\sanctum.php =====
return [
'stateful' => explode(',',env('SANCTUM_STATEFUL_DOMAINS',sprintf('%s%s','localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',Sanctum::currentApplicationUrlWithPort(),))),'guard' => ['web'],'expiration' => null,'token_prefix' => env('SANCTUM_TOKEN_PREFIX',''),'middleware' => [
'authenticate_session' => AuthenticateSession::class,'encrypt_cookies' => EncryptCookies::class,'validate_csrf_token' => ValidateCsrfToken::class,],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\services.php =====
return [
'postmark' => [
'key' => env('POSTMARK_API_KEY'),],'resend' => [
'key' => env('RESEND_API_KEY'),],'ses' => [
'key' => env('AWS_ACCESS_KEY_ID'),'secret' => env('AWS_SECRET_ACCESS_KEY'),'region' => env('AWS_DEFAULT_REGION','us-east-1'),],'slack' => [
'notifications' => [
'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),],],];
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\config\session.php =====
return [
'driver' => env('SESSION_DRIVER','database'),'lifetime' =>(int)env('SESSION_LIFETIME',120),'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE',false),'encrypt' => env('SESSION_ENCRYPT',false),'files' => storage_path('framework/sessions'),'connection' => env('SESSION_CONNECTION'),'table' => env('SESSION_TABLE','sessions'),'store' => env('SESSION_STORE'),'lottery' => [2,100],'cookie' => env('SESSION_COOKIE',Str::slug((string)env('APP_NAME','laravel')).'-session'),'path' => env('SESSION_PATH','/'),'domain' => env('SESSION_DOMAIN'),'secure' => env('SESSION_SECURE_COOKIE'),'http_only' => env('SESSION_HTTP_ONLY',true),'same_site' => env('SESSION_SAME_SITE','lax'),'partitioned' => env('SESSION_PARTITIONED_COOKIE',false),'serialization' => 'json',];

// === [Routes] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\routes\api.php =====
Route::post('/register',[AuthController::class,'register'])->middleware('throttle:register')->name('register');Route::post('/login',[AuthController::class,'login'])->middleware('throttle:login')->name('login');Route::middleware('auth:sanctum')->group(function(){Route::post('/logout',[AuthController::class,'logout']);Route::get('/me',[AuthController::class,'me']);});Route::get('/test',function(){return response()->json([
'working' => true
]);});Route::middleware('throttle:public-api')->group(function(){Route::apiResource('products',ProductController::class)->except(['store','update','destroy']);Route::apiResource('categories',CategoryController::class)->except(['store','update','destroy']);});Route::middleware('auth:sanctum')->group(function(){Route::apiResource('products',ProductController::class)->except(['index','show']);Route::apiResource('categories',CategoryController::class)->except(['index','show']);});Route::middleware(['auth:sanctum','throttle:cart'])->prefix('cart')->group(function(){Route::post('/add/{product_id}',[CartController::class,'add']);Route::get('/',[CartController::class,'getCartProducts']);Route::delete('/clear',[CartController::class,'deleteAll']);Route::delete('/remove',[CartController::class,'deleteProducts']);Route::post('/update/{product_id}',[CartController::class,'update']);});Route::middleware(['auth:sanctum','throttle:inventory-update'])->prefix('inventory')->group(function(){Route::get('/',[InventoryController::class,'index']);Route::get('/{productId}',[InventoryController::class,'show']);Route::put('/{productId}',[InventoryController::class,'update']);});Route::middleware('auth:sanctum')->prefix('orders')->group(function(){Route::get('/',[OrderController::class,'index'])->middleware('throttle:authenticated-api');Route::post('/checkout',[OrderController::class,'checkout']);Route::get('/{order}',[OrderController::class,'show'])->middleware('throttle:authenticated-api');Route::put('/{id}/status',[OrderController::class,'updateStatus'])->middleware([
'role:Admin','throttle:admin-actions',]);});Route::middleware('auth:sanctum')->group(function(){Route::get('/wallet',[WalletController::class,'show'])->middleware('throttle:authenticated-api');Route::post('/wallet/topup',[WalletController::class,'topUp'])->middleware('throttle:wallet');});Route::middleware('auth:sanctum')->group(function(){Route::get('/daily-sales-report/{date}',[DailySalesReportController::class,'show']);});Route::get('/test-checkout',function(){Auth::loginUsingId(1);$service = app(OrderService::class);return $service->checkout([
'shipping_address' => 'Damascus',]);});Route::get('/node-info',function(){return response()->json([
'hostname' => gethostname(),'server_id' => env('SERVER_ID','unknown'),'server_ip' => request()->server('SERVER_ADDR'),'timestamp' => now()->toISOString(),'php_version' => PHP_VERSION,]);});Route::prefix('nodes')->group(function(){Route::get('/status',[NodeController::class,'status']);Route::post('/{node}/stop',[NodeController::class,'stop']);Route::post('/{node}/start',[NodeController::class,'start']);Route::post('/restore-all',[NodeController::class,'restoreAll']);});
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\routes\console.php =====
Artisan::command('inspire',function(){$this->comment(Inspiring::quote());})->purpose('Display an inspiring quote');
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\routes\web.php =====
Route::get('/',function(){return view('welcome');});Route::get('/lb-dashboard',function(){return view('lb-dashboard');});

// === [Tests] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\tests\Feature\ExampleTest.php =====
namespace Tests\Feature;class ExampleTest extends TestCase{public function test_the_application_returns_a_successful_response(): void{$response = $this->get('/');$response->assertStatus(200);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\tests\Unit\ExampleTest.php =====
namespace Tests\Unit;class ExampleTest extends TestCase{public function test_that_true_is_true(): void{$this->assertTrue(true);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\tests\Feature\ProcessDailySalesJobTest.php =====
namespace Tests\Feature;class ProcessDailySalesJobTest extends TestCase{use RefreshDatabase;public function test_process_daily_sales_job_creates_report_and_updates_inventory(): void{$user = User::factory()->create();$product = Product::factory()->create();$inventory = Inventory::factory()->create([
'product_id' => $product->id,'quantity' => 100,]);$order = Order::factory()->create([
'user_id' => $user->id,'status' => 'Completed','payment_status' => 'paid','total_amount' => 200.00,'created_at' => Carbon::yesterday(),]);$orderItem = OrderItem::factory()->create([
'product_id' => $product->id,'order_id' => $order->id,'quantity' => 10,'unit_price' => 20.00,]);$job = new ProcessDailySalesJob;$job->handle(app(DailySalesProcessingService::class));$report = DailySalesReport::where('date',Carbon::yesterday()->toDateString())->first();$this->assertNotNull($report);$this->assertEquals(1,$report->total_orders);$this->assertEquals(200.00,$report->total_revenue);$this->assertNotNull($report->export_start_time);$this->assertNotNull($report->export_end_time);$this->assertNotNull($report->pdf_path);}public function test_job_skips_if_report_already_exists(): void{DailySalesReport::create([
'date' => Carbon::yesterday()->toDateString(),'total_orders' => 0,'total_revenue' => 0.0,'pdf_path' => null,]);$job = new ProcessDailySalesJob;$job->handle(app(DailySalesProcessingService::class));$report = DailySalesReport::where('date',Carbon::yesterday()->toDateString())->first();$this->assertEquals(0,$report->total_orders);}public function test_api_returns_report_data(): void{$report = DailySalesReport::create([
'date' => '2023-10-01','total_orders' => 5,'total_revenue' => 1000.00,'pdf_path' => 'public/daily-reports/test.pdf','export_start_time' => Carbon::now()->subMinutes(2),'export_end_time' => Carbon::now(),]);$user = User::factory()->create();$this->actingAs($user,'sanctum');$response = $this->getJson('/api/daily-sales-report/2023-10-01');$response->assertStatus(200)->assertJsonFragment([
'total_orders' => 5,'total_revenue' => '1000.00',])->assertJsonPath('pdf_url','/storage/daily-reports/test.pdf');}public function test_api_returns_404_if_report_not_found(): void{$user = User::factory()->create();$this->actingAs($user,'sanctum');$response = $this->getJson('/api/daily-sales-report/2023-10-01');$response->assertStatus(404)->assertJson(['message' => 'Report not found for the given date.']);}public function test_job_includes_all_orders_regardless_of_status(): void{$user = User::factory()->create();$product = Product::factory()->create();Order::factory()->create([
'user_id' => $user->id,'status' => 'Completed','payment_status' => 'paid','total_amount' => 100.00,'created_at' => Carbon::yesterday(),]);Order::factory()->create([
'user_id' => $user->id,'status' => 'Completed','payment_status' => 'failed','total_amount' => 50.00,'created_at' => Carbon::yesterday(),]);Order::factory()->create([
'user_id' => $user->id,'status' => 'Processing','payment_status' => 'paid','total_amount' => 75.00,'created_at' => Carbon::yesterday(),]);Order::factory()->create([
'user_id' => $user->id,'status' => 'Processing','payment_status' => 'pending','total_amount' => 25.00,'created_at' => Carbon::yesterday(),]);$job = new ProcessDailySalesJob;$job->handle(app(DailySalesProcessingService::class));$report = DailySalesReport::where('date',Carbon::yesterday()->toDateString())->first();$this->assertNotNull($report);$this->assertEquals(4,$report->total_orders);$this->assertEquals(250.00,$report->total_revenue);}public function test_processing_mode_enum_values(): void{$this->assertEquals('batch',ProcessingMode::Batch->value);$this->assertEquals('normal',ProcessingMode::Normal->value);$this->assertEquals('compare',ProcessingMode::Compare->value);}public function test_job_dispatch_with_batch_mode(): void{$user = User::factory()->create();$product = Product::factory()->create();Order::factory()->create([
'user_id' => $user->id,'status' => 'Completed','payment_status' => 'paid','total_amount' => 150.00,'created_at' => Carbon::yesterday(),]);$job = new ProcessDailySalesJob(Carbon::yesterday()->toDateString(),ProcessingMode::Batch);$job->handle(app(DailySalesProcessingService::class));$report = DailySalesReport::where('date',Carbon::yesterday()->toDateString())->first();$this->assertNotNull($report);$this->assertEquals('batch',$report->processing_mode);}public function test_job_dispatch_with_normal_mode(): void{$user = User::factory()->create();$product = Product::factory()->create();Order::factory()->create([
'user_id' => $user->id,'status' => 'Completed','payment_status' => 'paid','total_amount' => 150.00,'created_at' => Carbon::yesterday(),]);$job = new ProcessDailySalesJob(Carbon::yesterday()->toDateString(),ProcessingMode::Normal);$job->handle(app(DailySalesProcessingService::class));$report = DailySalesReport::where('date',Carbon::yesterday()->toDateString())->first();$this->assertNotNull($report);$this->assertEquals('normal',$report->processing_mode);}public function test_job_dispatch_with_compare_mode(): void{$user = User::factory()->create();$product = Product::factory()->create();Order::factory()->create([
'user_id' => $user->id,'status' => 'Completed','payment_status' => 'paid','total_amount' => 150.00,'created_at' => Carbon::yesterday(),]);$job = new ProcessDailySalesJob(Carbon::yesterday()->toDateString(),ProcessingMode::Compare);$job->handle(app(DailySalesProcessingService::class));$report = DailySalesReport::where('date',Carbon::yesterday()->toDateString())->first();$this->assertNotNull($report);$this->assertEquals('compare',$report->processing_mode);}public function test_processing_service_batch_returns_correct_structure(): void{$user = User::factory()->create();Order::factory(25)->create([
'user_id' => $user->id,'created_at' => Carbon::yesterday(),]);$service = app(DailySalesProcessingService::class);$result = $service->process(Carbon::yesterday()->toDateString(),ProcessingMode::Batch);$this->assertEquals(ProcessingMode::Batch->value,$result['mode']);$this->assertArrayHasKey('batch_result',$result);$this->assertArrayHasKey('total_orders',$result['batch_result']);$this->assertArrayHasKey('total_revenue',$result['batch_result']);$this->assertArrayHasKey('execution_time',$result['batch_result']);$this->assertArrayHasKey('peak_memory',$result['batch_result']);$this->assertArrayHasKey('memory_used',$result['batch_result']);$this->assertArrayHasKey('batches_metrics',$result['batch_result']);$this->assertArrayHasKey('orders_data',$result['batch_result']);}public function test_processing_service_normal_returns_correct_structure(): void{$user = User::factory()->create();Order::factory(25)->create([
'user_id' => $user->id,'created_at' => Carbon::yesterday(),]);$service = app(DailySalesProcessingService::class);$result = $service->process(Carbon::yesterday()->toDateString(),ProcessingMode::Normal);$this->assertEquals(ProcessingMode::Normal->value,$result['mode']);$this->assertArrayHasKey('normal_result',$result);$this->assertArrayHasKey('total_orders',$result['normal_result']);$this->assertArrayHasKey('execution_time',$result['normal_result']);$this->assertArrayHasKey('peak_memory',$result['normal_result']);$this->assertArrayHasKey('orders_data',$result['normal_result']);}public function test_orders_sample_limited_to_50(): void{$user = User::factory()->create();Order::factory(75)->create([
'user_id' => $user->id,'created_at' => Carbon::yesterday(),]);$service = app(DailySalesProcessingService::class);$result = $service->process(Carbon::yesterday()->toDateString(),ProcessingMode::Batch);$ordersData = $result['batch_result']['orders_data'];$this->assertLessThanOrEqual(50,count($ordersData));}public function test_batch_processor_tracks_metrics_correctly(): void{$user = User::factory()->create();Order::factory(1025)->create([
'user_id' => $user->id,'created_at' => Carbon::yesterday(),]);$service = app(DailySalesProcessingService::class);$result = $service->process(Carbon::yesterday()->toDateString(),ProcessingMode::Batch);$batchResult = $result['batch_result'];$this->assertGreaterThan(1,$batchResult['batches_count']);$this->assertNotEmpty($batchResult['batches_metrics']);foreach($batchResult['batches_metrics'] as $batch){$this->assertArrayHasKey('batch_number',$batch);$this->assertArrayHasKey('orders_count',$batch);$this->assertArrayHasKey('execution_time',$batch);$this->assertArrayHasKey('memory_before',$batch);$this->assertArrayHasKey('memory_after',$batch);}}public function test_performance_metrics_are_measured_correctly(): void{$user = User::factory()->create();Order::factory(25)->create([
'user_id' => $user->id,'created_at' => Carbon::yesterday(),]);$service = app(DailySalesProcessingService::class);$result = $service->process(Carbon::yesterday()->toDateString(),ProcessingMode::Batch);$metrics = $result['batch_result'];$this->assertIsFloat($metrics['execution_time']);$this->assertIsFloat($metrics['peak_memory']);$this->assertIsFloat($metrics['memory_used']);$this->assertGreaterThanOrEqual(0,$metrics['execution_time']);$this->assertGreaterThanOrEqual(0,$metrics['peak_memory']);}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\tests\TestCase.php =====
namespace Tests;abstract class TestCase extends BaseTestCase{}

// === [Console] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Console\Commands\GenerateFullProjectCompact.php =====
namespace App\Console\Commands;class GenerateFullProjectCompact extends Command{protected $signature = 'generate:exportProject';protected $description = 'Generate FullProject.php to AI';public function handle(): void{$outputPath = base_path('FullProject.php');$content = "
$sections = [
'Controllers' => app_path('Http/Controllers'),'Services' => app_path('Services'),'Models' => app_path('Models'),'Providers' => app_path('Providers'),'Requests' => app_path('Http/Requests'),'Helpers' => app_path('Helpers'),'Middleware' => app_path('Http/Middleware'),'Jobs' => app_path('Jobs'),'Processors' => app_path('Processors'),'Migrations' => database_path('migrations'),'Seeders' => database_path('seeders'),'Factories' => database_path('factories'),'Bootstrap' => base_path('Bootstrap'),'Config' => base_path('config'),'Routes' => base_path('routes'),'Tests' => base_path('Tests'),'Console' => app_path('Console'),'Resources_views_pdf' => base_path('resources/views/pdf'),'ApiCollections' => base_path('api-collections'),];foreach($sections as $sectionName => $path){if(! File::exists($path)){$this->warn("$sectionName directory not found,skipping...");continue;}$isYamlSection = $sectionName === 'ApiCollections';$files = File::allFiles($path);$files = array_filter($files,function($file)use($sectionName,$isYamlSection){if($isYamlSection){return in_array($file->getExtension(),['yaml','yml']);}if($sectionName === 'Migrations'){return $file->getExtension()=== 'php';}return $file->getExtension()== 'php';});usort($files,function($a,$b){return strcmp($a->getFilename(),$b->getFilename());});$content .= "\n
foreach($files as $file){$filename = str_replace(base_path().'/','',$file->getRealPath());$fileContent = File::get($file->getRealPath());if($isYamlSection){$fileContent = preg_replace('/
$fileContent = preg_replace('/^\s*$(?:\r\n?|\n)/m','',$fileContent);}else{$fileContent = str_replace(['',''],'',$fileContent);$fileContent = preg_replace('/^use .*;/m','',$fileContent);$fileContent = preg_replace('/^declare\(.*\);/m','',$fileContent);$fileContent = preg_replace('/\/\/.*$/m','',$fileContent);$fileContent = preg_replace('/
$fileContent = preg_replace('
$fileContent = preg_replace('/^\s*$(?:\r\n?|\n)/m','',$fileContent);$fileContent = preg_replace('/\s*{\s*/','{',$fileContent);$fileContent = preg_replace('/\s*}\s*/','}',$fileContent);$fileContent = preg_replace('/\s*;\s*/',';',$fileContent);$fileContent = preg_replace('/\s*\(\s*/','(',$fileContent);$fileContent = preg_replace('/\s*\)\s*/',')',$fileContent);$fileContent = preg_replace('/\s*,\s*/',',',$fileContent);$fileContent = preg_replace('/[ ]{2,}/',' ',$fileContent);$fileContent = preg_replace('/^\s+/m','',$fileContent);}$content .= "
$content .= $fileContent."\n";}}File::put($outputPath,$content);$this->info('FullProject.php generated successfully with minified,cleaned,organized content.');}}
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\app\Console\Kernel.php =====
namespace App\Console;class Kernel extends ConsoleKernel{protected function schedule(Schedule $schedule): void{$schedule->job(new ProcessDailySalesJob)->dailyAt('00:00');}protected function commands(): void{$this->load(__DIR__.'/Commands');require base_path('routes/console.php');}}

// === [Resources_views_pdf] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\resources\views\pdf\daily-sales-report.blade.php =====
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Batch Processing Performance Analysis Report -{{$date}}</title>
<style>
*{margin: 0;padding: 0;}body{font-family: Arial,sans-serif;font-size: 12px;line-height: 1.4;color: 
.container{width: 100%;padding: 20px;}h1{text-align: center;font-size: 24px;margin-bottom: 20px;color: 
h2{font-size: 14px;margin-top: 20px;margin-bottom: 10px;color: 
h3{font-size: 12px;margin-top: 15px;margin-bottom: 8px;color: 
table{width: 100%;border-collapse: collapse;margin-top: 10px;}th{background-color: 
td{border: 1px solid 
tr:nth-child(even){background-color: 
.summary-grid{display: table;width: 100%;margin-top: 10px;}.summary-item{display: table-cell;width: 25%;padding: 8px;border-right: 1px solid 
.summary-item:last-child{border-right: none;}.summary-label{font-weight: bold;color: 
.summary-value{font-size: 14px;color: 
.metric-row{display: table;width: 100%;margin-top: 5px;}.metric-col{display: table-cell;width: 50%;padding: 5px;}.metric-label{font-weight: bold;color: 
.metric-value{color: 
.positive{color: 
.batch-item{margin-bottom: 8px;padding: 8px;background-color: 
.orders-table{margin-top: 15px;}.page-break{page-break-after: always;margin-top: 30px;}.executive-summary{background-color: 
.executive-summary table{margin-top: 10px;}.executive-summary th{background-color: 
.insights{background-color: 
.academic-section{background-color: 
.academic-section ul{margin-left: 20px;}</style>
</head>
<body>
<div class="container">
<!-- Header -->
<h1>Batch Processing Performance Analysis Report</h1>{{-- ===== Sales Statistics Table ===== --}}<div class="executive-summary">
<h2>Sales Statistics</h2>
<table> <thead>
<tr style="background:
<th>Total Cost</th>
<th>Average Order</th>
<th>Completed</th>
<th>Cancelled</th>
<th>Processing</th>
<th>Pending</th>
</tr>
</thead>
<tbody>
<tr style="text-align:center;">
<td>{{number_format($order_stats['total_cost'],2)}}</td>
<td>{{number_format($order_stats['average_order'],2)}}</td>
<td>{{$order_stats['completed_orders']}}</td>
<td>{{$order_stats['canceled_orders']}}</td>
<td>{{$order_stats['processing_orders']}}</td>
<td>{{$order_stats['pending_orders']}}</td>
</tr>
</tbody>
</table>
</div>
<!-- Executive Summary -->
<div class="executive-summary">
<h2>Executive Summary</h2>
<table>
<thead>
<tr>
<th>Metric</th>
<th>Normal Processing</th>
<th>Batch Processing</th>
</tr>
</thead>
<tbody>
<tr>
<td>Orders Processed</td>
<td>{{$normal_stats['orders_processed'] ?? 'N/A'}}</td>
<td>{{$batch_stats['orders_processed'] ?? 'N/A'}}</td>
</tr>
<tr>
<td>Execution Time</td>
<td>{{$normal_stats['execution_time'] ?? 'N/A'}}s</td>
<td>{{$batch_stats['execution_time'] ?? 'N/A'}}s</td>
</tr>
<tr>
<td>Peak Memory Usage</td>
<td>{{$normal_stats['peak_memory_real'] ?? 'N/A'}}MB</td>
<td>{{$batch_stats['peak_memory_real'] ?? 'N/A'}}MB</td>
</tr>
<tr>
<td>Total Memory Increase</td>
<td>{{$normal_stats['memory_delta'] ?? 'N/A'}}MB</td>
<td>{{$batch_stats['memory_delta'] ?? 'N/A'}}MB</td>
</tr>
<tr>
<td>Processing Status</td>
<td>{{$normal_stats['status'] ?? 'N/A'}}</td>
<td>{{$batch_stats['status'] ?? 'N/A'}}</td>
</tr>
<tr>
<td>Batch Count</td>
<td>N/A</td>
<td>{{$batch_stats['batch_count'] ?? 'N/A'}}</td>
</tr>
<tr>
<td>Batch Size</td>
<td>N/A</td>
<td>{{$batch_stats['batch_size'] ?? 'N/A'}}</td>
</tr>
</tbody>
</table>
</div>
<!-- Memory Consumption Analysis -->
<h2>Memory Consumption Analysis</h2>
<table>
<thead>
<tr>
<th>Metric</th>
<th>Normal Processing</th>
<th>Batch Processing</th>
</tr>
</thead>
<tbody>
<tr>
<td>Start Memory(Real)</td>
<td>{{$normal_stats['start_memory_real'] ?? 'N/A'}}MB</td>
<td>{{$batch_stats['start_memory_real'] ?? 'N/A'}}MB</td>
</tr>
<tr>
<td>End Memory(Real)</td>
<td>{{$normal_stats['end_memory_real'] ?? 'N/A'}}MB</td>
<td>{{$batch_stats['end_memory_real'] ?? 'N/A'}}MB</td>
</tr>
<tr>
<td>Peak Memory(Real)</td>
<td>{{$normal_stats['peak_memory_real'] ?? 'N/A'}}MB</td>
<td>{{$batch_stats['peak_memory_real'] ?? 'N/A'}}MB</td>
</tr>
<tr>
<td>Memory Increase</td>
<td>{{$normal_stats['memory_delta'] ?? 'N/A'}}MB</td>
<td>{{$batch_stats['memory_delta'] ?? 'N/A'}}MB</td>
</tr>
<tr>
<td>Allocated Memory</td>
<td>{{$normal_stats['peak_memory_allocated'] ?? 'N/A'}}MB</td>
<td>{{$batch_stats['peak_memory_allocated'] ?? 'N/A'}}MB</td>
</tr>{{-- <tr>--}}{{-- <td>Peak Allocated Memory</td>--}}{{-- <td>{{$normal_stats['peak_memory_allocated'] ?? 'N/A'}}MB</td>--}}{{-- <td>{{$batch_stats['peak_memory_allocated'] ?? 'N/A'}}MB</td>--}}{{-- </tr>--}}<tr>
<td>Number of Batches</td>
<td>N/A</td>
<td>{{$batch_stats['batch_count'] ?? 'N/A'}}</td>
</tr>
<tr>
<td>Average Batch Memory</td>
<td>N/A</td>
<td>{{$batch_stats['average_batch_memory'] ?? 'N/A'}}MB</td>
</tr>
<tr>
<td>Largest Batch Memory</td>
<td>N/A</td>
<td>{{$batch_stats['largest_batch_memory'] ?? 'N/A'}}MB</td>
</tr>
<tr>
<td>Smallest Batch Memory</td>
<td>N/A</td>
<td>{{$batch_stats['smallest_batch_memory'] ?? 'N/A'}}MB</td>
</tr>
</tbody>
</table>
<!-- Performance Percentage -->
@if(isset($comparison['memory_reduction_percent']))<h2>Performance Analysis</h2>
<p>Memory Reduction %:{{$comparison['memory_reduction_percent']}}%</p>
<p>Batch Processing was{{$comparison['speed_improvement_percent']}}% faster than Normal Processing.</p>
<p>.</p>
<p>.</p>
<p>.</p>
<p>.</p>
<p>.</p>
@endif
<!-- Performance Insights -->
<div class="insights">
<h2>Performance Insights</h2>
<p>
@if(isset($comparison['memory_reduction_percent'])&& $comparison['memory_reduction_percent'] > 0)Normal Processing loaded all orders into memory at once,causing very high memory usage of{{$normal_stats['peak_memory_real'] ?? 'N/A'}}MB.
Batch Processing kept memory stable by processing orders in chunks,reducing peak memory to{{$batch_stats['peak_memory_real'] ?? 'N/A'}}MB.
This represents a{{$comparison['memory_reduction_percent']}}% reduction in memory usage.
@else
Batch Processing maintained efficient memory usage through chunked processing.
@endif
</p>
<p>
@if(isset($comparison['speed_improvement_percent']))@if($comparison['speed_improvement_percent'] > 0)Batch Processing was{{$comparison['speed_improvement_percent']}}% faster than Normal Processing.
@elseif($comparison['speed_improvement_percent'] < 0)Normal Processing was faster in this case,but Batch Processing provides better scalability.
@else
Execution times were comparable between both methods.
@endif
@endif
</p>
<p>
System scalability improved significantly using chunked processing,allowing handling of larger datasets without memory exhaustion.
</p>
</div>
</div>
</body>
</html>

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\resources\views\pdf\invoice.blade.php =====
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice</title>
<style>
body{font-family: sans-serif;margin: 30px;}h1{text-align: center;}.section{margin-bottom: 20px;}table{width: 100%;border-collapse: collapse;}table,th,td{border: 1px solid black;}th,td{padding: 10px;text-align: left;}</style>
</head>
<body>
<h1>Invoice</h1>
<div class="section">
<strong>Invoice Number:</strong>{{$invoice_number}}<br>
<strong>Purchase Date:</strong>{{$purchase_date}}<br>
<strong>Customer Name:</strong>{{$customer_name}}<br>
<strong>Shipping Address:</strong>{{$shipping_address}}<br>
<strong>Payment Status:</strong>{{$payment_status}}</div>
<table>
<thead>
<tr>
<th>Product</th>
<th>Quantity</th>
<th>Unit Price</th>
<th>Subtotal</th>
</tr>
</thead>
<tbody>
@foreach($items as $item)<tr>
<td>{{$item['name']}}</td>
<td>{{$item['quantity']}}</td>
<td>${{$item['unit_price']}}</td>
<td>${{$item['subtotal']}}</td>
</tr>
@endforeach
</tbody>
</table>
<br>
<strong>Total Amount:</strong>${{$total_amount}}</body>
</html>


// === [ApiCollections] ===
// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Category\Create Category.yml =====
info:
  name: Create Category
  type: http
  seq: 1
http:
  method: POST
  url: "{{base_url}}/categories"
  headers:
    - name: Accept
      value: application/json
  body:
    type: multipart-form
    data:
      - name: name
        type: text
        value: phones-alaa
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Product\Create Product.yml =====
info:
  name: Create Product
  type: http
  seq: 1
http:
  method: POST
  url: "{{base_url}}/products"
  body:
    type: multipart-form
    data:
      - name: name
        type: text
        value: iPhone 15
      - name: description
        type: text
        value: Apple smartphone
      - name: price
        type: text
        value: "1200"
      - name: category_id
        type: text
        value: "5"
      - name: image
        type: file
        value:
          - postman-cloud:///1efdca67-1582-4a60-90f8-ffdc53b327d3
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Category\Delete Category.yml =====
info:
  name: Delete Category
  type: http
  seq: 5
http:
  method: DELETE
  url: "{{base_url}}/categories/1"
  headers:
    - name: Accept
      value: application/json
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Product\Delete Product.yml =====
info:
  name: Delete Product
  type: http
  seq: 5
http:
  method: DELETE
  url: "{{base_url}}/products/1"
  headers:
    - name: Accept
      value: application/json
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Category\Get Categories.yml =====
info:
  name: Get Categories
  type: http
  seq: 3
http:
  method: GET
  url: "{{base_url}}/categories"
  body:
    type: multipart-form
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Category\Get Category.yml =====
info:
  name: Get Category
  type: http
  seq: 4
http:
  method: GET
  url: "{{base_url}}/categories/5"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Product\Get Product.yml =====
info:
  name: Get Product
  type: http
  seq: 4
http:
  method: GET
  url: "{{base_url}}/products/2"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Product\Get Products.yml =====
info:
  name: Get Products
  type: http
  seq: 3
http:
  method: GET
  url: "{{base_url}}/products"
  body:
    type: multipart-form
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Auth\Login.yml =====
info:
  name: Login
  type: http
  seq: 2
http:
  method: POST
  url: "{{base_url}}/login"
  headers:
    - name: Contant-Type
      value: application/json
      disabled: true
  body:
    type: multipart-form
    data:
      - name: email
        type: text
        value: admin@example.com
      - name: password
        type: text
        value: password
  auth: inherit
runtime:
  scripts:
    - type: after-response
      code: |-
        test("Successful POST request", function () {
            expect(res.getStatus()).to.be.oneOf([200, 201]);
        });
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5
docs: |-
  This is a POST request, submitting data to an API via the request body. This request submits JSON data, and the data is reflected in the response.
  A successful POST request typically returns a `200 OK` or `201 Created` response code.

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Auth\Logout.yml =====
info:
  name: Logout
  type: http
  seq: 4
http:
  method: POST
  url: "{{base_url}}/logout"
  auth:
    type: bearer
    token: qJhYEpmWe76jVmVTEv0ieClXFdYA7A8xiLzYtoR9273dceee
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Auth\Me.yml =====
info:
  name: Me
  type: http
  seq: 3
http:
  method: GET
  url: "{{base_url}}/me"
  auth:
    type: bearer
    token: qJhYEpmWe76jVmVTEv0ieClXFdYA7A8xiLzYtoR9273dceee
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Auth\Register.yml =====
info:
  name: Register
  type: http
  seq: 1
http:
  method: POST
  url: "{{base_url}}/register"
  headers:
    - name: Accept
      value: application/json
  body:
    type: multipart-form
    data:
      - name: name
        type: text
        value: Test User
      - name: email
        type: text
        value: test@example.com
      - name: password
        type: text
        value: "123456"
      - name: password_confirmation
        type: text
        value: "123456"
  auth: inherit
runtime:
  scripts:
    - type: after-response
      code: |-
        test("Status code is 200", function () {
            expect(res.getStatus()).to.equal(200);
        });
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5
docs: |-
  This is a GET request and it is used to "get" data from an endpoint. There is no request body for a GET request, but you can use query parameters to help specify the resource you want data on (e.g., in this request, we have `id=1`).
  A successful GET response will have a `200 OK` status, and should include some kind of response body - for example, HTML web content or JSON data.

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Report Daily\Report.yml =====
info:
  name: Report
  type: http
  seq: 1
http:
  method: GET
  url: "{{base_url}}/daily-sales-report/2026-05-09"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Category\Update Category.yml =====
info:
  name: Update Category
  type: http
  seq: 2
http:
  method: PUT
  url: "{{base_url}}/categories/1"
  body:
    type: multipart-form
    data:
      - name: name
        type: text
        value: phones-alaa
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Product\Update Product.yml =====
info:
  name: Update Product
  type: http
  seq: 2
http:
  method: PUT
  url: "{{base_url}}/products/6"
  body:
    type: multipart-form
    data:
      - name: name
        type: text
        value: phones-alaa
      - name: description
        type: text
        value: Apple smartphone
      - name: price
        type: text
        value: "1000"
      - name: category_id
        type: text
        value: "9"
      - name: image
        type: file
        value: []
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Cart\add to cart.yml =====
info:
  name: add to cart
  type: http
  seq: 2
http:
  method: POST
  url: "{{base_url}}/cart/add/21"
  body:
    type: multipart-form
    data:
      - name: quantity
        type: text
        value: "3"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\environments\base_url.yml =====
name: base_url
variables:
  - name: base_url
    value: http://127.0.0.1:8000/api
  - name: token
    value: 1|7b7bBX0fn2Semgobgt8ynoLz8NAn7JSgT1Az0G8c426d150c

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Wallet\check wallet.yml =====
info:
  name: check wallet
  type: http
  seq: 1
http:
  method: GET
  url: "{{base_url}}/wallet"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Order\checkout.yml =====
info:
  name: checkout
  type: http
  seq: 2
http:
  method: POST
  url: "{{base_url}}/orders/checkout"
  body:
    type: multipart-form
    data:
      - name: shipping_address
        type: text
        value: Damascus
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Cart\clear cart.yml =====
info:
  name: clear cart
  type: http
  seq: 3
http:
  method: DELETE
  url: "{{base_url}}/cart/clear"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Auth\folder.yml =====
info:
  name: Auth
  type: folder
  seq: 1
request:
  auth: inherit

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Cart\folder.yml =====
info:
  name: Cart
  type: folder
  seq: 4
request:
  auth: inherit

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Category\folder.yml =====
info:
  name: Category
  type: folder
  seq: 2
request:
  auth:
    type: bearer
    token: ""

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Order\folder.yml =====
info:
  name: Order
  type: folder
  seq: 6
request:
  auth: inherit

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Product\folder.yml =====
info:
  name: Product
  type: folder
  seq: 3
request:
  auth:
    type: bearer
    token: ""

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Report Daily\folder.yml =====
info:
  name: Report Daily
  type: folder
  seq: 8
request:
  auth: inherit

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Wallet\folder.yml =====
info:
  name: Wallet
  type: folder
  seq: 7
request:
  auth: inherit

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\inventory\folder.yml =====
info:
  name: inventory
  type: folder
  seq: 5
request:
  auth: inherit

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Order\get all.yml =====
info:
  name: get all
  type: http
  seq: 1
http:
  method: GET
  url: "{{base_url}}/orders"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\inventory\get all.yml =====
info:
  name: get all
  type: http
  seq: 3
http:
  method: GET
  url: "{{base_url}}/inventory"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Cart\get products.yml =====
info:
  name: get products
  type: http
  seq: 1
http:
  method: GET
  url: ""
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\inventory\get quantity of product.yml =====
info:
  name: get quantity of product
  type: http
  seq: 2
http:
  method: GET
  url: ""
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\opencollection.yml =====
opencollection: 1.0.0
info:
  name: E-Commerce-Backend-Engine
config:
  proxy:
    inherit: true
    config:
      protocol: http
      hostname: ""
      port: ""
      auth:
        username: ""
        password: ""
      bypassProxy: ""
request:
  headers:
    - name: Accept
      value: application/json
  auth:
    type: bearer
    token: 1|Zs3YSgartLT4oHQHQ8pK0MEzyRFbZKgz3aEe98Cb6061d27c
  variables:
    - name: id
      value: "1"
    - name: base_url
      value: localhost/api
docs:
  content: |-
    This template guides you through CRUD operations (GET, POST, PUT, DELETE), variables, and tests.
    RESTful APIs allow you to perform CRUD operations using the POST, GET, PUT, and DELETE HTTP methods.
    This collection contains each of these [request](https://learning.postman.com/docs/sending-requests/requests/) types. Open each request and click "Send" to see what happens.
    Observe the response tab for status code (200 OK), response time, and size.
    Update or add new data in "Body" in the POST request. Typically, Body data is also used in PUT request.
    ```
    {
        "name": "Add your name in the body"
    }
     ```
    Variables enable you to store and reuse values in Postman. We have created a [variable](https://learning.postman.com/docs/sending-requests/variables/) called `base_url` with the sample request [https://postman-api-learner.glitch.me](https://postman-api-learner.glitch.me). Replace it with your API endpoint to customize this collection.
    Adding tests to your requests can help you confirm that your API is working as expected. You can write test scripts in JavaScript and view the output in the "Test Results" tab.
    <img src="https://content.pstmn.io/fa30ea0a-373d-4545-a668-e7b283cca343/aW1hZ2UucG5n" alt="" height="1530" width="2162">
    - Use folders to group related requests and organize the collection.
    - Add more [scripts](https://learning.postman.com/docs/writing-scripts/intro-to-scripts/) to verify if the API works as expected and execute workflows.
    [API testing basics](https://go.postman.co/redirect/workspace?type=personal&collectionTemplateId=e9a37a28-055b-49cd-8c7e-97494a21eb54&sourceTemplateId=ddb19591-3097-41cf-82af-c84273e56719)  
    [API documentation](https://go.postman.co/redirect/workspace?type=personal&collectionTemplateId=e9c28f47-1253-44af-a2f3-20dce4da1f18&sourceTemplateId=ddb19591-3097-41cf-82af-c84273e56719)  
    [Authorization methods](https://go.postman.co/redirect/workspace?type=personal&collectionTemplateId=31a9a6ed-4cdf-4ced-984c-d12c9aec1c27&sourceTemplateId=ddb19591-3097-41cf-82af-c84273e56719)
  type: text/markdown
bundled: false
extensions:
  bruno:
    ignore:
      - node_modules
      - .git

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Cart\remove products.yml =====
info:
  name: remove products
  type: http
  seq: 4
http:
  method: DELETE
  url: "{{base_url}}/cart/remove"
  params:
    - name: ""
      value: ""
      type: query
  body:
    type: json
    data: |-
      {
        "product_ids":[3]
      }
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Order\show.yml =====
info:
  name: show
  type: http
  seq: 4
http:
  method: GET
  url: "{{base_url}}/orders/31"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Wallet\topup.yml =====
info:
  name: topup
  type: http
  seq: 2
http:
  method: POST
  url: "{{base_url}}/wallet/topup"
  body:
    type: multipart-form
    data:
      - name: amount
        type: text
        value: "9000000.05"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Cart\update quantity.yml =====
info:
  name: update quantity
  type: http
  seq: 5
http:
  method: PATCH
  url: "{{base_url}}/cart/update/3"
  body:
    type: multipart-form
    data:
      - name: quantity
        type: text
        value: "3"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\inventory\update quantity.yml =====
info:
  name: update quantity
  type: http
  seq: 1
http:
  method: PUT
  url: "{{base_url}}/inventory/1"
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

// ===== D:\Development\Laravel\E-Commerce-Backend-Engine\api-collections\Order\update status.yml =====
info:
  name: update status
  type: http
  seq: 3
http:
  method: PUT
  url: "{{base_url}}/orders/31/status"
  body:
    type: multipart-form
    data:
      - name: status
        type: text
        value: Processing
  auth: inherit
settings:
  encodeUrl: true
  timeout: 0
  followRedirects: true
  maxRedirects: 5

