<?php

namespace App\Http\Resources;

use App\Support\AssetPath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_number' => $this->reservation_number,
            'property_id' => $this->property_id,
            'room_id' => $this->room_id,
            'guest_profile_id' => $this->guest_profile_id,
            'booking_source' => $this->booking_source,
            'currency_code' => $this->currency_code,
            'status' => $this->status,
            'check_in_date' => $this->check_in_date,
            'check_out_date' => $this->check_out_date,
            'actual_check_in_at' => $this->actual_check_in_at,
            'actual_check_out_at' => $this->actual_check_out_at,
            'check_in_signature_name' => $this->check_in_signature_name,
            'check_in_signature_path' => $this->check_in_signature_path,
            'check_in_signature_url' => AssetPath::storageUrl($this->check_in_signature_path),
            'signed_registration_at' => $this->signed_registration_at,
            'id_verified_at' => $this->id_verified_at,
            'pre_arrival_status' => $this->pre_arrival_status,
            'pre_arrival_submitted_at' => $this->pre_arrival_submitted_at,
            'pre_arrival_completed_at' => $this->pre_arrival_completed_at,
            'expected_arrival_time' => $this->expected_arrival_time,
            'registration_channel' => $this->registration_channel,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'compliance_notes' => $this->compliance_notes,
            'adult_count' => $this->adult_count,
            'child_count' => $this->child_count,
            'night_count' => $this->night_count,
            'rate_amount' => $this->rate_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'special_requests' => $this->special_requests,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'guest' => new GuestProfileResource($this->whenLoaded('guestProfile')),
            'identity_documents' => GuestIdentityDocumentResource::collection($this->whenLoaded('identityDocuments')),
            'visitors' => ReservationVisitorResource::collection($this->whenLoaded('visitors')),
            'room_moves' => $this->whenLoaded('roomMoves', fn () => $this->roomMoves->map(fn ($move) => [
                'id' => $move->id,
                'from_room_id' => $move->from_room_id,
                'to_room_id' => $move->to_room_id,
                'reason' => $move->reason,
                'moved_at' => $move->moved_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}