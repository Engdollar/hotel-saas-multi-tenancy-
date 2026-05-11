<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDashboardPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardPreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widgets' => ['required', 'array'],
            'widgets.*' => ['boolean'],
            'layout' => ['nullable', 'array'],
            'layout.*' => ['string', 'max:120'],
            'drag_enabled' => ['nullable', 'boolean'],
        ]);

        $layout = collect($validated['layout'] ?? [])
            ->map(fn (string $key) => Str::of($key)->trim()->toString())
            ->filter()
            ->values()
            ->all();

        $attributes = [
            'widgets' => $validated['widgets'],
        ];

        if (Schema::hasColumn('user_dashboard_preferences', 'layout')) {
            $attributes['layout'] = $layout;
        }

        if (Schema::hasColumn('user_dashboard_preferences', 'drag_enabled')) {
            $attributes['drag_enabled'] = (bool) ($validated['drag_enabled'] ?? true);
        }

        UserDashboardPreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $attributes,
        );

        return response()->json([
            'saved' => true,
            'message' => 'Dashboard layout updated.',
        ]);
    }
}