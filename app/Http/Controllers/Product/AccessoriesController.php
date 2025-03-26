<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\ProductAccessories;
use App\Models\ProductFiles;


class AccessoriesController extends Controller
{
    //
    // Store accessories and files
    public function store(Request $request)
    {
        \Log::info($request);
        // Validate input
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:product,prod_id',
            'accessories' => 'array',
            'accessories.*.pa_prod_id' => 'required|exists:product,prod_id',
            'accessories.*.pa_name' => 'required|string|max:255',
            'accessories.*.pa_serial_number' => 'required|string|max:255|unique:product_accessories,pa_serial_number',
            'accessories.*.pa_qty' => 'required|integer|min:1',
            'accessories.*.pa_color' => 'nullable|string|max:50',
            'accessories.*.pa_desc' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:2048|mimes:pdf,doc,docx,xls,xlsx,png,jpeg,jpg',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Store accessories
            $accessories = [];
            foreach ($request->input('accessories') as $accessoryData) {
                $accessory = ProductAccessories::create($accessoryData);
                $accessories[] = $accessory;
            }

            // Store files
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store(
                        'files/product/' . $request->product_id, // Assuming product_id exists in request
                        'public'
                    );
                    


                    ProductFiles::create([
                        'pf_file_name' => $file->getClientOriginalName(),
                        'pf_file_size' => $file->getSize(),
                        'pf_file_path' => $path,
                        'pf_file_type' => $file->getMimeType(),
                        'pf_prod_id'   => $request->product_id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Accessories and files created successfully',
                'data' => $accessories
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Creation failed: ' . $e->getMessage()], 500);
        }
    }

    // Update accessory
    public function update(Request $request, $id)
    {
        $accessory = ProductAccessories::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'pa_name' => 'required|string|max:255',
            'pa_serial_number' => 'required|string|max:255',
            'pa_qty' => 'required|integer|min:1',
            'pa_color' => 'nullable|string|max:50',
            'pa_desc' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $accessory->update($request->all());

        return response()->json(['message' => 'Accessory updated successfully', 'data' => $accessory]);
    }

    // Delete accessory and related files
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $accessory = ProductAccessories::findOrFail($id);
            
            // Delete related files
            // ProductFiles::where('product_id', $accessory->product_id)->each(function($file) {
            //     Storage::disk('public')->delete($file->pf_file_path);
            //     $file->delete();
            // });

            $accessory->delete();

            DB::commit();

            return response()->json(['message' => 'Accessory deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Deletion failed: ' . $e->getMessage()], 500);
        }
    }

    // Get all accessories with files
    public function index($productId)
    {
        $accessories = ProductAccessories::where('pa_prod_id', $productId)->get();
        $files = ProductFiles::where('pf_prod_id', $productId)->get();

        return response()->json([
            'accessories' => $accessories,
            'files' => $files
        ]);
    }

    // Get single accessory with files
    public function show($id)
    {
        $accessory = ProductAccessories::with(['productFiles' => function($query) {
            $query->select('id', 'product_id', 'pf_file_name', 'pf_file_type');
        }])->findOrFail($id);

        return response()->json($accessory);
    }
}
