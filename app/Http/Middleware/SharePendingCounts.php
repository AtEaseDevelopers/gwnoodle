<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\InventoryRequest;
use App\Models\InventoryCount;

class SharePendingCounts
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Share with all views after authentication
        view()->share([
            'pendingStockRequestsCount' => InventoryRequest::where('status', InventoryRequest::STATUS_PENDING)->count(),
            'pendingStockCountsCount' => InventoryCount::where('status', InventoryCount::STATUS_PENDING)->count(),
        ]);
        
        return $next($request);
    }
}
