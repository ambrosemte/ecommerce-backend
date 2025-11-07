<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\CategorySpecification;
use App\Models\SpecificationKey;
use Illuminate\Http\Request;

class SpecificationController extends Controller
{
    public function index($categoryId)
    {
        $specificationIds = CategorySpecification::query()->where('category_id', $categoryId)->pluck('specification_key_id');

        $specifications = SpecificationKey::whereIn('id', $specificationIds)
            ->with('specificationValues')
            ->get()
            ->toArray();

        return Response::success(message: 'Specifications retrieved', data: $specifications);
    }

    /**
     * Create a new specification key and attach it to a category.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'type' => 'required|in:text,integer,list,multiple',
            'values' => 'nullable|array',
            'values.*' => 'string|max:255'
        ]);

        // Create or get the specification key
        $specKey = SpecificationKey::firstOrCreate(
            ['name' => $validated['name']],
            ['type' => $validated['type']]
        );

        // Attach to category if not already attached
        CategorySpecification::firstOrCreate([
            'category_id' => $validated['category_id'],
            'specification_key_id' => $specKey->id
        ]);

        // Add specification values if provided
        if (!empty($validated['values'])) {
            foreach ($validated['values'] as $value) {
                $specKey->specificationValues()->firstOrCreate(['value' => $value]);
            }
        }

        return Response::success(message: 'Specification added successfully');
    }

    public function delete($id)
    {
        $specificationKey = SpecificationKey::find($id);

        if (!$specificationKey) {
            return Response::notFound(message: 'Specification Key not found');
        }

        $specificationKey->specificationValues()->delete();
        $specificationKey->delete();

        return Response::success(message: 'Specification deleted successfully');
    }
}
