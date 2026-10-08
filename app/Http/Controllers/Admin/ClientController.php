<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveClientRequest;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Support\AccountOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(AccountOptions::CLIENT_STATUSES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $search = trim($validated['search'] ?? '');

        $clients = User::query()
            ->where('type', User::TYPE_CLIENT)
            ->with('businessProfile')
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->whereHas(
                'businessProfile',
                fn ($profile) => $profile->where('status', $status),
            ))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhereHas('businessProfile', fn ($profile) => $profile->where(
                            fn ($fields) => $fields
                                ->where('company_name', 'like', $like)
                                ->orWhere('industry', 'like', $like)
                                ->orWhere('country', 'like', $like)
                                ->orWhere('city', 'like', $like)
                                ->orWhere('account_manager', 'like', $like),
                        ));
                });
            })
            ->latest('id')
            ->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'data' => $clients->getCollection()->map(fn (User $user) => $this->transform($user))->values(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'from' => $clients->firstItem(),
                'to' => $clients->lastItem(),
            ],
            'counts' => $this->statusCounts(),
        ]);
    }

    public function store(SaveClientRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                ...$this->userAttributes($data),
                'type' => User::TYPE_CLIENT,
                'password' => $data['password'],
                'status' => 'active',
            ]);

            $user->businessProfile()->create([
                ...$this->profileAttributes($data),
                'status' => $data['status'],
                'status_updated_at' => now(),
            ]);

            return $user;
        });

        return response()->json([
            'message' => 'Client added.',
            'client' => $this->transform($user->load('businessProfile')),
        ], 201);
    }

    public function show(User $client): JsonResponse
    {
        $this->ensureClient($client);

        return response()->json(['client' => $this->transform($client->load('businessProfile'))]);
    }

    public function update(SaveClientRequest $request, User $client): JsonResponse
    {
        $this->ensureClient($client);
        $data = $request->validated();

        DB::transaction(function () use ($client, $data) {
            $client->update($this->userAttributes($data));

            $profile = $client->businessProfile()->firstOrNew();
            $statusChanged = $profile->status !== $data['status'];
            $profile->fill([
                ...$this->profileAttributes($data),
                'status' => $data['status'],
            ]);

            if ($statusChanged) {
                $profile->status_updated_at = now();
            }

            $profile->save();
        });

        return response()->json([
            'message' => 'Client updated.',
            'client' => $this->transform($client->load('businessProfile')),
            'counts' => $this->statusCounts(),
        ]);
    }

    public function updateStatus(Request $request, User $client): JsonResponse
    {
        $this->ensureClient($client);

        $data = $request->validate([
            'status' => ['required', Rule::in(AccountOptions::CLIENT_STATUSES)],
        ]);

        $profile = $client->businessProfile;
        abort_unless($profile, 404, 'Client profile not found.');

        if ($profile->status !== $data['status']) {
            $profile->forceFill([
                'status' => $data['status'],
                'status_updated_at' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'Status updated.',
            'client' => $this->transform($client->load('businessProfile')),
            'counts' => $this->statusCounts(),
        ]);
    }

    private function ensureClient(User $user): void
    {
        abort_unless($user->type === User::TYPE_CLIENT, 404, 'Client not found.');
    }

    private function userAttributes(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'email' => $data['email'],
            'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
        ];
    }

    private function profileAttributes(array $data): array
    {
        $text = fn (string $key) => filled($data[$key] ?? null) ? trim($data[$key]) : null;

        return [
            'company_name' => trim($data['company_name']),
            'company_website' => $text('company_website'),
            'industry' => $data['industry'],
            'company_size' => $data['company_size'],
            'country' => $text('country'),
            'city' => $text('city'),
            'job_title' => trim($data['job_title']),
            'billing_email' => $text('billing_email'),
            'services' => array_values(array_unique($data['services'])),
            'message' => $text('message'),
            'lead_source' => $data['lead_source'] ?? null,
            'engagement_type' => $data['engagement_type'] ?? null,
            'team_size' => $data['team_size'] ?? null,
            'monthly_value' => $data['monthly_value'] ?? null,
            'currency' => $data['currency'],
            'contract_start' => $data['contract_start'] ?? null,
            'contract_end' => $data['contract_end'] ?? null,
            'account_manager' => $text('account_manager'),
            'notes' => $text('notes'),
        ];
    }

    /** @return array<string, int> */
    private function statusCounts(): array
    {
        $byStatus = BusinessProfile::query()
            ->whereHas('user', fn ($query) => $query->where('type', User::TYPE_CLIENT))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = ['all' => (int) $byStatus->sum()];

        foreach (AccountOptions::CLIENT_STATUSES as $status) {
            $counts[$status] = (int) ($byStatus[$status] ?? 0);
        }

        return $counts;
    }

    private function transform(User $user): array
    {
        $profile = $user->businessProfile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'account_status' => $user->status,
            'created_at' => $user->created_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'company_name' => $profile?->company_name ?? $user->name,
            'company_website' => $profile?->company_website,
            'industry' => $profile?->industry,
            'company_size' => $profile?->company_size,
            'country' => $profile?->country,
            'city' => $profile?->city,
            'job_title' => $profile?->job_title,
            'billing_email' => $profile?->billing_email,
            'services' => $profile?->services ?? [],
            'message' => $profile?->message,
            'lead_source' => $profile?->lead_source,
            'engagement_type' => $profile?->engagement_type,
            'team_size' => $profile?->team_size,
            'monthly_value' => $profile?->monthly_value !== null ? (float) $profile->monthly_value : null,
            'currency' => $profile?->currency ?? 'USD',
            'contract_start' => $profile?->contract_start?->format('Y-m-d'),
            'contract_end' => $profile?->contract_end?->format('Y-m-d'),
            'account_manager' => $profile?->account_manager,
            'notes' => $profile?->notes,
            'status' => $profile?->status ?? BusinessProfile::STATUS_NEW_INQUIRY,
            'status_updated_at' => $profile?->status_updated_at?->toIso8601String(),
        ];
    }
}
