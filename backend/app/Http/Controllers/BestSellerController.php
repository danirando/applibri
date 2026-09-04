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
        $latestWeekDates = BestSeller::query()
            ->selectRaw('list_name, MAX(week_date) as week_date')
            ->groupBy('list_name')
            ->pluck('week_date', 'list_name');

        if ($latestWeekDates->isEmpty()) {
            return response()->json([
                'week_date' => null,
                'data' => (object) [],
            ]);
        }

        $bestSellers = BestSeller::with(['book.authors', 'book.genres'])
            ->whereRaw(
                'week_date = (SELECT MAX(bs_latest.week_date) FROM best_sellers AS bs_latest WHERE bs_latest.list_name = best_sellers.list_name)'
            )
            ->orderBy('rank', 'asc')
            ->get();

        $grouped = $bestSellers->groupBy('list_name')->map(function ($items) {
            return BestSellerResource::collection($items);
        });

        return response()->json([
            'week_date' => $latestWeekDates,
            'data' => $grouped,
        ]);
    }
}
