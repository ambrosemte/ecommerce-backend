<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all()->toArray();

        return Response::success(message: "Categories retrieved", data: $categories);
    }
}
