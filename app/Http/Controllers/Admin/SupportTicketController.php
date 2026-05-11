<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Http\Requests\UpdateSupportTicketRequest;
use App\Models\Company;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Tenancy\CurrentCompanyContext;
use App\Services\AdminNotificationService;
use App\Services\SupportTicketContentSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(
        protected CurrentCompanyContext $companyContext,
        protected SupportTicketContentSanitizer $contentSanitizer,
        protected AdminNotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupportTicket::class);

        $query = SupportTicket::query()->with(['company:id,name', 'creator:id,name,email', 'assignee:id,name']);

        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $category = trim((string) $request->query('category', ''));
        $assigneeId = (int) $request->query('assigned_to_user_id', 0);
        $companyId = auth()->user()?->isSuperAdmin()
            ? (int) $request->query('company_id', 0)
            : 0;
        $search = trim((string) $request->query('search', ''));

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($priority !== '') {
            $query->where('priority', $priority);
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($assigneeId > 0) {
            $query->where('assigned_to_user_id', $assigneeId);
        }

        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        $statsBase = SupportTicket::query();

        $stats = [
            'open' => (clone $statsBase)->where('status', SupportTicket::STATUS_OPEN)->count(),
            'in_progress' => (clone $statsBase)->where('status', SupportTicket::STATUS_IN_PROGRESS)->count(),
            'waiting_on_customer' => (clone $statsBase)->where('status', SupportTicket::STATUS_WAITING_ON_CUSTOMER)->count(),
            'resolved' => (clone $statsBase)->whereIn('status', [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED])->count(),
            'urgent' => (clone $statsBase)->where('priority', SupportTicket::PRIORITY_URGENT)->count(),
            'unassigned' => (clone $statsBase)->whereNull('assigned_to_user_id')->count(),
        ];

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'status' => $status,
            'priority' => $priority,
            'category' => $category,
            'assignedToUserId' => $assigneeId,
            'companyId' => $companyId,
            'search' => $search,
            'statuses' => SupportTicket::statuses(),
            'priorities' => SupportTicket::priorities(),
            'categories' => SupportTicket::query()->select('category')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all(),
            'assigneeOptions' => User::query()->select(['id', 'name'])->orderBy('name')->get(),
            'companyOptions' => auth()->user()?->isSuperAdmin()
                ? Company::withoutGlobalScopes()->select(['id', 'name'])->orderBy('name')->get()
                : collect(),
            'isSuperAdmin' => auth()->user()?->isSuperAdmin() ?? false,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SupportTicket::class);

        return view('admin.tickets.create', [
            'priorities' => SupportTicket::priorities(),
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', SupportTicket::class);

        $user = $request->user();

        $ticket = SupportTicket::create([
            'company_id' => $this->companyContext->id() ?? $user->company_id,
            'created_by_user_id' => $user->id,
            'subject' => $request->string('subject')->toString(),
            'category' => $request->string('category')->toString(),
            'priority' => $request->string('priority')->toString(),
            'status' => SupportTicket::STATUS_OPEN,
            'description' => $this->contentSanitizer->sanitize($request->string('description')->toString()),
            'last_reply_at' => now(),
        ]);

        $this->notificationService->send(
            'New support ticket created',
            sprintf('%s opened ticket %s (%s).', $user->name, $ticket->ticket_number, $ticket->subject),
            route('admin.tickets.show', $ticket),
        );

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'creator:id,name,email',
            'assignee:id,name,email',
            'replies.user:id,name,email',
        ]);

        $this->hydrateReplyAuthorNames($ticket);

        $assignedUsers = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get();

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'statuses' => SupportTicket::statuses(),
            'priorities' => SupportTicket::priorities(),
            'assignedUsers' => $assignedUsers,
            'isSuperAdmin' => auth()->user()?->isSuperAdmin() ?? false,
        ]);
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $data = $request->validated();

        if (! $request->user()?->isSuperAdmin()) {
            $data['assigned_to_user_id'] = $ticket->assigned_to_user_id;
        }

        if ($data['status'] === SupportTicket::STATUS_RESOLVED || $data['status'] === SupportTicket::STATUS_CLOSED) {
            $data['resolved_at'] = now();
        } elseif ($ticket->resolved_at !== null) {
            $data['resolved_at'] = null;
        }

        $ticket->update($data);

        return back()->with('success', 'Ticket updated successfully.');
    }

    public function stream(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'creator:id,name,email',
            'assignee:id,name,email',
            'replies.user:id,name,email',
        ]);

        $this->hydrateReplyAuthorNames($ticket);

        $latestReplyUpdatedAt = $ticket->replies->max('updated_at');
        $fingerprint = implode('|', [
            optional($ticket->updated_at)->timestamp,
            optional($ticket->last_reply_at)->timestamp,
            optional($latestReplyUpdatedAt)->timestamp,
            $ticket->replies->count(),
        ]);

        if ((string) $request->query('fingerprint', '') === $fingerprint) {
            return response()->json([
                'has_updates' => false,
                'fingerprint' => $fingerprint,
            ]);
        }

        $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;

        return response()->json([
            'has_updates' => true,
            'fingerprint' => $fingerprint,
            'conversation_html' => view('admin.tickets.partials.conversation', [
                'ticket' => $ticket,
                'isSuperAdmin' => $isSuperAdmin,
            ])->render(),
            'details_html' => view('admin.tickets.partials.details', [
                'ticket' => $ticket,
            ])->render(),
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'assigned_to_user_id' => $ticket->assigned_to_user_id,
        ]);
    }

    protected function hydrateReplyAuthorNames(SupportTicket $ticket): void
    {
        $replyUserIds = $ticket->replies
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($replyUserIds->isEmpty()) {
            return;
        }

        $userNames = User::withoutGlobalScopes()
            ->whereIn('id', $replyUserIds)
            ->pluck('name', 'id');

        $ticket->replies->each(function ($reply) use ($userNames): void {
            $fallback = $reply->user?->name;
            $reply->setAttribute('author_name', $userNames->get((int) $reply->user_id, $fallback));
        });
    }
}
