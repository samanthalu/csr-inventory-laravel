<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // //
        // sleep(5);
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        $suppliers = Supplier::all();
    

        if($suppliers) {
            return response()->json([
                'message' => 'Supplier created successfully!',
                'data' => $suppliers
            ]);
        }
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
        // sleep(4);
        //
        $validatedData = $request->validate([
            'sup_name'              => 'required|string|max:255',
            'sup_address'           => 'required|string|max:255',
            'sup_phone'             => 'required|string|max:20',
            'sup_email'             => 'required|email|max:255|unique:suppliers,sup_email',
            'sup_district'          => 'required|string|max:100',
            'sup_type'              => 'required|string|max:100',
            'sup_tax_id'            => 'nullable|string|max:100',
            'sup_contact_person'    => 'nullable|string|max:255',
            'sup_contact_phone'     => 'nullable|string|max:20',
            'sup_bank_details'      => 'nullable|string|max:500',
            'sup_registration_number' => 'nullable|string|max:100',
        ]);

        // Create and save the supplier
        $supplier = Supplier::create($validatedData);

        // Return response
        return response()->json([
            'message' => 'Supplier created successfully!',
            'supplier' => $supplier
        ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
 
        if (!is_numeric($id)) {
            # code...
            return response()->json(['message' => 'Invalid supplier identifier']);
        }
        
        $supplier = Supplier::findOrFail($id);
        
        return response()->json([
            'message' => 'Successfully fetched supplier',
            'data' => $supplier
        ]);

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
        if (!Gate::allows('edit')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        $supplier = Supplier::findOrFail($id);

        $validatedData = $request->validate([
            'sup_name'              => 'required|string|max:255',
            'sup_address'           => 'required|string|max:255',
            'sup_phone'             => 'required|string|max:20',
            'sup_email'             => ['required','email','max:255',Rule::unique('suppliers', 'sup_email')->ignore($id, 'sup_id'),],
            'sup_district'          => 'required|string|max:100',
            'sup_type'              => 'required|string|max:100',
            'sup_tax_id'            => 'nullable|string|max:100',
            'sup_contact_person'    => 'nullable|string|max:255',
            'sup_contact_phone'     => 'nullable|string|max:20',
            'sup_bank_details'      => 'nullable|string|max:200',
            'sup_registration_number' => 'nullable|string|max:100',
        ]);

        $supplier->update($validatedData);

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'data' => $supplier
        ]);

        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
