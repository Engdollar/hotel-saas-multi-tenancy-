<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\Supplier;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public function __construct(protected CurrentCompanyContext $companyContext)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return SupplierResource::collection(Supplier::query()->latest()->paginate());
    }

    public function store(StoreSupplierRequest $request): SupplierResource
    {
        $this->ensureCompanyContext();

        $supplier = Supplier::query()->create($request->validated());

        return new SupplierResource($supplier);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update($request->validated());

        return new SupplierResource($supplier->fresh());
    }

    public function destroy(Supplier $supplier): \Illuminate\Http\JsonResponse
    {
        $supplier->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function ensureCompanyContext(): void
    {
        abort_if($this->companyContext->id() === null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Select a company context before creating tenant records.');
    }
}