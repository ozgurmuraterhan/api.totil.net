<?php

namespace PickBazar\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PickBazar\Database\Repositories\ProductRepository;
use PickBazar\Database\Models\Product;
use PickBazar\Http\Requests\ProductCreateRequest;
use PickBazar\Http\Requests\ProductUpdateRequest;

class ProductController extends CoreController
{
    public $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Product[]
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?   $request->limit : 15;
        return $this->repository->with(['type', 'categories', 'variations.attribute'])->paginate($limit);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ProductCreateRequest $request
     * @return mixed
     */
    public function store(ProductCreateRequest $request)
    {
        return $this->repository->storeProduct($request);
    }

    /**
     * Display the specified resource.
     *
     * @param $slug
     * @return JsonResponse
     */
    public function show($slug, Request $request)
    {
        try {
            $limit = isset($request->limit) ? $request->limit : 10;
            $product = $this->repository
                ->with(['type', 'categories', 'variations.attribute.values', 'variation_options'])
                ->findOneByFieldOrFail('slug', $slug);
            $product->related_products = $this->repository->fetchRelated($slug, $limit);
            return $product;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Product not found!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ProductUpdateRequest $request
     * @param int $id
     * @return array
     */
    public function update(ProductUpdateRequest $request, $id)
    {
        return $this->repository->updateProduct($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            return $this->repository->findOrFail($id)->delete();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Product not found!'], 404);
        }
    }

    public function relatedProducts(Request $request)
    {
        $limit = isset($request->limit) ? $request->limit : 10;
        return $this->repository->fetchRelated($request->slug, $limit);
    }
}
