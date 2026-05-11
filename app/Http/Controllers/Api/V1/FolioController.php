<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hotel\Models\Folio;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Services\FolioService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFolioLineRequest;
use App\Http\Requests\StoreFolioRequest;
use App\Http\Resources\FolioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FolioController extends Controller
{
    public function __construct(protected FolioService $folioService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Folio::query()->with('lines');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return FolioResource::collection($query->latest()->paginate());
    }

    public function store(StoreFolioRequest $request): FolioResource
    {
        $reservation = Reservation::query()->findOrFail($request->integer('reservation_id'));
        $folio = $this->folioService->openForReservation($reservation);

        return new FolioResource($folio->load('lines'));
    }

    public function show(Folio $folio): FolioResource
    {
        return new FolioResource($folio->load('lines'));
    }

    public function addCharge(StoreFolioLineRequest $request, Folio $folio): FolioResource
    {
        $this->folioService->addCharge($folio, $request->validated());

        return new FolioResource($folio->fresh()->load('lines'));
    }
}