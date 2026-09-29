<?php

namespace App\Http\Controllers\Dhp\Issuer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dhp\ConfirmIdentityRequest;
use App\Http\Requests\Dhp\RegisterCitizenRequest;
use App\Models\Citizen;
use App\Models\District;
use App\Models\User;
use App\Services\DhpAuditLogger;
use App\Services\DhpIdentifierService;
use App\Services\DhpIdentityConfirmation;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Assisted access: search, identity confirmation, registration.
 * There is no "list all citizens" route; every lookup needs a query.
 * Verifier sees minimal data (search shows age, never full date of birth
 * unless the issuer already knows it for confirmation).
 */
class CitizenController extends Controller
{
    /**
     * Minimal public shape for search results and suggestions.
     */
    public static function summary(Citizen $citizen): array
    {
        return [
            'id' => $citizen->id,
            'passport_id' => $citizen->passport_id,
            'national_id_masked' => $citizen->maskedNationalId(),
            'full_name' => $citizen->full_name,
            'age' => $citizen->date_of_birth?->age,
            'sex' => $citizen->sex,
            'district' => $citizen->district,
            'confirm_url' => route('dhp.issuer.citizens.confirm-form', $citizen),
        ];
    }

    public function search(Request $request): View
    {
        $results = null;
        $error = null;

        if ($request->has('query') || $request->has('date_of_birth')) {
            [$results, $error] = $this->runSearch($request);
        }

        return view('dhp.issuer.citizens.search', [
            'results' => $results,
            'searchError' => $error,
            'query' => (string) $request->input('query', ''),
            'dateOfBirth' => (string) $request->input('date_of_birth', ''),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        [$results, $error] = $this->runSearch($request, 10);

        if ($error !== null) {
            return response()->json(['error' => $error], 422);
        }

        return response()->json(['data' => array_map(
            fn (Citizen $c) => array_diff_key(self::summary($c), ['confirm_url' => true]) + ['confirm_url' => route('dhp.issuer.citizens.confirm-form', $c)],
            $results ?? []
        )]);
    }

    /**
     * @return array{0: array<int, Citizen>|null, 1: string|null}
     */
    private function runSearch(Request $request, int $limit = 25): array
    {
        $query = trim((string) $request->input('query', ''));
        $dob = trim((string) $request->input('date_of_birth', ''));

        if ($query === '' && $dob === '') {
            return [null, null];
        }

        if ($query === '' && $dob !== '') {
            return [null, 'Enter a Passport ID, National ID or name together with the date of birth.'];
        }

        $base = Citizen::query()->select(['id', 'passport_id', 'national_id', 'first_name', 'last_name', 'sex', 'date_of_birth', 'district']);

        // Exact Passport ID match first.
        if ($query !== '' && Citizen::query()->where('passport_id', $query)->exists()) {
            $found = (clone $base)->where('passport_id', $query)->get()->all();

            return [$found, null];
        }

        // National ID exact or prefix match.
        if ($query !== '' && mb_strlen($query) >= 3) {
            $byNational = (clone $base)->where('national_id', strtoupper($query))
                ->orWhere(function ($inner) use ($query) {
                    $inner->where('national_id', 'like', strtoupper($query).'%');
                });

            if ($dob !== '') {
                $byNational->whereDate('date_of_birth', $dob);
            }

            $found = $byNational->limit($limit)->get();

            if ($found->isNotEmpty()) {
                return [$found->all(), null];
            }
        }

        // Controlled full-name search, minimum 3 characters.
        if (mb_strlen($query) < 3) {
            return [null, 'Type at least 3 characters to search by name.'];
        }

        $byName = (clone $base)->where(function ($inner) use ($query) {
            $inner->where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
        });

        if ($dob !== '') {
            $byName->whereDate('date_of_birth', $dob);
        }

        return [$byName->limit($limit)->get()->all(), null];
    }

    public function confirmForm(Citizen $citizen): View
    {
        $this->authorize('view', $citizen);

        return view('dhp.issuer.citizens.confirm', ['citizen' => $citizen]);
    }

    public function confirmIdentity(ConfirmIdentityRequest $request): RedirectResponse
    {
        $citizen = Citizen::query()->findOrFail($request->integer('citizen_id'));
        $this->authorize('view', $citizen);

        $issuer = $request->user();

        if (DhpIdentityConfirmation::tooManyAttempts($issuer, $citizen->id, $request->ip())) {
            return back()->with('error', 'Too many confirmation attempts. Please wait 10 minutes and try again.');
        }

        $matches = DhpIdentityConfirmation::countMatches($citizen, $request->only(
            ['first_name', 'last_name', 'date_of_birth', 'sex', 'district', 'village']
        ));

        if ($matches < DhpIdentityConfirmation::REQUIRED_MATCHES) {
            DhpIdentityConfirmation::hit($issuer, $citizen->id, $request->ip());

            DhpAuditLogger::log(
                user: $issuer,
                action: 'citizen_identity_confirmation_failed',
                entityType: 'citizen',
                entityId: $citizen->id,
                details: ['reason' => 'insufficient_matches'],
                ipAddress: $request->ip(),
            );

            return back()->with('error', 'The details do not match this passport. Check with the citizen and try again.');
        }

        DhpIdentityConfirmation::clear($issuer, $citizen->id, $request->ip());
        DhpIdentityConfirmation::grant($citizen->id);

        DhpAuditLogger::log(
            user: $issuer,
            action: 'citizen_identity_confirmed',
            entityType: 'citizen',
            entityId: $citizen->id,
            details: ['matched_field_count' => $matches],
            ipAddress: $request->ip(),
        );

        return redirect()->route('dhp.issuer.citizens.show', $citizen)->with('success', 'Identity confirmed for the next 15 minutes.');
    }

    public function show(Citizen $citizen): View
    {
        $this->authorize('view', $citizen);
        $citizen->load(['credentials' => fn ($q) => $q->with(['facility', 'vaccinationDetail', 'testDetail'])->latest('issue_date')]);

        return view('dhp.issuer.citizens.show', [
            'citizen' => $citizen,
            'grouped' => $citizen->credentials->groupBy(fn ($c) => $c->effective_status->value),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Citizen::class);

        return view('dhp.issuer.citizens.create', [
            'districts' => District::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(RegisterCitizenRequest $request, DhpIdentifierService $ids): RedirectResponse
    {
        // Exact National ID duplicate: reject without exposing the stored value.
        if ($request->filled('national_id')
            && Citizen::query()->where('national_id', $request->input('national_id'))->exists()) {
            return back()->withInput()->with('error', 'A citizen with this National ID is already registered. Search for the existing passport instead of creating a new one.');
        }

        // Strong demographic duplicate: warn unless the issuer documents otherwise.
        $duplicate = Citizen::query()
            ->whereRaw('LOWER(first_name) = ?', [mb_strtolower(trim($request->input('first_name')))])
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($request->input('last_name')))])
            ->whereDate('date_of_birth', $request->input('date_of_birth'))
            ->where('district', $request->input('district'))
            ->first();

        if ($duplicate && ! $request->boolean('duplicate_override')) {
            return back()->withInput()->with('duplicate', [
                'passport_id' => $duplicate->passport_id,
                'full_name' => $duplicate->full_name,
                'confirm_url' => route('dhp.issuer.citizens.confirm-form', $duplicate),
            ])->with('error', 'A very similar passport already exists. Open it, or tick the override box and explain why a separate record is needed.');
        }

        $portalAccount = $request->boolean('create_account') && $request->filled('email');

        $citizen = DB::transaction(function () use ($request, $ids, $portalAccount) {
            $user = null;

            if ($portalAccount) {
                $user = User::create([
                    'name' => trim($request->input('first_name').' '.$request->input('last_name')),
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone'),
                    'role' => 'citizen',
                    'is_active' => true,
                    'status' => 'active',
                    'must_change_password' => true,
                    // Random secret; activation email is configured in Phase 7.
                    'password' => Hash::make(Str::random(40)),
                ]);
            }

            return Citizen::create([
                'passport_id' => $ids->nextPassportId(),
                'national_id' => $request->input('national_id'),
                'first_name' => trim($request->input('first_name')),
                'last_name' => trim($request->input('last_name')),
                'sex' => $request->input('sex'),
                'date_of_birth' => $request->input('date_of_birth'),
                'district' => $request->input('district'),
                'village' => $request->input('village'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'user_id' => $user?->id,
                'created_by' => $request->user()->id,
            ]);
        });

        DhpAuditLogger::log(
            user: $request->user(),
            action: 'citizen_registered',
            entityType: 'citizen',
            entityId: $citizen->id,
            details: [
                'source' => 'issuer_registration',
                'has_national_id' => $citizen->national_id !== null,
                'has_email' => $citizen->email !== null,
                'has_phone' => $citizen->phone !== null,
                'portal_account_created' => $portalAccount,
            ],
            ipAddress: $request->ip(),
        );

        $message = $portalAccount
            ? 'Citizen registered. The portal account was created; activation notification will be configured later.'
            : 'Citizen registered.';

        return redirect()->route('dhp.issuer.citizens.registration-slip', $citizen)->with('success', $message);
    }

    public function registrationSlip(Citizen $citizen): View
    {
        $this->authorize('view', $citizen);
        $citizen->load('creator');

        return view('dhp.issuer.citizens.registration-slip', ['citizen' => $citizen]);
    }
}
