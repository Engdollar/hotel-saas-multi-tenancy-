<?php

namespace App\Http\Requests;

use App\Services\DashboardService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardStudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dashboardService = app(DashboardService::class);

        return [
            'dashboard_stats' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($dashboardService): void {
                    $rows = json_decode((string) $value, true);

                    if (! is_array($rows)) {
                        $fail('The stats configuration is invalid.');
                        return;
                    }

                    foreach ($rows as $row) {
                        if (! is_array($row)) {
                            $fail('Each stat card must be valid.');
                            return;
                        }

                        if (! array_key_exists((string) ($row['source'] ?? ''), $dashboardService->statSources())) {
                            $fail('One of the selected stat sources is invalid.');
                            return;
                        }

                        if (! array_key_exists((string) ($row['icon'] ?? ''), $dashboardService->iconOptions())) {
                            $fail('One of the selected stat icons is invalid.');
                            return;
                        }
                    }
                },
            ],
            'dashboard_charts' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($dashboardService): void {
                    $rows = json_decode((string) $value, true);
                    $chartSources = $dashboardService->chartSources();

                    if (! is_array($rows)) {
                        $fail('The chart configuration is invalid.');
                        return;
                    }

                    foreach ($rows as $row) {
                        if (! is_array($row)) {
                            $fail('Each chart must be valid.');
                            return;
                        }

                        $source = (string) ($row['source'] ?? '');

                        if (! array_key_exists($source, $chartSources)) {
                            $fail('One of the selected chart sources is invalid.');
                            return;
                        }

                        if (! in_array((string) ($row['type'] ?? ''), $chartSources[$source]['types'] ?? [], true)) {
                            $fail('One of the selected chart types is invalid.');
                            return;
                        }
                    }
                },
            ],
            'auth_login_visual_mode' => ['required', 'in:default,custom-image'],
            'auth_register_visual_mode' => ['required', 'in:default,custom-image'],
            'auth_login_visual_image' => ['nullable', 'image', 'max:4096'],
            'auth_register_visual_image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}