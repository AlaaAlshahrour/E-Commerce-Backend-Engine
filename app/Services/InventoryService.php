<?php

namespace App\Services;

use App\Repositories\InventoryRepository;

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

    public function updateQuantity(int $productId, int $quantity): array
    {
        $inventory = $this->inventoryRepository->getByProductId($productId);

        if (!$inventory) {
            return ['success' => false, 'message' => 'Product not found in inventory'];
        }

        $this->inventoryRepository->updateQuantity($productId, $quantity);

        return ['success' => true, 'message' => 'Inventory updated successfully'];
    }
}
