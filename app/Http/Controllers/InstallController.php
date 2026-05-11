<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstallApplicationRequest;
use App\Http\Requests\InstallDatabaseTestRequest;
use App\Services\InstallerDatabaseService;
use App\Services\InstallerService;
use App\Services\InstallerRequirementsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstallController extends Controller
{
    public function __construct(
        protected InstallerService $installerService,
        protected InstallerRequirementsService $installerRequirementsService,
        protected InstallerDatabaseService $installerDatabaseService,
    ) {
    }

    public function create(): View|RedirectResponse
    {
        $alreadyInstalled = $this->installerService->isInstalled();

        if ($alreadyInstalled && ! $this->installerService->allowsLocalInstallerAccess()) {
            return redirect()->route('login');
        }

        return view('install.index', [
            'defaults' => $this->installerService->defaults(),
            'alreadyInstalled' => $alreadyInstalled,
            'canReinstall' => $this->installerService->allowsLocalInstallerAccess(),
            'requirementsSummary' => $this->installerRequirementsService->summary(),
        ]);
    }

    public function store(InstallApplicationRequest $request): RedirectResponse
    {
        if ($this->installerService->isInstalled() && ! $this->installerService->allowsLocalInstallerAccess()) {
            return redirect()->route('login');
        }

        $requirementsSummary = $this->installerRequirementsService->summary();

        if (! $requirementsSummary['passes']) {
            throw ValidationException::withMessages([
                'requirements' => 'Server requirements are not satisfied yet. Fix the failed PHP extension or permission checks before continuing.',
            ]);
        }

        $this->installerService->install($request->validated());

        return redirect()->route('login')->with('status', 'Installation complete. You can now sign in as the super admin.');
    }

    public function testDatabase(InstallDatabaseTestRequest $request): JsonResponse
    {
        $result = $this->installerDatabaseService->testMySqlConnection($request->validated());

        return response()->json($result, $result['passes'] ? 200 : 422);
    }
}