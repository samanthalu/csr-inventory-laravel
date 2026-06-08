<?php

namespace App\Http\Controllers\FieldWork;

use App\Http\Controllers\Controller;
use App\Models\FieldWorkSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FieldWorkSessionController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $sessions = FieldWorkSession::with(['createdBy:id,name', 'hire:id,hire_purpose'])
            ->withCount(['assistants', 'assignments'])
            ->withCount(['assignments as unreturned_count' => fn($q) => $q->whereNull('raa_date_returned')])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($s) => $this->format($s));

        return response()->json(['data' => $sessions]);
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'fw_title'       => 'required|string|max:255',
            'fw_description' => 'nullable|string',
            'fw_location'    => 'nullable|string|max:255',
            'fw_start_date'  => 'required|date',
            'fw_end_date'    => 'nullable|date|after_or_equal:fw_start_date',
            'fw_hire_id'     => 'nullable|exists:hires,id',
        ]);

        $data['fw_created_by'] = $request->user()->id;
        $session = FieldWorkSession::create($data);

        return response()->json(['data' => $this->format($session->fresh(['createdBy', 'hire']))], 201);
    }

    public function show($id)
    {
        if (!Gate::allows('view_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::with([
            'createdBy:id,name',
            'hire:id,hire_purpose,hire_date',
            'assistants.assignments.product:prod_id,prod_name,prod_tag_number',
        ])->findOrFail($id);

        return response()->json(['data' => $this->formatDetail($session)]);
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('update_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::findOrFail($id);

        $data = $request->validate([
            'fw_title'       => 'sometimes|string|max:255',
            'fw_description' => 'nullable|string',
            'fw_location'    => 'nullable|string|max:255',
            'fw_start_date'  => 'sometimes|date',
            'fw_end_date'    => 'nullable|date',
            'fw_status'      => 'sometimes|in:active,completed,cancelled',
            'fw_hire_id'     => 'nullable|exists:hires,id',
        ]);

        $session->update($data);

        return response()->json(['data' => $this->format($session->fresh(['createdBy', 'hire']))]);
    }

    public function destroy($id)
    {
        if (!Gate::allows('delete_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        FieldWorkSession::findOrFail($id)->delete();

        return response()->noContent();
    }

    private function format(FieldWorkSession $s): array
    {
        return [
            'id'                => $s->id,
            'fw_title'          => $s->fw_title,
            'fw_description'    => $s->fw_description,
            'fw_location'       => $s->fw_location,
            'fw_start_date'     => $s->fw_start_date,
            'fw_end_date'       => $s->fw_end_date,
            'fw_status'         => $s->fw_status,
            'fw_hire_id'        => $s->fw_hire_id,
            'hire'              => $s->hire ? ['id' => $s->hire->id, 'hire_purpose' => $s->hire->hire_purpose] : null,
            'created_by'        => $s->createdBy?->name,
            'assistants_count'  => $s->assistants_count ?? 0,
            'assignments_count' => $s->assignments_count ?? 0,
            'unreturned_count'  => $s->unreturned_count ?? 0,
            'created_at'        => $s->created_at,
        ];
    }

    private function formatDetail(FieldWorkSession $s): array
    {
        return array_merge($this->format($s), [
            'assistants' => $s->assistants->map(fn($ra) => [
                'id'           => $ra->id,
                'ra_name'      => $ra->ra_name,
                'ra_phone'     => $ra->ra_phone,
                'ra_email'     => $ra->ra_email,
                'ra_id_number' => $ra->ra_id_number,
                'ra_district'  => $ra->ra_district,
                'ra_notes'     => $ra->ra_notes,
                'assignments'  => $ra->assignments->map(fn($a) => [
                    'id'                => $a->id,
                    'raa_product_id'    => $a->raa_product_id,
                    'product'           => $a->product ? [
                        'prod_id'         => $a->product->prod_id,
                        'prod_name'       => $a->product->prod_name,
                        'prod_tag_number' => $a->product->prod_tag_number,
                    ] : null,
                    'raa_date_out'        => $a->raa_date_out,
                    'raa_expected_return' => $a->raa_expected_return,
                    'raa_date_returned'   => $a->raa_date_returned,
                    'raa_condition_out'   => $a->raa_condition_out,
                    'raa_condition_in'    => $a->raa_condition_in,
                    'raa_notes'           => $a->raa_notes,
                ])->values(),
            ])->values(),
        ]);
    }
}
