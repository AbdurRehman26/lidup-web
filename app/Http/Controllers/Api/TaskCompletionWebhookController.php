<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskCompletionEvent;
use App\Notifications\TaskCompleted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class TaskCompletionWebhookController extends Controller
{
    #[OA\Post(
        path: '/webhooks/task-completed',
        operationId: 'taskCompletedWebhook',
        summary: 'Report a completed task',
        description: 'Stores a terminal task event and emails the owner of the activation key. Reusing an event_id is idempotent and never sends a duplicate email.',
        security: [['licenseKey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['event_id', 'status', 'summary'],
                properties: [
                    new OA\Property(property: 'event_id', type: 'string', example: 'evt_01JZ8F7KQJ3N4D6M8P2R'),
                    new OA\Property(property: 'task_id', type: 'string', nullable: true, example: 'codex-task-184'),
                    new OA\Property(property: 'status', type: 'string', enum: ['completed', 'failed', 'cancelled'], example: 'completed'),
                    new OA\Property(property: 'summary', type: 'string', example: 'Production build and test suite completed successfully.'),
                    new OA\Property(property: 'duration_seconds', type: 'integer', minimum: 0, nullable: true, example: 754),
                    new OA\Property(property: 'device_id', type: 'string', nullable: true, example: 'macbook-pro-hardware-id'),
                    new OA\Property(property: 'device_name', type: 'string', nullable: true, example: 'Work MacBook Pro'),
                    new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(
                        property: 'details',
                        type: 'object',
                        nullable: true,
                        example: ['agent' => 'Codex', 'tests' => 25, 'branch' => 'main'],
                    ),
                ],
            ),
        ),
        tags: ['Tasks'],
        responses: [
            new OA\Response(response: 202, description: 'The event was stored and the email was sent.'),
            new OA\Response(response: 200, description: 'The event was already processed; no duplicate email was sent.'),
            new OA\Response(response: 401, description: 'The activation key is missing or invalid.'),
            new OA\Response(response: 403, description: 'The activation key cannot submit task events.'),
            new OA\Response(response: 422, description: 'The payload is invalid.'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token || ! $token->can('tasks:complete')) {
            return response()->json([
                'accepted' => false,
                'reason' => 'ability_denied',
                'message' => 'This key cannot submit task completion events.',
            ], 403);
        }

        $validated = $request->validate([
            'event_id' => ['required', 'string', 'max:191'],
            'task_id' => ['nullable', 'string', 'max:191'],
            'status' => ['required', Rule::in(['completed', 'failed', 'cancelled'])],
            'summary' => ['required', 'string', 'max:2000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:31536000'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'completed_at' => ['nullable', 'date'],
            'details' => ['nullable', 'array', 'max:30'],
        ]);

        $event = TaskCompletionEvent::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'event_id' => $validated['event_id'],
            ],
            [
                ...$validated,
                'completed_at' => $validated['completed_at'] ?? now(),
            ],
        );

        if ($event->notification_sent_at) {
            return response()->json([
                'accepted' => true,
                'duplicate' => true,
                'event_id' => $event->event_id,
                'message' => 'This event was already processed.',
            ]);
        }

        $request->user()->notify(new TaskCompleted($event));
        $event->forceFill(['notification_sent_at' => now()])->save();

        return response()->json([
            'accepted' => true,
            'duplicate' => false,
            'event_id' => $event->event_id,
            'message' => 'Task completion email sent.',
        ], 202);
    }
}
