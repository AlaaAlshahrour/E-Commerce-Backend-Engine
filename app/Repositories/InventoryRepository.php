<?php

namespace App\Repositories;

use App\Models\Inventory;
use App\Models\Product;

class InventoryRepository
{
    public function getAll()
    {
        return Inventory::query()
            ->with('product:id,name,price,photo_url')
            ->get()
            ->map(function ($inventory) {
                return [
                    'product_id'   => $inventory->product->id,
                    'product_name' => $inventory->product->name,
                    'price'        => $inventory->product->price,
                    'photo_url'    => $inventory->product->photo_url,
                    'quantity'     => $inventory->quantity,
                ];
            });
    }

    public function getByProductId(int $productId)
    {
        return Inventory::query()
            ->with('product:id,name,price,photo_url')
            ->where('product_id', $productId)
            ->first();
    }

    public function incrementQuantity(int $productId, int $quantity): bool
    {
        return Inventory::where('product_id', $productId)
            ->increment('quantity' , $quantity);
    }
}
