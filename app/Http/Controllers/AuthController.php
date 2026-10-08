<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterBusinessRequest;
use App\Http\Requests\Auth\RegisterCandidateRequest;
use App\Models\BusinessProfile;
use App\Models\CandidateProfile;
use App\Models\User;
use App\Support\AccountPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registerCandidate(RegisterCandidateRequest $request): JsonResponse
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
                'last_login_at' => now(),
            ]);

            $profile = $user->candidateProfile()->create([
                'location' => trim($data['location']),
                'desired_position' => trim($data['desired_position']),
                'experience_level' => $data['experience_level'],
                'work_setup' => $data['work_setup'],
                'skills' => array_values($data['skills'] ?? []),
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'status' => CandidateProfile::STATUS_FOR_ASSESSMENT,
                'status_updated_at' => now(),
            ]);

            $profile->storeCv($request->file('cv'));

            return $user;
        });

        return $this->sessionResponse($user, 'Your candidate profile has been created.', 201);
    }

    public function registerBusiness(RegisterBusinessRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'type' => User::TYPE_CLIENT,
                'name' => trim($data['name']),
                'email' => $data['email'],
                'phone' => isset($data['phone']) ? trim($data['phone']) : null,
                'password' => $data['password'],
                'status' => 'active',
                'last_login_at' => now(),
            ]);

            $user->businessProfile()->create([
                'company_name' => trim($data['company_name']),
                'company_website' => $data['company_website'] ?? null,
                'industry' => $data['industry'],
                'company_size' => $data['company_size'],
                'job_title' => trim($data['job_title']),
                'services' => array_values(array_unique($data['services'])),
                'message' => $data['message'] ?? null,
                'lead_source' => 'website',
                'status' => BusinessProfile::STATUS_NEW_INQUIRY,
                'status_updated_at' => now(),
            ]);

            return $user;
        });

        return $this->sessionResponse($user, 'Your business account has been created.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()->where('email', $credentials['email'])->first();

        $invalid = response()->json(['message' => 'Invalid email or password.'], 401);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $invalid;
        }

        if ($user->type !== $credentials['type']) {
            // Never reveal that an email belongs to an admin, or point admins at public portals.
            if ($user->isAdmin() || $credentials['type'] === User::TYPE_ADMIN) {
                return $invalid;
            }

            return response()->json([
                'message' => $user->type === User::TYPE_CLIENT
                    ? 'This email is registered as a business account. Please sign in through the business portal.'
                    : 'This email is registered as a job seeker account. Please sign in through the careers portal.',
                'error' => 'type_mismatch',
                'type' => $user->type,
            ], 409);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'This account is not active. Please contact LPO support.',
            ], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->sessionResponse($user, 'Signed in successfully.');
    }

    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        return response()->json(AccountPayload::for($user));
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Signed out.']);
    }

    private function sessionResponse(User $user, string $message, int $status = 200): JsonResponse
    {
        $token = auth('api')->login($user);

        return response()->json(array_merge([
            'message' => $message,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], AccountPayload::for($user)), $status);
    }
}
