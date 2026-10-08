<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AccountOptions;
use App\Support\AccountPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TalentCvController extends Controller
{
    /** Lets a signed-in talent upload or replace their own CV. */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cv' => ['required', 'file', 'mimes:'.AccountOptions::CV_MIMES, 'max:'.AccountOptions::CV_MAX_SIZE],
        ], [
            'cv.required' => 'Please choose your CV file.',
            'cv.mimes' => 'Upload your CV as a PDF, DOC, or DOCX file.',
            'cv.max' => 'Your CV must be 5 MB or smaller.',
        ]);

        /** @var User $user */
        $user = auth('api')->user();
        $profile = $user->candidateProfile;
        abort_unless($profile, 404, 'Talent profile not found.');

        $profile->storeCv($request->file('cv'));

        return response()->json(array_merge(
            ['message' => 'Your CV has been uploaded.'],
            AccountPayload::for($user),
        ));
    }
}
