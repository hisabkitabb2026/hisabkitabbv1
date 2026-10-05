<?php

declare(strict_types=1);

namespace Modules\AccessRequest\Http\Controllers;

use App\Domains\Accounts\Models\Company;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\AccessRequest\Mail\AccessRequestMail;

class AccessRequestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $company = Company::query()
            ->whereKey((int) $request->header('company'))
            ->with('owner')
            ->first();

        $owner = $company?->owner;

        if ($owner === null || $owner->email === null || $owner->email === '') {
            return response()->json([
                'error' => 'This company has no owner to receive the request.',
            ], 422);
        }

        Mail::to($owner->email)->send(new AccessRequestMail(
            (string) $request->user()?->name,
            (string) $company->name,
            $validated['subject'],
            $validated['message'],
        ));

        return response()->json(['success' => true]);
    }
}
