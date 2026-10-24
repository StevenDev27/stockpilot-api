<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function stockValuation(): JsonResponse
    {
        $rows = Category::query()
            ->leftJoin('products', 'products.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('categories.id, categories.name,
                COUNT(products.id) as products_count,
                COALESCE(SUM(products.stock), 0) as units,
                COALESCE(SUM(products.stock * products.price), 0) as value')
            ->orderBy('categories.name')
            ->get()
            ->map(fn ($row) => [
                'category_id' => $row->id,
                'category' => $row->name,
                'products_count' => (int) $row->products_count,
                'units' => (int) $row->units,
                'value' => round((float) $row->value, 2),
            ]);

        return response()->json([
            'data' => $rows,
            'total_units' => $rows->sum('units'),
            'total_value' => round($rows->sum('value'), 2),
        ]);
    }
}
