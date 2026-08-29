<?php

namespace App\Http\Controllers;

use App\Http\Resources\BestSellerResource;
use App\Models\BestSeller;
use Illuminate\Http\JsonResponse;

class BestSellerController extends Controller
{
    /**
     * Display the most recent best seller rankings grouped by list_name.
     */
    public function index(): JsonResponse
    {
        $latestWeekDate = BestSeller::max('week_date');

        if (! $latestWeekDate) {
            return response()->json([
                'week_date' => null,
                'data' => (object) [],
            ]);
        }

        $bestSellers = BestSeller::with(['book.authors', 'book.genres'])
            ->where('week_date', $latestWeekDate)
            ->orderBy('rank', 'asc')
            ->get();

        $grouped = $bestSellers->groupBy('list_name')->map(function ($items) {
            return BestSellerResource::collection($items);
        });

        return response()->json([
            'week_date' => $latestWeekDate,
            'data' => $grouped,
        ]);
    }
}
