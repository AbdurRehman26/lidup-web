<?php

namespace App\Http\Controllers;

use App\Models\FeedbackItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoadmapController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $sort = $request->string('sort', 'top')->toString();

        $items = FeedbackItem::query()
            ->where(fn ($query) => $query
                ->where('is_public', true)
                ->when($request->user(), fn ($query, $user) => $query->orWhere('user_id', $user->id)))
            ->whereIn('type', ['feature', 'problem'])
            ->when(in_array($status, ['in_review', 'planned', 'completed'], true), fn ($query) => $query->where('status', $status))
            ->withCount('votes')
            ->when(
                $sort === 'new',
                fn ($query) => $query->latest(),
                fn ($query) => $query->orderByDesc('votes_count')->latest(),
            )
            ->get()
            ->map(fn (FeedbackItem $item): array => $this->present($item, $request));

        $reviews = FeedbackItem::query()
            ->where(fn ($query) => $query
                ->where('is_public', true)
                ->when($request->user(), fn ($query, $user) => $query->orWhere('user_id', $user->id)))
            ->where('type', 'review')
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (FeedbackItem $item): array => $this->present($item, $request));

        return Inertia::render('Roadmap', [
            'items' => $items,
            'reviews' => $reviews,
            'filters' => [
                'status' => $status,
                'sort' => in_array($sort, ['top', 'new'], true) ? $sort : 'top',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['review', 'feature', 'problem'])],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'rating' => [Rule::requiredIf($request->input('type') === 'review'), 'nullable', 'integer', 'between:1,5'],
            'submitter_name' => [Rule::requiredIf($request->user() === null), 'nullable', 'string', 'max:120'],
            'submitter_email' => [Rule::requiredIf($request->user() === null), 'nullable', 'email:rfc', 'max:255'],
        ]);

        FeedbackItem::create([
            ...$validated,
            'user_id' => $request->user()?->id,
            'submitter_name' => $request->user()?->name ?? $validated['submitter_name'] ?? null,
            'submitter_email' => $request->user()?->email ?? $validated['submitter_email'] ?? null,
            'status' => 'submitted',
            'is_public' => false,
        ]);

        return back()->with('feedback_message', 'Thanks — your submission is now with the LidUp team for review.');
    }

    public function vote(Request $request, FeedbackItem $feedbackItem): RedirectResponse
    {
        abort_unless($feedbackItem->is_public && in_array($feedbackItem->type, ['feature', 'problem'], true), 404);

        $vote = $feedbackItem->votes()->whereBelongsTo($request->user())->first();

        if ($vote) {
            $vote->delete();
        } else {
            $feedbackItem->votes()->create(['user_id' => $request->user()->id]);
        }

        return back();
    }

    private function present(FeedbackItem $item, Request $request): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'title' => $item->title,
            'description' => $item->description,
            'rating' => $item->rating,
            'status' => $item->status,
            'admin_response' => $item->admin_response,
            'is_public' => $item->is_public,
            'is_own' => $request->user()?->is($item->user) ?? false,
            'votes_count' => $item->votes_count ?? $item->votes()->count(),
            'has_voted' => $request->user()
                ? $item->votes()->whereBelongsTo($request->user())->exists()
                : false,
            'submitter_name' => $item->submitter_name ?: $item->user?->name,
            'created_at' => $item->created_at,
        ];
    }
}
