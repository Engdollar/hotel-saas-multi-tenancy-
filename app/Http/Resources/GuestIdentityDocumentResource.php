<?php

namespace App\Http\Resources;

use App\Support\AssetPath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestIdentityDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'issuing_country' => $this->issuing_country,
            'issued_at' => $this->issued_at,
            'expires_at' => $this->expires_at,
            'file_path' => $this->file_path,
            'file_url' => AssetPath::storageUrl($this->file_path),
            'is_primary' => $this->is_primary,
            'verified_at' => $this->verified_at,
            'notes' => $this->notes,
            'extraction_requests' => GuestDocumentExtractionRequestResource::collection($this->whenLoaded('extractionRequests')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}