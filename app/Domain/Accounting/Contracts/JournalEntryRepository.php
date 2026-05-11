<?php

namespace App\Domain\Accounting\Contracts;

use App\Domain\Accounting\Models\JournalEntry;

interface JournalEntryRepository
{
    public function create(array $attributes, array $lines): JournalEntry;
}