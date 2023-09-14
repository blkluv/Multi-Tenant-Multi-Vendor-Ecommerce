<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreRequest;
use App\Http\Requests\Products\UpdateRequest;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return handleResponse([
                'status' => 200,
                'errors' => null,
                'message' => 'products returned successfully',
                'result' => 'success',
                'data' => Product::with('images')->paginate(10),
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return $th->getMessage();
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        try {

            DB::beginTransaction();
            $product = Product::create([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'description' => $request->description,
                'price' => $request->price,
                'qty' => $request->qty
            ]);
            $fileName = uploadFile($request->file('image'));
            Image::create([
                'name' => $fileName,
                'product_id' => $product->id
            ]);

            foreach ($request->sizeIds as $key => $sizeId) {
                $product->sizes()->attach([
                    'size_id' => $sizeId
                ]);
            }
            DB::commit();
            return handleResponse([
                'status' => Response::HTTP_OK,
                'message' => 'product created successfully',
                'errors' => null,
                'result' => 'success',
                'data' => $product->where('id' , $product->id)->with(['images' , 'sizes'])->first()
            ]);



        } catch (\Throwable $th) {
            DB::rollBack();
            // throw new HttpResponseException(handleResponse([]));
            return ['status' => Response::HTTP_INTERNAL_SERVER_ERROR , 'message' => $th->getMessage(), 'line' => $th->getLine()];
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $isFavourite = false;
        try {

            $user = auth()->user();
            $userFavourites = $user->favouriteProducts;

            foreach ($userFavourites as $key => $userFavourite) {
                # code...
                if ($userFavourite->id == $product->id) {
                    # code...
                    $isFavourite = true;
                }
            }
            //code...
            $data = $product->with(['reviews' , 'images' , 'sizes' , 'offer'])->where('id' , $product->id)->first();
            $data->isFavourite = $isFavourite;
            return handleResponse([
                'status' => Response::HTTP_OK,
                'message' => 'product returned successfully',
                'errors' => null,
                'result' => 'success',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return ['status' => Response::HTTP_INTERNAL_SERVER_ERROR,'message' => $th->getMessage(), 'line' => $th->getLine()];
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Product $product)
    {
        try {

            DB::beginTransaction();
            $product->update([
                'name' => $request->name,
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'description' => $request->description,
                'price' => $request->price,
                'qty' => $request->qty

            ]);

            dd($product->images);
            // uploadFile('');

            DB::commit();
            return handleResponse([
                'status' => Response::HTTP_OK,
                'message' => 'product created successfully',
                'errors' => null,
                'result' => 'success',
                'data' => $product
            ]);



        } catch (\Throwable $th) {
            DB::rollBack();
            return ['status' => Response::HTTP_INTERNAL_SERVER_ERROR , 'message' => $th->getMessage(), 'line' => $th->getLine()];
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {

            // delete images first

            // delete post
            $product->delete();
            return handleResponse([
                'status' => 200,
                'message' => 'category deleted successfully',
                'errors' => null,
                'result' => 'success',
                'data' => $product
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['status' => Response::HTTP_INTERNAL_SERVER_ERROR , 'message' => $th->getMessage(), 'line' => $th->getLine()];
        }
    }
}