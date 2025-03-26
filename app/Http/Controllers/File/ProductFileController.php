<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\ProductAccessories;
use App\Models\ProductFiles;
use App\Models\Product;

class ProductFileController extends Controller {
    // Get all files for a product
    public function index(Product $product)
    {
        return response()->json($product->files);
    }

    // Store new file
    public function store(Request $request, Product $product)
    {
        \Log::info('ReQ ' . $request);
        \Log::info('Product ' . $product);
   
        $request->validate([
            'file' => 'required|file|max:5120', // 5MB max
        ]);

        $file = $request->file('file');
        $path = $file->store("files/product/{$product->prod_id}", 'public');
        // $path = $file->store(
        //     'files/product/' . $request->product_id, // Assuming product_id exists in request
        //     'public'
        // );

        $savedFile = ProductFiles::create([
            'pf_file_name' => $file->getClientOriginalName(),
            'pf_file_path' => $path,
            'pf_file_size' => $file->getSize(),
            'pf_file_type' => $file->getMimeType(),
            'pf_prod_id' => $product->prod_id,
        ]);

        return response()->json($savedFile, 201);
    }

    // Delete a file
    public function destroy($id)
    {
        $prodFile = ProductFiles::findOrFail($id);
        if($prodFile) {
            Storage::disk('public')->delete(
            str_replace('/storage/', '', $prodFile->pf_file_path)
        );
        }
        
        $prodFile->delete();
        
        return response()->noContent();
    }
}