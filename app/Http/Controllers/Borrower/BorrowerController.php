<?php

namespace App\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\StaffProduct;
use App\Models\Staff;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BorrowerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        // $borrowers = Borrower::with(['borrowedDevices.product'])->get();
        // return response()->json($borrowers);
        $borrowers = Borrower::with(['staff', 'borrowedDevices.product'])->get()->map(function ($borrower) {
            return [
                'borrower_id' => $borrower->pb_id,
                'purpose' => $borrower->pb_purpose,
                'borrowed_from' => $borrower->pb_date_from,
                'borrowed_to' => $borrower->pb_date_to,
                'status' => $borrower->pb_status,
                'staff' => [
                    'id' => $borrower->staff?->staff_id,
                    'name' => $borrower->staff?->staff_first_name. ' ' .$borrower->staff?->staff_last_name,
                    'email' => $borrower->staff?->staff_email,
                ],
                'devices' => $borrower->borrowedDevices->map(function ($record) {
                    return [
                        'device_id' => $record->sp_prod_id,
                        'device_name' => optional($record->product)->prod_name,
                        'device_tag' => $record->product->prod_tag_number
                    ];
                }),
            ];
        });
        
        return response()->json($borrowers);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        // \Log::info("message", $request->all());
        // Validate the request data
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,staff_id',
            'pb_purpose' => 'required|string|max:255',
            'pb_date_from' => 'required|date',
            'pb_date_to' => 'required|date|after_or_equal:pb_date_from',
            'pb_with_accessories' => 'required|in:yes,no',
            'pb_status' => 'required|in:not-returned,returned',
            'products' => 'required|array|min:1',
            'products.*.prod_id' => 'required|exists:products,prod_id',
        ]);

        try {
            // Start a transaction to ensure data consistency
            DB::beginTransaction();

            // Create the borrower record
            $borrower = Borrower::create([
                'staff_id' => $validated['staff_id'],
                'pb_purpose' => $validated['pb_purpose'],
                'pb_date_from' => Carbon::parse($validated['pb_date_from'])->format('Y-m-d'),
                'pb_date_to' => Carbon::parse($validated['pb_date_to'])->format('Y-m-d'),
                'pb_with_accessories' => $validated['pb_with_accessories'],
                'pb_status' => $validated['pb_status'],
            ]);

            // Prepare staff_product entries
            $staffProductData = array_map(function ($product) use ($borrower, $validated) {
                return [
                    'sp_pb_id' => $borrower->pb_id,
                    'sp_staff_id' => $validated['staff_id'],
                    'sp_prod_id' => $product['prod_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $validated['products']);

            // Insert staff_product records
            StaffProduct::insert($staffProductData);

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Borrower and associated products created successfully',
                'borrower' => $borrower,
            ], 201);

        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create borrower',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getBorrowerById($id)
    {
        $borrower = Borrower::with(['staff', 'borrowedDevices.product'])->find($id);
        if (!$borrower) {
            return response()->json(['message' => 'Borrower not found'], 404);
        }

        return response()->json([
            'borrower_id' => $borrower->pb_id,
            'purpose' => $borrower->pb_purpose,
            'borrowed_from' => $borrower->pb_date_from,
            'borrowed_to' => $borrower->pb_date_to,
            'status' => $borrower->pb_status,
            'staff' => [
                'id' => $borrower->staff?->staff_id,
                'name' => $borrower->staff?->staff_first_name. ' ' .$borrower->staff?->staff_last_name,
                'email' => $borrower->staff?->staff_email,
            ],
            'devices' => $borrower->borrowedDevices->map(function ($record) {
                return [
                    'device_id' => $record->sp_prod_id,
                    'device_name' => optional($record->product)->prod_name,
                    'device_tag' => $record->product->prod_tag_number
                ];
            }),
        ]);
    }
    public function getBorrowerByStaffId($id)
    {
        $borrowers = Borrower::with(['staff', 'borrowedDevices.product'])
            ->where('pb_staff_id', $id)
            ->get()
            ->map(function ($borrower) {
                return [
                    'borrower_id' => $borrower->pb_id,
                    'purpose' => $borrower->pb_purpose,
                    'borrowed_from' => $borrower->pb_date_from,
                    'borrowed_to' => $borrower->pb_date_to,
                    'status' => $borrower->pb_status,
                    'staff' => [
                        'id' => $borrower->staff?->staff_id,
                        'name' => $borrower->staff?->staff_first_name. ' ' .$borrower->staff?->staff_last_name,
                        'email' => $borrower->staff?->staff_email,
                    ],
                    'devices' => $borrower->borrowedDevices->map(function ($record) {
                        return [
                            'device_id' => $record->sp_prod_id,
                            'device_name' => optional($record->product)->prod_name,
                            'device_tag' => $record->product->prod_tag_number
                        ];
                    }),
                ];
            });

        return response()->json($borrowers);
    }
    public function getBorrowerByStatus($status)
    {
        $borrowers = Borrower::with(['staff', 'borrowedDevices.product'])
            ->where('pb_status', $status)
            ->get()
            ->map(function ($borrower) {
                return [
                    'borrower_id' => $borrower->pb_id,
                    'purpose' => $borrower->pb_purpose,
                    'borrowed_from' => $borrower->pb_date_from,
                    'borrowed_to' => $borrower->pb_date_to,
                    'status' => $borrower->pb_status,
                    'staff' => [
                        'id' => $borrower->staff?->staff_id,
                        'name' => $borrower->staff?->staff_first_name. ' ' .$borrower->staff?->staff_last_name,
                        'email' => $borrower->staff?->staff_email,
                    ],
                    'devices' => $borrower->borrowedDevices->map(function ($record) {
                        return [
                            'device_id' => $record->sp_prod_id,
                            'device_name' => optional($record->product)->prod_name,
                            'device_tag' => $record->product->prod_tag_number
                        ];
                    }),
                ];
            });

        return response()->json($borrowers);
    } 
    public function getBorrowerByDate($date)
    {
        $borrowers = Borrower::with(['staff', 'borrowedDevices.product'])
            ->where('pb_date_from', '<=', $date)
            ->where('pb_date_to', '>=', $date)
            ->get()
            ->map(function ($borrower) {
                return [
                    'borrower_id' => $borrower->pb_id,
                    'purpose' => $borrower->pb_purpose,
                    'borrowed_from' => $borrower->pb_date_from,
                    'borrowed_to' => $borrower->pb_date_to,
                    'status' => $borrower->pb_status,
                    'staff' => [
                        'id' => $borrower->staff?->staff_id,
                        'name' => $borrower->staff?->staff_first_name. ' ' .$borrower->staff?->staff_last_name,
                        'email' => $borrower->staff?->staff_email,
                    ],
                    'devices' => $borrower->borrowedDevices->map(function ($record) {
                        return [
                            'device_id' => $record->sp_prod_id,
                            'device_name' => optional($record->product)->prod_name,
                            'device_tag' => $record->product->prod_tag_number
                        ];
                    }),
                ];
            });

        return response()->json($borrowers);
    }

}
