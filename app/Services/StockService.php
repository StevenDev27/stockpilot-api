<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function register(
        Product $product,
        User $user,
        MovementType $type,
        int $quantity,
        ?string $reason = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $user, $type, $quantity, $reason) {
            // Bloquea la fila: otra petición sobre este producto espera a que termine esta.
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            $newStock = $type === MovementType::In
                ? $locked->stock + $quantity
                : $locked->stock - $quantity;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ["Stock insuficiente: disponible {$locked->stock}, solicitado {$quantity}."],
                ]);
            }

            $locked->update(['stock' => $newStock]);

            return $locked->movements()->create([
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
            ]);
        });
    }
}
