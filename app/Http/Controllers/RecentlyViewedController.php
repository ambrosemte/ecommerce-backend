<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Services\RecentlyViewedService;

class RecentlyViewedController extends Controller
{
    protected $recentlyViewedService;
    public function __construct(RecentlyViewedService $recentlyViewedService)
    {
        $this->recentlyViewedService = $recentlyViewedService;
    }

    public function index()
    {
        try {
            $data = $this->recentlyViewedService->getRecentlyViewedItems();
        } catch (\Exception $e) {
            return Response::error(500, 'Failed to fetch recently viewed items');
        }

        return Response::success(message: "Recently viewed retrieved", data: $data);
    }

}
