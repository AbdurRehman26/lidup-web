<?php

namespace App\Http\Controllers;

use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function index(): Response
    {
        $release = Release::query()
            ->available()
            ->where('is_current', true)
            ->latest('published_at')
            ->first();

        return Inertia::render('Download', [
            'release' => $release ? [
                'version' => $release->version,
                'size' => $this->formatBytes($release->file_size),
                'minimum_os' => $release->minimum_os,
                'available' => Storage::disk('local')->exists($release->file_path),
                'url' => route('download.latest'),
            ] : [
                'version' => '1.0.0-beta.1',
                'size' => '18.4 MB',
                'minimum_os' => 'macOS 14 Sonoma',
                'available' => false,
                'url' => route('download.latest'),
            ],
        ]);
    }

    public function latest(Request $request): BinaryFileResponse
    {
        $release = Release::query()
            ->available()
            ->where('is_current', true)
            ->latest('published_at')
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($release->file_path), 404);

        $release->downloads()->create([
            'user_id' => $request->user()?->id,
            'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), config('app.key')) : null,
            'user_agent' => str($request->userAgent())->limit(1000),
            'downloaded_at' => now(),
        ]);

        $extension = strtolower(pathinfo($release->file_path, PATHINFO_EXTENSION)) ?: 'dmg';
        $contentType = match ($extension) {
            'pkg' => 'application/x-newton-compatible-pkg',
            'zip' => 'application/zip',
            default => 'application/x-apple-diskimage',
        };

        return response()->download(
            Storage::disk('local')->path($release->file_path),
            "LidUp-{$release->version}.{$extension}",
            ['Content-Type' => $contentType],
        );
    }

    private function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '—';
        }

        return number_format($bytes / 1024 / 1024, 1).' MB';
    }
}
