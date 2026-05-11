<?php

namespace App\Services;

use App\Models\Inventory;
use App\Repositories\InventoryRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    protected $inventoryRepository;

    public function __construct(InventoryRepository $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    public function getAll(): array
    {
        $inventories = $this->inventoryRepository->getAll();

        if ($inventories->isEmpty()) {
            return ['message' => 'No inventory found'];
        }

        return ['data' => $inventories];
    }

    public function getByProductId(int $productId): array
    {
        $inventory = $this->inventoryRepository->getByProductId($productId);

        if (!$inventory) {
            return ['message' => 'Product not found in inventory'];
        }

        return [
            'data' => [
                'product_id'   => $inventory->product->id,
                'product_name' => $inventory->product->name,
                'price'        => $inventory->product->price,
                'photo_url'    => $inventory->product->photo_url,
                'quantity'     => $inventory->quantity,
            ]
        ];
    }

    public function updateQuantityUnsafe(int $productId, int $quantity): array
    {

        $inventory = $this->inventoryRepository->getByProductId($productId);
        sleep(1);
        if (!$inventory) {
            return ['success' => false, 'message' => 'Product not found in inventory'];
        }

        $this->inventoryRepository->updateQuantity($productId, $quantity);

        return ['success' => true, 'message' => 'Inventory updated successfully'];
    }
    public function updateQuantitySafe(int $productId, int $quantity): array
    {

        return DB::transaction(function () use ($productId, $quantity) {

            $inventory = Inventory::where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                return ['success' => false, 'message' => 'Product not found'];
            }

            $secondsSinceLastUpdate = now()->diffInSeconds($inventory->updated_at);
            $pendingPurchases = (int) Cache::get("product:active_purchases:{$productId}", 0);

            if ($secondsSinceLastUpdate < 5 || $pendingPurchases >0) {
                return [
                    'success' => false,
                    'message' => 'Inventory was just modified or has a recent purchase. Retry in a moment to avoid overwriting it.',

                ];
            }


            $inventory->update(['quantity' => $quantity]);

            return ['success' => true, 'message' => 'Inventory updated successfully'];
        });
    }
}
