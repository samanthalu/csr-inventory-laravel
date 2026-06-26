<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductSelectResource;
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
use App\Services\AuditLogger;

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
    public function getProducts(Request $request)
    {
        if (!Gate::allows('view_products')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        try {
            $this->authorize('viewAny', Product::class);

            // Shared search scope so the listing and the tab counts stay in sync.
            $search = trim((string) $request->query('search', ''));
            $applySearch = function ($q) use ($search) {
                if ($search !== '') {
                    $q->where(function ($w) use ($search) {
                        $w->where('prod_name', 'like', "%{$search}%")
                          ->orWhere('prod_serial_num', 'like', "%{$search}%")
                          ->orWhere('prod_model_number', 'like', "%{$search}%")
                          ->orWhere('prod_tag_number', 'like', "%{$search}%");
                    });
                }
            };

            $query = Product::with(['supplier', 'category'])
                ->withExists(['activeHireItems as is_hired_out']);
            $applySearch($query);

            $paginating = $request->has('page') || $request->has('per_page');

            // Per-category counts for the listing tabs — reflect the active search
            // but NOT the category filter, so every tab shows its own total. Built
            // from a clean query (no eager loads) to keep the GROUP BY valid.
            $categoryCounts = null;
            $allCount       = null;
            if ($paginating) {
                $categoryCounts = Product::query()
                    ->tap($applySearch)
                    ->selectRaw('cat_id, COUNT(*) as aggregate')
                    ->groupBy('cat_id')
                    ->pluck('aggregate', 'cat_id');
                $allCount = (int) $categoryCounts->sum();
            }

            // Server-side category filter (cat_id)
            if ($category = (int) $request->query('category', 0)) {
                $query->where('cat_id', $category);
            }

            // Server-side sort (whitelisted columns only)
            $sortable = [
                'prod_name', 'prod_serial_num', 'prod_model_number',
                'prod_quantity', 'prod_cost', 'prod_condition',
                'prod_current_status', 'created_at',
            ];
            $sortBy  = in_array($request->query('sort_by'), $sortable, true) ? $request->query('sort_by') : 'created_at';
            $sortDir = strtolower((string) $request->query('sort_dir')) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortDir);

            // Paginate when the client asks for it; otherwise return all
            // (kept for callers like the product pickers in hire/maintenance/disposal).
            if ($paginating) {
                $perPage = min(max((int) $request->query('per_page', 25), 1), 200);
                $result  = $query->paginate($perPage);

                return response()->json([
                    'success'         => true,
                    'data'            => $result->items(),
                    'total'           => $result->total(),
                    'per_page'        => $result->perPage(),
                    'current_page'    => $result->currentPage(),
                    'last_page'       => $result->lastPage(),
                    'category_counts' => $categoryCounts,
                    'all_count'       => $allCount,
                    'message'         => 'Products retrieved successfully',
                ]);
            }

            return response()->json([
                'success' => true,
                'data'    => $query->get(),
                'message' => 'Products retrieved successfully',
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
       
        if (!Gate::allows('view_products')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        try {
            $product = Product::with(['accessories', 'files', 'supplier', 'category', 'assignedUser:id,name,email,user_type'])
                ->withExists(['activeHireItems as is_hired_out'])
                ->findOrFail($id);
            $this->authorize('view', Product::class);

            // Surface the expected return date of the current hire, if any.
            if ($product->is_hired_out) {
                $activeItem = $product->activeHireItems()->with('hire:id,hire_return_date')->first();
                $product->setAttribute('hire_return_date', optional($activeItem?->hire)->hire_return_date);
            }

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
        
        if (!Gate::allows('create_products')) {
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
                'order_id' => 'nullable|string|max:100',
                'prod_notes' => 'nullable|string',
                'prod_warranty_expire' => 'nullable|date',
                'prod_condition' => 'nullable|string',
                'prod_current_status' => 'nullable|string',
                'custom_fields' => 'nullable|array',
                'custom_fields.*.key' => 'required_with:custom_fields.*|string|max:100',
                'custom_fields.*.value' => 'nullable|string|max:500',
            ]);

            $validator->stopOnFirstFailure();
            $validated = $validator->validate();
            $validated['user_id'] = Auth::id();

            $product = Product::create($validated);
            $insertId = $product->prod_id;

            AuditLogger::log('product', 'created', "Product '{$product->prod_name}' created", $insertId, null, $product->toArray());

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
        if (!Gate::allows('update_products')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        try {
            // \Log::info($request);
            
            $id = $request->prod_id;
            $product = Product::findOrFail($id);
            $this->authorize('update', Product::class);
            $oldValues = $product->toArray();

            $validator = Validator::make($request->all(), [
                'prod_name' => 'required|string|max:255',
                'prod_desc' => 'required|string',
                'prod_cost' => 'required|numeric|min:0',
                'prod_quantity' => 'required|integer|min:0',
                'prod_serial_num' => "required|string|unique:products,prod_serial_num,{$id},prod_id",
                'prod_tag_number' => 'nullable|string',
                'prod_model_number' => 'nullable|string',
                'prod_batch_number' => 'nullable|string',
                'prod_other_identifier' => 'nullable|string',
                'prod_quantity_measure' => 'nullable|string',
                'prod_purchase_date' => 'nullable|date',
                'cat_id' => 'nullable|integer',
                'sup_id' => 'nullable|integer',
                'order_id' => 'nullable|string|max:100',
                'prod_notes' => 'nullable|string',
                'prod_warranty_expire' => 'nullable|date',
                'prod_condition' => 'nullable|string',
                'prod_current_status' => 'nullable|string',
                'custom_fields' => 'nullable|array',
                'custom_fields.*.key' => 'required_with:custom_fields.*|string|max:100',
                'custom_fields.*.value' => 'nullable|string|max:500',
            ]);

            $validator->stopOnFirstFailure();
            $validated = $validator->validate();
            unset($validated['user_id']);

            $product->update($validated);

            AuditLogger::log('product', 'updated', "Product '{$product->prod_name}' updated", $product->prod_id, $oldValues, $product->fresh()->toArray());

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
        
        if (!Gate::allows('delete_products')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        try {
            $product = Product::findOrFail($id);
            $this->authorize('delete', Product::class);
            $name = $product->prod_name;

            // Soft delete — row stays in DB, deleted_at is set
            $product->delete();

            AuditLogger::log('product', 'deleted', "Product '{$name}' moved to trash", (int) $id);

            return response()->json([
                'success' => true,
                'message' => 'Product moved to trash',
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
                    default => 'Failed to delete product',
                },
            ], $statusCode);
        }
    }

    public function getSelectProducts(Request $request) {
        return ProductSelectResource::collection(Product::all());
    }

    public function trash()
    {
        if (!Gate::allows('delete_products')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }
        $products = Product::onlyTrashed()
            ->with(['supplier:sup_id,sup_name', 'category:cat_id,cat_name'])
            ->orderByDesc('deleted_at')
            ->get();
        return response()->json(['data' => $products]);
    }

    public function restore($id)
    {
        if (!Gate::allows('update_products')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();
        AuditLogger::log('product', 'restored', "Product '{$product->prod_name}' restored from trash", (int) $id);
        return response()->json(['success' => true, 'message' => 'Product restored successfully']);
    }

    public function forceDelete($id)
    {
        if (!Gate::allows('delete_products')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }
        try {
            $product = Product::onlyTrashed()->findOrFail($id);

            // Block permanent deletion if product has business records
            $blocking = [];
            if ($product->hireItems()->exists())       $blocking[] = 'hire records';
            if ($product->maintenanceLogs()->exists())  $blocking[] = 'maintenance records';
            if ($product->disposalRecords()->exists())  $blocking[] = 'disposal records';

            if (!empty($blocking)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot permanently delete: product has ' . implode(', ', $blocking) . '.',
                ], Response::HTTP_CONFLICT);
            }

            $name = $product->prod_name;
            $product->accessories()->delete();
            $product->files()->delete();
            $product->staffProducts()->delete();
            $product->forceDelete();

            AuditLogger::log('product', 'force_deleted', "Product '{$name}' permanently deleted", (int) $id);
            return response()->json(['success' => true, 'message' => 'Product permanently deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to permanently delete product'], 500);
        }
    }
}
