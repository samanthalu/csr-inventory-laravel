<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use AuthorizesRequests;
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }

    /**
     * Get all products
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProducts()
    {
        // sleep(5);
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }
        
        try {
            $this->authorize('viewAny', Product::class);
            $products = Product::with(['supplier', 'category'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \Illuminate\Auth\Access\AuthorizationException ? 
                Response::HTTP_FORBIDDEN : Response::HTTP_INTERNAL_SERVER_ERROR;
            
            return response()->json([
                'success' => false,
                'message' => $statusCode === Response::HTTP_FORBIDDEN ? 
                    'You do not have permission to view products' : 'Failed to retrieve products',
                'error' => [
                    'type' => get_class($e),
                    'details' => $e->getMessage()
                ]
            ], $statusCode);
        }
    }

    /**
     * Get a specific product by ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductById($id)
    {
        // sleep(3);
       
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        try {
            $product = Product::with(['accessories', 'files', 'supplier', 'category'])->findOrFail($id);
            $this->authorize('view', Product::class);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product retrieved successfully'
            ]);
        } catch (\Exception $e) {
            $statusCode = match(true) {
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => Response::HTTP_NOT_FOUND,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => Response::HTTP_FORBIDDEN,
                default => Response::HTTP_INTERNAL_SERVER_ERROR
            };

            return response()->json([
                'success' => false,
                'message' => match($statusCode) {
                    Response::HTTP_NOT_FOUND => 'Product not found',
                    Response::HTTP_FORBIDDEN => 'You do not have permission to view this product',
                    default => 'Failed to retrieve product'
                },
                'error' => [
                    'type' => get_class($e),
                    'details' => $e->getMessage()
                ]
            ], $statusCode);
        }
    }

    /**
     * Add a new product
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addProduct(Request $request)
    {
        
        if (!Gate::allows('create')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }
        try {
            $this->authorize('create', Product::class);

            $validator = Validator::make($request->all(), [
                'prod_name' => 'required|string|max:255',
                'prod_desc' => 'required|string',
                'prod_cost' => 'required|numeric|min:0',
                'prod_quantity' => 'required|integer|min:0',
                'prod_serial_num' => 'string',
                'prod_tag_number' => 'nullable|string',
                'prod_model_number' => 'nullable|string',
                'prod_batch_number' => 'nullable|string',
                'prod_other_identifier' => 'nullable|string',
                'prod_quantity_measure' => 'nullable|string',
                'prod_purchase_date' => 'nullable|date',
                'cat_id' => 'nullable|integer',
                'sup_id' => 'nullable|integer',
                'order_id' => 'nullable|integer',
                'user_id' => 'nullable|integer',
                'prod_notes' => 'nullable|string',
                'prod_warranty_expire' => 'nullable|date',
                'prod_condition' => 'nullable|string',
                'prod_current_status' => 'nullable|string',
            ]);

            // 'cat_id' => 'nullable|integer|exists:categories,id',
            //     'sup_id' => 'nullable|integer|exists:suppliers,id',
            //     'order_id' => 'nullable|integer|exists:orders,id',
            //     'user_id' => 'nullable|integer|exists:users,id',
            
            $validator->stopOnFirstFailure(); 
            $validated = $validator->validate(); 
            // \Log::info($request);
            // \Log::info($validated);

            $product = Product::create($validated);
            $insertId = $product->prod_id;

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product created successfully',
                'insertId' => $insertId
            ], Response::HTTP_CREATED);
        }catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing product
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProduct(Request $request)
    {
        if (!Gate::allows('edit')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        try {
            // \Log::info($request);
            
            $id = $request->prod_id;
            $product = Product::findOrFail($id);
            $this->authorize('update', Product::class);

            $validator = Validator::make($request->all(), [
                'prod_name' => 'required|string|max:255',
                'prod_desc' => 'required|string',
                'prod_cost' => 'required|numeric|min:0',
                'prod_quantity' => 'required|integer|min:0',
                'prod_serial_num' => 'required|string|unique:product,prod_serial_num',
                'prod_tag_number' => 'nullable|string',
                'prod_model_number' => 'nullable|string',
                'prod_batch_number' => 'nullable|string',
                'prod_other_identifier' => 'nullable|string',
                'prod_quantity_measure' => 'nullable|string',
                'prod_purchase_date' => 'nullable|date',
                'cat_id' => 'nullable|integer',
                'sup_id' => 'nullable|integer',
                'order_id' => 'nullable|integer',
                'user_id' => 'nullable|integer',
                'prod_notes' => 'nullable|string',
                'prod_warranty_expire' => 'nullable|date',
                'prod_condition' => 'nullable|string',
                'prod_current_status' => 'nullable|string',
            ]);
            
            $validator->stopOnFirstFailure();
            $validated = $validator->validate();
            
            $product->update($validated);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product updated successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteProduct($id)
    {
        
        if (!Gate::allows('delete')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        try {
            $product = Product::findOrFail($id);
            $this->authorize('delete', Product::class);
            
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            $statusCode = match(true) {
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => Response::HTTP_NOT_FOUND,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => Response::HTTP_FORBIDDEN,
                default => Response::HTTP_INTERNAL_SERVER_ERROR
            };

            return response()->json([
                'success' => false,
                'message' => match($statusCode) {
                    Response::HTTP_NOT_FOUND => 'Product not found',
                    Response::HTTP_FORBIDDEN => 'You do not have permission to delete products',
                    default => 'Failed to delete product'
                },
                'error' => [
                    'type' => get_class($e),
                    'details' => $e->getMessage()
                ]
            ], $statusCode);
        }
    }
}
