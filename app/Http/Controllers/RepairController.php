<?php

namespace App\Http\Controllers;

use App\Models\RepairJob;
use App\Services\Json\JsonRepairer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RepairController extends Controller
{
    public function __invoke(Request $request, JsonRepairer $repairer): JsonResponse
    {
        $validated = $request->validate([
            'raw_text' => ['required', 'string', 'max:20000'],
        ]);

        try {
            $result = $repairer->repair($validated['raw_text']);
            $job = RepairJob::create([
                'raw_text' => $validated['raw_text'],
                'status' => $result['valid'] ? 'valid' : 'invalid',
            ]);
            $job->repairedJson()->create([
                'json' => $result['json'],
                'valid' => $result['valid'],
                'errors' => $result['errors'],
            ]);

            return response()->json($result + ['job_id' => $job->id]);
        } catch (Throwable) {
            return response()->json([
                'job_id' => null,
                'json' => null,
                'valid' => false,
                'errors' => ['Repair could not be completed. Please try again.'],
                'repair_source' => 'none',
                'llm_used' => false,
                'changes' => [],
            ]);
        }
    }
}
