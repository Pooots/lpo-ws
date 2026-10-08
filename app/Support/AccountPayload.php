<?php

namespace App\Support;

use App\Models\CandidateProfile;
use App\Models\User;

/**
 * The signed-in user's own account payload (used by auth responses and /auth/me).
 */
class AccountPayload
{
    public static function for(User $user): array
    {
        $profile = match ($user->type) {
            User::TYPE_CLIENT => $user->businessProfile()->first(),
            User::TYPE_TALENT => $user->candidateProfile()->first(),
            default => null,
        };

        $profileData = null;

        if ($profile) {
            $profileData = collect($profile->toArray())
                ->except(['user_id', 'updated_at', 'cv_original_name', 'cv_size', 'cv_uploaded_at'])
                ->all();

            if ($profile instanceof CandidateProfile) {
                $profileData['cv'] = $profile->cvSummary();
            }
        }

        return [
            'type' => $user->type,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'profile' => $profileData,
        ];
    }
}
