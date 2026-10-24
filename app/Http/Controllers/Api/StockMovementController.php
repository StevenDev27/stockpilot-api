<?php

namespace App\Http\Controllers\Api;

use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Movement\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockMovementController extends Controller
{
    public function index(Request $request, Product $product)
    {
        $request->validate([
            'type' => ['nullable', Rule::enum(MovementType::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $movements = $product->movements()
            ->with('user:id,name')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return StockMovementResource::collection($movements);
    }

    public function store(StoreStockMovementRequest $request, Product $product, StockService $stock)
    {
        $movement = $stock->register(
            $product,
            $request->user(),
            $request->enum('type', MovementType::class),
            $request->integer('quantity'),
            $request->input('reason'),
        );

        return new StockMovementResource($movement->load('user:id,name', 'product'));
    }
}
