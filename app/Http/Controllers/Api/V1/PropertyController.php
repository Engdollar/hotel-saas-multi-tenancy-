<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\Property;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PropertyController extends Controller
{
    public function __construct(protected CurrentCompanyContext $companyContext)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return PropertyResource::collection(Property::query()->latest()->paginate());
    }

    public function store(StorePropertyRequest $request): PropertyResource
    {
        $this->ensureCompanyContext();

        $property = Property::query()->create($request->validated());

        return new PropertyResource($property);
    }

    public function show(Property $property): PropertyResource
    {
        return new PropertyResource($property);
    }

    public function update(UpdatePropertyRequest $request, Property $property): PropertyResource
    {
        $property->update($request->validated());

        return new PropertyResource($property->fresh());
    }

    public function destroy(Property $property): \Illuminate\Http\JsonResponse
    {
        $property->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function ensureCompanyContext(): void
    {
        abort_if($this->companyContext->id() === null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Select a company context before creating tenant records.');
    }
}