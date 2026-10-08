<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTalentRequest;
use App\Models\CandidateProfile;
use App\Models\User;
use App\Support\AccountOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TalentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(AccountOptions::TALENT_STATUSES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $search = trim($validated['search'] ?? '');

        $talents = User::query()
            ->where('type', User::TYPE_TALENT)
            ->with('candidateProfile')
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->whereHas(
                'candidateProfile',
                fn ($profile) => $profile->where('status', $status),
            ))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhereHas('candidateProfile', fn ($profile) => $profile->where(
                            fn ($fields) => $fields
                                ->where('desired_position', 'like', $like)
                                ->orWhere('location', 'like', $like),
                        ));
                });
            })
            ->latest('id')
            ->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'data' => $talents->getCollection()->map(fn (User $user) => $this->transform($user))->values(),
            'meta' => [
                'current_page' => $talents->currentPage(),
                'last_page' => $talents->lastPage(),
                'per_page' => $talents->perPage(),
                'total' => $talents->total(),
                'from' => $talents->firstItem(),
                'to' => $talents->lastItem(),
            ],
            'counts' => $this->statusCounts(),
        ]);
    }

    public function store(StoreTalentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::query()->create([
                'type' => User::TYPE_TALENT,
                'name' => trim($data['name']),
                'email' => $data['email'],
                'phone' => trim($data['phone']),
                'password' => $data['password'],
                'status' => 'active',
            ]);

            $profile = $user->candidateProfile()->create([
                'location' => trim($data['location']),
                'desired_position' => trim($data['desired_position']),
                'experience_level' => $data['experience_level'],
                'work_setup' => $data['work_setup'],
                'skills' => array_values($data['skills'] ?? []),
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'status' => $data['status'],
                'status_updated_at' => now(),
            ]);

            if ($request->hasFile('cv')) {
                $profile->storeCv($request->file('cv'));
            }

            return $user;
        });

        return response()->json([
            'message' => 'Talent added.',
            'talent' => $this->transform($user->load('candidateProfile')),
        ], 201);
    }

    public function show(User $talent): JsonResponse
    {
        $this->ensureTalent($talent);

        return response()->json(['talent' => $this->transform($talent->load('candidateProfile'))]);
    }

    public function updateStatus(Request $request, User $talent): JsonResponse
    {
        $this->ensureTalent($talent);

        $data = $request->validate([
            'status' => ['required', Rule::in(AccountOptions::TALENT_STATUSES)],
        ]);

        $profile = $talent->candidateProfile;
        abort_unless($profile, 404, 'Talent profile not found.');

        if ($profile->status !== $data['status']) {
            $profile->forceFill([
                'status' => $data['status'],
                'status_updated_at' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'Status updated.',
            'talent' => $this->transform($talent->load('candidateProfile')),
            'counts' => $this->statusCounts(),
        ]);
    }

    public function uploadCv(Request $request, User $talent): JsonResponse
    {
        $this->ensureTalent($talent);

        $request->validate([
            'cv' => ['required', 'file', 'mimes:'.AccountOptions::CV_MIMES, 'max:'.AccountOptions::CV_MAX_SIZE],
        ], [
            'cv.required' => 'Please choose a CV file.',
            'cv.mimes' => 'Upload the CV as a PDF, DOC, or DOCX file.',
            'cv.max' => 'The CV must be 5 MB or smaller.',
        ]);

        $profile = $talent->candidateProfile;
        abort_unless($profile, 404, 'Talent profile not found.');

        $profile->storeCv($request->file('cv'));

        return response()->json([
            'message' => 'CV uploaded.',
            'talent' => $this->transform($talent->load('candidateProfile')),
        ]);
    }

    public function downloadCv(User $talent): StreamedResponse|JsonResponse
    {
        $this->ensureTalent($talent);

        $profile = $talent->candidateProfile;

        if (! $profile || ! $profile->hasCv()) {
            return response()->json(['message' => 'This talent has not uploaded a CV yet.'], 404);
        }

        return Storage::disk(CandidateProfile::CV_DISK)->download(
            $profile->cv_path,
            $profile->cv_original_name ?: 'cv',
        );
    }

    private function ensureTalent(User $user): void
    {
        abort_unless($user->type === User::TYPE_TALENT, 404, 'Talent not found.');
    }

    /** @return array<string, int> */
    private function statusCounts(): array
    {
        $byStatus = CandidateProfile::query()
            ->whereHas('user', fn ($query) => $query->where('type', User::TYPE_TALENT))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['all' => (int) $byStatus->sum()];

        foreach (AccountOptions::TALENT_STATUSES as $status) {
            $counts[$status] = (int) ($byStatus[$status] ?? 0);
        }

        return $counts;
    }

    private function transform(User $user): array
    {
        $profile = $user->candidateProfile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'account_status' => $user->status,
            'created_at' => $user->created_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'location' => $profile?->location,
            'desired_position' => $profile?->desired_position,
            'experience_level' => $profile?->experience_level,
            'work_setup' => $profile?->work_setup,
            'skills' => $profile?->skills ?? [],
            'linkedin_url' => $profile?->linkedin_url,
            'status' => $profile?->status ?? CandidateProfile::STATUS_FOR_ASSESSMENT,
            'status_updated_at' => $profile?->status_updated_at?->toIso8601String(),
            'cv' => $profile?->cvSummary(),
        ];
    }
}
