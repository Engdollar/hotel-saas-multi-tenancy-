<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\GuestProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuestProfileRequest;
use App\Http\Requests\UpdateGuestProfileRequest;
use App\Http\Resources\GuestProfileResource;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class GuestProfileController extends Controller
{
    public function __construct(protected CurrentCompanyContext $companyContext)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = GuestProfile::query();

        if ($request->filled('query')) {
            $term = '%'.$request->string('query').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('passport_number', 'like', $term);
            });
        }

        return GuestProfileResource::collection($query->latest()->paginate());
    }

    public function store(StoreGuestProfileRequest $request): GuestProfileResource
    {
        $this->ensureCompanyContext();

        $guest = GuestProfile::query()->create($request->validated());

        return new GuestProfileResource($guest->load('identityDocuments.extractionRequests'));
    }

    public function show(GuestProfile $guest): GuestProfileResource
    {
        return new GuestProfileResource($guest->load('identityDocuments.extractionRequests'));
    }

    public function update(UpdateGuestProfileRequest $request, GuestProfile $guest): GuestProfileResource
    {
        $guest->update($request->validated());

        return new GuestProfileResource($guest->fresh()->load('identityDocuments.extractionRequests'));
    }

    public function destroy(GuestProfile $guest): \Illuminate\Http\JsonResponse
    {
        $guest->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function ensureCompanyContext(): void
    {
        abort_if($this->companyContext->id() === null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Select a company context before creating tenant records.');
    }
}