<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class HotelErpFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'starter',
                'name' => 'Starter',
                'monthly_price' => 99,
                'yearly_price' => 990,
                'currency_code' => 'USD',
                'max_properties' => 1,
                'max_users' => 15,
                'max_storage_gb' => 20,
                'features' => ['reservations', 'frontdesk', 'basic-accounting'],
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'monthly_price' => 499,
                'yearly_price' => 4990,
                'currency_code' => 'USD',
                'max_properties' => 20,
                'max_users' => 500,
                'max_storage_gb' => 500,
                'features' => ['reservations', 'frontdesk', 'accounting', 'inventory', 'payroll', 'bi'],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['code' => $plan['code']],
                $plan,
            );
        }
    }
}