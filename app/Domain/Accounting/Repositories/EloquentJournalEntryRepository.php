<?php

namespace App\Domain\Accounting\Repositories;

use App\Domain\Accounting\Contracts\JournalEntryRepository;
use App\Domain\Accounting\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class EloquentJournalEntryRepository implements JournalEntryRepository
{
    public function create(array $attributes, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($attributes, $lines) {
            $entry = JournalEntry::query()->create($attributes);

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            return $entry->load('lines');
        });
    }
}