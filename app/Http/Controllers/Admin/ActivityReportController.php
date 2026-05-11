<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityReportController extends Controller
{
    public function index(Request $request): View
    {
        $subjectOptions = $this->subjectOptions();

        $query = Activity::query()
            ->with('causer')
            ->when($request->filled('causer_id'), fn (Builder $builder) => $builder->where('causer_id', $request->integer('causer_id')))
            ->when($request->filled('subject_type'), fn (Builder $builder) => $builder->where('subject_type', $request->string('subject_type')->toString()))
            ->when($request->filled('event'), function (Builder $builder) use ($request) {
                $event = $request->string('event')->toString();

                $builder->where(function (Builder $nested) use ($event) {
                    $nested->where('event', $event)->orWhere('description', $event);
                });
            })
            ->when($request->filled('date_from'), fn (Builder $builder) => $builder->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $builder) => $builder->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest();

        $activities = (clone $query)->paginate(12)->withQueryString();
        $summary = $this->summary((clone $query)->get(), $subjectOptions);

        return view('admin.activity.index', [
            'activities' => $activities,
            'activityEvents' => Activity::query()
                ->selectRaw('COALESCE(event, description) as activity_event')
                ->whereNotNull('description')
                ->distinct()
                ->orderBy('activity_event')
                ->pluck('activity_event')
                ->filter()
                ->values(),
            'subjectOptions' => $subjectOptions,
            'summary' => $summary,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    protected function summary($activities, array $subjectOptions): array
    {
        $mostCommonEvent = $activities
            ->groupBy(fn (Activity $activity) => $activity->event ?: $activity->description ?: 'Recorded')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        $mostActiveArea = $activities
            ->groupBy(fn (Activity $activity) => $subjectOptions[$activity->subject_type] ?? 'Other')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        $mostActiveUserId = $activities
            ->filter(fn (Activity $activity) => $activity->causer_id)
            ->groupBy('causer_id')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        $mostActiveUser = $mostActiveUserId ? User::query()->find($mostActiveUserId)?->name : 'System';

        return [
            ['label' => 'Total events', 'value' => $activities->count(), 'description' => 'Matching the active filters'],
            ['label' => 'Top activity', 'value' => $mostCommonEvent ?: 'None', 'description' => 'Most frequent action type'],
            ['label' => 'Busy module', 'value' => $mostActiveArea ?: 'None', 'description' => 'Most affected workspace area'],
            ['label' => 'Top actor', 'value' => $mostActiveUser ?: 'System', 'description' => 'User behind the most events'],
        ];
    }

    protected function subjectOptions(): array
    {
        return [
            Company::class => 'Companies',
            User::class => 'Users',
            Role::class => 'Roles',
            Permission::class => 'Permissions',
            Setting::class => 'Settings',
        ];
    }
}