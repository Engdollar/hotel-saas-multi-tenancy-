<?php

namespace App\Domain\Hotel\Services;

use App\Domain\Hotel\Models\GuestDocumentExtractionRequest;
use App\Domain\Hotel\Models\Reservation;
use App\Domain\Hotel\Models\Room;
use App\Domain\Hotel\Models\RoomMove;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ReservationOperationsService
{
    public function __construct(
        protected ReservationConflictService $reservationConflictService,
        protected FolioService $folioService,
        protected HousekeepingService $housekeepingService,
    ) {
    }

    public function submitPreArrivalRegistration(Reservation $reservation, User $actor, array $attributes = []): Reservation
    {
        if (! in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PENDING], true)) {
            throw new InvalidArgumentException('Pre-arrival registration is only available for pending or confirmed reservations.');
        }

        return DB::transaction(function () use ($reservation, $actor, $attributes) {
            $guest = $reservation->guestProfile()->firstOrFail();
            $guestUpdates = $this->extractGuestProfileUpdates($attributes['guest'] ?? []);

            if ($guestUpdates !== []) {
                $guest->update($guestUpdates);
            }

            $signaturePath = $reservation->check_in_signature_path;

            if (($signatureFile = Arr::get($attributes, 'signature_file')) instanceof UploadedFile) {
                $signaturePath = $signatureFile->store($this->documentDirectory($reservation, 'signatures'), 'public');
            }

            $reservation->update([
                'check_in_signature_name' => $attributes['signature_name'] ?? $reservation->check_in_signature_name,
                'check_in_signature_path' => $signaturePath,
                'signed_registration_at' => Arr::exists($attributes, 'signature_name') || Arr::exists($attributes, 'signature_file')
                    ? now()
                    : $reservation->signed_registration_at,
                'pre_arrival_status' => Reservation::PRE_ARRIVAL_STATUS_COMPLETED,
                'pre_arrival_submitted_at' => $reservation->pre_arrival_submitted_at ?? now(),
                'pre_arrival_completed_at' => now(),
                'expected_arrival_time' => $attributes['expected_arrival_time'] ?? $reservation->expected_arrival_time,
                'registration_channel' => $attributes['registration_channel'] ?? 'self_service',
                'emergency_contact_name' => $attributes['emergency_contact_name'] ?? $reservation->emergency_contact_name,
                'emergency_contact_phone' => $attributes['emergency_contact_phone'] ?? $reservation->emergency_contact_phone,
                'compliance_notes' => $attributes['compliance_notes'] ?? $reservation->compliance_notes,
                'special_requests' => $attributes['special_requests'] ?? $reservation->special_requests,
            ]);

            if (Arr::exists($attributes, 'identity_documents')) {
                $this->syncIdentityDocuments($reservation->fresh(['guestProfile']), $actor, $attributes['identity_documents'], true);
                $reservation->forceFill([
                    'id_verified_at' => $reservation->identityDocuments()->exists() ? now() : $reservation->id_verified_at,
                    'id_verified_by_user_id' => $reservation->identityDocuments()->exists() ? $actor->id : $reservation->id_verified_by_user_id,
                ])->save();
            }

            if (Arr::exists($attributes, 'visitors')) {
                $this->syncVisitors($reservation, $attributes['visitors']);
            }

            return $this->refreshReservation($reservation);
        });
    }

    public function checkIn(Reservation $reservation, User $actor, array $attributes = []): Reservation
    {
        if (! in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PENDING], true)) {
            throw new InvalidArgumentException('Only pending or confirmed reservations can be checked in.');
        }

        return DB::transaction(function () use ($reservation, $actor, $attributes) {
            $signatureFile = Arr::get($attributes, 'signature_file');
            $signaturePath = $reservation->check_in_signature_path;

            if ($signatureFile instanceof UploadedFile) {
                $signaturePath = $signatureFile->store($this->documentDirectory($reservation, 'signatures'), 'public');
            }

            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_IN,
                'actual_check_in_at' => $attributes['actual_check_in_at'] ?? now(),
                'checked_in_by_user_id' => $actor->id,
                'check_in_signature_name' => $attributes['signature_name'] ?? $reservation->check_in_signature_name,
                'check_in_signature_path' => $signaturePath,
                'signed_registration_at' => Arr::exists($attributes, 'signature_name') || Arr::exists($attributes, 'signature_file')
                    ? now()
                    : $reservation->signed_registration_at,
                'id_verified_at' => Arr::exists($attributes, 'identity_documents')
                    ? (! empty($attributes['identity_documents']) ? now() : null)
                    : $reservation->id_verified_at,
                'id_verified_by_user_id' => Arr::exists($attributes, 'identity_documents')
                    ? (! empty($attributes['identity_documents']) ? $actor->id : null)
                    : $reservation->id_verified_by_user_id,
            ]);

            if (Arr::exists($attributes, 'identity_documents')) {
                $this->syncIdentityDocuments($reservation->fresh(['guestProfile']), $actor, $attributes['identity_documents']);
            }

            if (Arr::exists($attributes, 'visitors')) {
                $this->syncVisitors($reservation, $attributes['visitors']);
            }

            $reservation->room()->update([
                'status' => Room::STATUS_OCCUPIED,
                'cleaning_status' => 'occupied',
            ]);

            $this->folioService->openForReservation($reservation->fresh());

            return $this->refreshReservation($reservation);
        });
    }

    public function checkOut(Reservation $reservation, User $actor, array $attributes = []): Reservation
    {
        if ($reservation->status !== Reservation::STATUS_CHECKED_IN) {
            throw new InvalidArgumentException('Only checked-in reservations can be checked out.');
        }

        return DB::transaction(function () use ($reservation, $actor, $attributes) {
            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_OUT,
                'actual_check_out_at' => $attributes['actual_check_out_at'] ?? now(),
                'checked_out_by_user_id' => $actor->id,
            ]);

            $reservation->room()->update([
                'status' => Room::STATUS_DIRTY,
                'cleaning_status' => 'dirty',
            ]);

            $this->housekeepingService->createCheckoutCleaningTask(
                $reservation->fresh(),
                $attributes['housekeeping_notes'] ?? 'Checkout cleaning required after guest departure.',
            );

            return $this->refreshReservation($reservation);
        });
    }

    public function moveRoom(Reservation $reservation, int $toRoomId, User $actor, ?string $reason = null): Reservation
    {
        if ($reservation->status !== Reservation::STATUS_CHECKED_IN) {
            throw new InvalidArgumentException('Room moves are only allowed for checked-in reservations.');
        }

        $targetRoom = Room::query()->findOrFail($toRoomId);

        if ((int) $targetRoom->property_id !== (int) $reservation->property_id) {
            throw new InvalidArgumentException('Room moves must stay within the same property.');
        }

        if ($this->reservationConflictService->hasConflict(
            $targetRoom->id,
            Carbon::parse($reservation->check_in_date),
            Carbon::parse($reservation->check_out_date),
            $reservation->id,
        )) {
            throw new InvalidArgumentException('The target room is not available for the current stay window.');
        }

        return DB::transaction(function () use ($reservation, $targetRoom, $actor, $reason) {
            $fromRoomId = $reservation->room_id;

            Room::query()->whereKey($fromRoomId)->update([
                'status' => Room::STATUS_DIRTY,
                'cleaning_status' => 'dirty',
            ]);

            $targetRoom->update([
                'status' => Room::STATUS_OCCUPIED,
                'cleaning_status' => 'occupied',
            ]);

            $reservation->update(['room_id' => $targetRoom->id]);

            RoomMove::query()->create([
                'company_id' => $reservation->company_id,
                'reservation_id' => $reservation->id,
                'from_room_id' => $fromRoomId,
                'to_room_id' => $targetRoom->id,
                'moved_by_user_id' => $actor->id,
                'reason' => $reason,
                'moved_at' => now(),
            ]);

            return $this->refreshReservation($reservation);
        });
    }

    protected function syncIdentityDocuments(Reservation $reservation, User $actor, array $documents, bool $createExtractionHooks = false): void
    {
        if ($documents === []) {
            $reservation->identityDocuments()->delete();

            return;
        }

        $guest = $reservation->guestProfile()->firstOrFail();

        $reservation->identityDocuments()->delete();

        $primaryPassport = null;

        foreach ($documents as $document) {
            $file = Arr::get($document, 'file');
            $filePath = $file instanceof UploadedFile
                ? $file->store($this->documentDirectory($reservation, 'identity-documents'), 'public')
                : null;

            $createdDocument = $reservation->identityDocuments()->create([
                'company_id' => $reservation->company_id,
                'guest_profile_id' => $guest->id,
                'verified_by_user_id' => $actor->id,
                'document_type' => $document['document_type'],
                'document_number' => $document['document_number'] ?? null,
                'issuing_country' => $document['issuing_country'] ?? null,
                'issued_at' => $document['issued_at'] ?? null,
                'expires_at' => $document['expires_at'] ?? null,
                'file_path' => $filePath,
                'is_primary' => (bool) ($document['is_primary'] ?? false),
                'verified_at' => now(),
                'notes' => $document['notes'] ?? null,
            ]);

            if ($createExtractionHooks && $filePath !== null && ($document['request_ocr'] ?? false)) {
                $createdDocument->extractionRequests()->create([
                    'company_id' => $reservation->company_id,
                    'reservation_id' => $reservation->id,
                    'provider' => $document['ocr_provider'] ?? 'manual-review',
                    'status' => GuestDocumentExtractionRequest::STATUS_PENDING,
                    'requested_at' => now(),
                ]);
            }

            if (($document['document_type'] ?? null) === 'passport' && (($document['is_primary'] ?? false) || $primaryPassport === null)) {
                $primaryPassport = $createdDocument;
            }
        }

        if ($primaryPassport !== null) {
            $guest->update([
                'passport_number' => $primaryPassport->document_number,
                'passport_expiry_date' => $primaryPassport->expires_at,
                'nationality' => $primaryPassport->issuing_country ?: $guest->nationality,
            ]);
        }
    }

    protected function syncVisitors(Reservation $reservation, array $visitors): void
    {
        $reservation->visitors()->delete();

        foreach ($visitors as $visitor) {
            $reservation->visitors()->create([
                'company_id' => $reservation->company_id,
                'full_name' => $visitor['full_name'],
                'relationship_to_guest' => $visitor['relationship_to_guest'] ?? null,
                'identification_number' => $visitor['identification_number'] ?? null,
                'phone' => $visitor['phone'] ?? null,
                'checked_in_at' => $visitor['checked_in_at'] ?? now(),
                'checked_out_at' => $visitor['checked_out_at'] ?? null,
                'notes' => $visitor['notes'] ?? null,
            ]);
        }
    }

    protected function documentDirectory(Reservation $reservation, string $suffix): string
    {
        return 'hotel/company-'.$reservation->company_id.'/reservations/'.$reservation->id.'/'.$suffix;
    }

    protected function extractGuestProfileUpdates(array $guestAttributes): array
    {
        if ($guestAttributes === []) {
            return [];
        }

        $updates = Arr::except($guestAttributes, ['gdpr_consent', 'marketing_consent']);

        if (Arr::exists($guestAttributes, 'gdpr_consent')) {
            $updates['gdpr_consent_at'] = $guestAttributes['gdpr_consent'] ? now() : null;
        }

        if (Arr::exists($guestAttributes, 'marketing_consent')) {
            $updates['marketing_consent_at'] = $guestAttributes['marketing_consent'] ? now() : null;
        }

        return $updates;
    }

    protected function refreshReservation(Reservation $reservation): Reservation
    {
        return $reservation->fresh([
            'property',
            'room',
            'guestProfile.identityDocuments.extractionRequests',
            'roomMoves',
            'identityDocuments.extractionRequests',
            'visitors',
        ]);
    }
}