<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LearnerController extends Controller
{
    // List and search learners (administrator route).
    public function index(Request $request)
    {
        $learners = Learner::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('learner_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return response()->json($learners);
    }

    // Submit a new enrollment (authenticated learner).
    public function store(Request $request)
{
    // Check whether this account already has an application.
    $existingApplication = Learner::where(
        'user_id',
        $request->user()->id
    )->first();

    if ($existingApplication) {
        return response()->json([
            'message' => 'You have already submitted an enrollment application.',
            'data' => [
                'learner_number' => $existingApplication->learner_number,
                'enrollment_status' => $existingApplication->enrollment_status,
            ],
        ], 409);
    }

    $validated = $request->validate([
            'learner_number' => [
                'required', 'string', 'max:50',
                'unique:learners,learner_number',
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:255',
                'unique:learners,email',
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'course' => ['required', 'string', 'max:255'],
        ]);

        // Every new application starts as pending.
	        // Link this application to the authenticated account.
$validated['user_id'] = $request->user()->id;

// Every new application starts as pending.
$validated['enrollment_status'] = 'pending';

$learner = Learner::create($validated);
        return response()->json([
            'message' => 'Learner enrolled successfully.',
            'data' => $learner,
        ], 201);
    }

    // View one learner (administrator route).
    public function show(Learner $learner)
    {
        return response()->json(['data' => $learner]);
    }

    // Update an existing learner (administrator route).
    public function update(Request $request, Learner $learner)
    {
        $validated = $request->validate([
            'learner_number' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('learners', 'learner_number')
                    ->ignore($learner->id),
            ],
            'first_name' => [
                'sometimes', 'required', 'string', 'max:100',
            ],
            'last_name' => [
                'sometimes', 'required', 'string', 'max:100',
            ],
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('learners', 'email')->ignore($learner->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'course' => ['sometimes', 'required', 'string', 'max:255'],
            'enrollment_status' => [
                'sometimes', Rule::in(['pending', 'approved', 'rejected']),
            ],
        ]);

        // Update the existing database record.
        $learner->update($validated);

        return response()->json([
            'message' => 'Learner updated successfully.',
            'data' => $learner->fresh(),
        ]);
    }

    // Delete a learner (administrator route).
    public function destroy(Learner $learner)
    {
        $learner->delete();

        return response()->json([
            'message' => 'Learner deleted successfully.',
        ]);
    }
// View the logged-in learner's own application.
public function myApplication(Request $request)
{
    $application = Learner::where(
        'user_id',
        $request->user()->id
    )->first();

    if (!$application) {
        return response()->json([
            'success' => true,
            'message' => 'You have not submitted an enrollment application yet.',
            'data' => null,
        ]);
    }

    return response()->json([
        'success' => true,
        'data' => $application,
    ]);
}
}
