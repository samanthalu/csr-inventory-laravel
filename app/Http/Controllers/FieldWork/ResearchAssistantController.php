<?php

namespace App\Http\Controllers\FieldWork;

use App\Http\Controllers\Controller;
use App\Models\ResearchAssistant;
use App\Models\FieldWorkSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResearchAssistantController extends Controller
{
    public function store(Request $request, $sessionId)
    {
        if (!Gate::allows('create_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::findOrFail($sessionId);

        $data = $request->validate([
            'ra_name'      => 'required|string|max:255',
            'ra_phone'     => 'nullable|string|max:50',
            'ra_email'     => 'nullable|email|max:255',
            'ra_id_number' => 'nullable|string|max:100',
            'ra_district'  => 'nullable|string|max:100',
            'ra_notes'     => 'nullable|string',
        ]);

        $data['ra_fw_session_id'] = $session->id;
        $ra = ResearchAssistant::create($data);

        return response()->json(['data' => $ra], 201);
    }

    public function bulkStore(Request $request, $sessionId)
    {
        if (!Gate::allows('create_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::findOrFail($sessionId);

        $request->validate([
            'assistants'               => 'required|array|min:1',
            'assistants.*.ra_name'     => 'required|string|max:255',
            'assistants.*.ra_phone'    => 'nullable|string|max:50',
            'assistants.*.ra_email'    => 'nullable|email|max:255',
            'assistants.*.ra_id_number'=> 'nullable|string|max:100',
            'assistants.*.ra_district' => 'nullable|string|max:100',
            'assistants.*.ra_notes'    => 'nullable|string',
        ]);

        $created = collect($request->assistants)->map(function ($row) use ($session) {
            return ResearchAssistant::create(array_merge($row, ['ra_fw_session_id' => $session->id]));
        });

        return response()->json(['data' => $created, 'count' => $created->count()], 201);
    }

    public function update(Request $request, $sessionId, $id)
    {
        if (!Gate::allows('update_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ra = ResearchAssistant::where('ra_fw_session_id', $sessionId)->findOrFail($id);

        $data = $request->validate([
            'ra_name'      => 'sometimes|string|max:255',
            'ra_phone'     => 'nullable|string|max:50',
            'ra_email'     => 'nullable|email|max:255',
            'ra_id_number' => 'nullable|string|max:100',
            'ra_district'  => 'nullable|string|max:100',
            'ra_notes'     => 'nullable|string',
        ]);

        $ra->update($data);

        return response()->json(['data' => $ra]);
    }

    public function destroy($sessionId, $id)
    {
        if (!Gate::allows('delete_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        ResearchAssistant::where('ra_fw_session_id', $sessionId)->findOrFail($id)->delete();

        return response()->noContent();
    }
}
