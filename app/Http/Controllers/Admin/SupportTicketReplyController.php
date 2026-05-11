<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportTicketReplyRequest;
use App\Models\SupportTicket;
use App\Services\AdminNotificationService;
use App\Services\SupportTicketContentSanitizer;
use App\Support\AssetPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketReplyController extends Controller
{
    public function __construct(
        protected SupportTicketContentSanitizer $contentSanitizer,
        protected AdminNotificationService $notificationService,
    ) {}

    public function store(StoreSupportTicketReplyRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('view', $ticket);

        $isInternal = (bool) $request->boolean('is_internal');

        if ($isInternal && ! $request->user()?->isSuperAdmin()) {
            $isInternal = false;
        }

        $ticket->replies()->create([
            'company_id' => $ticket->company_id,
            'user_id' => $request->user()->id,
            'is_internal' => $isInternal,
            'body' => $this->contentSanitizer->sanitize($request->string('body')->toString()),
        ]);

        if ($ticket->first_response_at === null && $request->user()->isSuperAdmin()) {
            $ticket->first_response_at = now();
        }

        $ticket->last_reply_at = now();
        $ticket->status = $request->user()->isSuperAdmin()
            ? SupportTicket::STATUS_WAITING_ON_CUSTOMER
            : SupportTicket::STATUS_IN_PROGRESS;
        $ticket->save();

        if (! $isInternal && ! $request->user()->isSuperAdmin()) {
            $this->notificationService->send(
                'Customer replied to support ticket',
                sprintf('%s replied on %s.', $request->user()->name, $ticket->ticket_number),
                route('admin.tickets.show', $ticket),
            );
        }

        return back()->with('success', 'Reply added successfully.');
    }

    public function uploadEditorImage(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user && ($user->can('create-ticket') || $user->can('update-ticket')), 403);

        $validated = $request->validate([
            'upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $companySegment = $user->company_id ?? 'global';
        $path = $validated['upload']->store("support-tickets/{$companySegment}", 'public');

        return response()->json([
            'url' => AssetPath::storageUrl($path),
        ]);
    }
}
