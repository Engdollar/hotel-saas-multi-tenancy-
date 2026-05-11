<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Invoice;
use App\Domain\Accounting\Models\SupplierBill;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinanceReportingService
{
    public function arAgingSummary(?string $asOfDate = null): array
    {
        $asOf = Carbon::parse($asOfDate ?? now()->toDateString())->endOfDay();
        $items = Invoice::query()
            ->where('balance_amount', '>', 0)
            ->whereNotIn('status', [Invoice::STATUS_VOID])
            ->get();

        return $this->buildAgingSummary($items, $asOf, 'invoice_number', 'due_date');
    }

    public function apAgingSummary(?string $asOfDate = null): array
    {
        $asOf = Carbon::parse($asOfDate ?? now()->toDateString())->endOfDay();
        $items = SupplierBill::query()
            ->where('balance_amount', '>', 0)
            ->whereNotIn('status', [SupplierBill::STATUS_VOID])
            ->get();

        return $this->buildAgingSummary($items, $asOf, 'bill_number', 'due_date');
    }

    protected function buildAgingSummary(Collection $items, CarbonInterface $asOf, string $numberField, string $dueDateField): array
    {
        $buckets = [
            'current' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '91_plus' => 0.0,
        ];

        $lines = $items->map(function ($item) use (&$buckets, $asOf, $numberField, $dueDateField) {
            $dueDate = $item->{$dueDateField} ? Carbon::parse($item->{$dueDateField}) : null;
            $daysPastDue = $dueDate && $dueDate->lessThanOrEqualTo($asOf)
                ? $dueDate->diffInDays($asOf)
                : 0;
            $bucket = $this->bucketFor($dueDate, $asOf, $daysPastDue);
            $amount = (float) $item->balance_amount;
            $buckets[$bucket] += $amount;

            return [
                'id' => $item->id,
                'number' => $item->{$numberField},
                'due_date' => $dueDate?->toDateString(),
                'days_past_due' => $daysPastDue,
                'balance_amount' => number_format($amount, 2, '.', ''),
                'bucket' => $bucket,
            ];
        })->values()->all();

        return [
            'as_of_date' => $asOf->toDateString(),
            'open_count' => $items->count(),
            'buckets' => collect($buckets)->map(fn (float $amount) => number_format($amount, 2, '.', ''))->all(),
            'total_balance' => number_format(array_sum($buckets), 2, '.', ''),
            'items' => $lines,
        ];
    }

    protected function bucketFor(?CarbonInterface $dueDate, CarbonInterface $asOf, int $daysPastDue): string
    {
        if (! $dueDate || $dueDate->greaterThan($asOf)) {
            return 'current';
        }

        return match (true) {
            $daysPastDue <= 30 => '1_30',
            $daysPastDue <= 60 => '31_60',
            $daysPastDue <= 90 => '61_90',
            default => '91_plus',
        };
    }
}