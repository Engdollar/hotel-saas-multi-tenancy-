<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyOnboardingService;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected CompanyOnboardingService $companyOnboardingService,
        protected CurrentCompanyContext $companyContext,
    ) {
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if ($this->companyContext->id() !== null) {
            throw ValidationException::withMessages([
                'email' => 'Company signup is only available from the main domain. Sign in through your company workspace instead.',
            ]);
        }

        $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['nullable', 'alpha_dash', 'max:63'],
            'custom_domain' => ['nullable', 'string', 'max:255', Rule::unique('companies', 'domain')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $result = $this->companyOnboardingService->registerCompanyWithAdmin($request->only([
            'company_name',
            'subdomain',
            'custom_domain',
            'name',
            'email',
            'password',
        ]));

        /** @var User $user */
        $user = $result['user'];

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('tenant.access-status');
    }
}
