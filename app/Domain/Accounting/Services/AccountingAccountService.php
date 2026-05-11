<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\LedgerAccount;

class AccountingAccountService
{
    public function cash(string $currencyCode = 'USD'): LedgerAccount
    {
        return $this->ensure('1000', 'Cash on Hand', LedgerAccount::TYPE_ASSET, 'cash', $currencyCode);
    }

    public function receivable(string $currencyCode = 'USD'): LedgerAccount
    {
        return $this->ensure('1100', 'Accounts Receivable', LedgerAccount::TYPE_ASSET, 'trade_receivable', $currencyCode);
    }

    public function payable(string $currencyCode = 'USD'): LedgerAccount
    {
        return $this->ensure('2100', 'Accounts Payable', LedgerAccount::TYPE_LIABILITY, 'trade_payable', $currencyCode);
    }

    public function roomRevenue(string $currencyCode = 'USD'): LedgerAccount
    {
        return $this->ensure('4000', 'Room Revenue', LedgerAccount::TYPE_INCOME, 'room_revenue', $currencyCode);
    }

    public function foodAndBeverageRevenue(string $currencyCode = 'USD'): LedgerAccount
    {
        return $this->ensure('4010', 'Food and Beverage Revenue', LedgerAccount::TYPE_INCOME, 'food_beverage_revenue', $currencyCode);
    }

    public function refunds(string $currencyCode = 'USD'): LedgerAccount
    {
        return $this->ensure('4050', 'Refunds and Allowances', LedgerAccount::TYPE_EXPENSE, 'refunds', $currencyCode);
    }

    public function operatingExpense(string $currencyCode = 'USD'): LedgerAccount
    {
        return $this->ensure('5000', 'Operating Expense', LedgerAccount::TYPE_EXPENSE, 'operating_expense', $currencyCode);
    }

    public function ensure(string $code, string $name, string $type, ?string $subtype, string $currencyCode): LedgerAccount
    {
        return LedgerAccount::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'subtype' => $subtype,
                'currency_code' => $currencyCode,
                'is_system' => true,
                'is_active' => true,
            ],
        );
    }
}