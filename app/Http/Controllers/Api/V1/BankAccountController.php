<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\BankAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Http\Resources\BankAccountResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BankAccountController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = BankAccount::query()->with('ledgerAccount');

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return BankAccountResource::collection($query->latest()->paginate());
    }

    public function store(StoreBankAccountRequest $request): BankAccountResource
    {
        $account = BankAccount::query()->create($request->validated());

        return new BankAccountResource($account->fresh('ledgerAccount'));
    }

    public function show(BankAccount $bankAccount): BankAccountResource
    {
        return new BankAccountResource($bankAccount->load('ledgerAccount'));
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): BankAccountResource
    {
        $bankAccount->update($request->validated());

        return new BankAccountResource($bankAccount->fresh('ledgerAccount'));
    }

    public function destroy(BankAccount $bankAccount): \Illuminate\Http\JsonResponse
    {
        $bankAccount->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}