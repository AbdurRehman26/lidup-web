<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ReleaseController extends Controller
{
    #[OA\Get(
        path: '/releases/latest',
        operationId: 'getLatestRelease',
        summary: 'Get the latest macOS release',
        description: 'Returns public update metadata for the latest published LidUp build. No account or activation key is required.',
        tags: ['Releases'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The latest published release metadata.',
                content: new OA\JsonContent(
                    required: ['version', 'minimum_os', 'release_notes', 'published_at', 'download_url', 'available'],
                    properties: [
                        new OA\Property(property: 'version', type: 'string', example: '1.2.0'),
                        new OA\Property(property: 'minimum_os', type: 'string', example: 'macOS 14 Sonoma'),
                        new OA\Property(
                            property: 'release_notes',
                            type: 'string',
                            nullable: true,
                            example: 'Update checking and reliability improvements.',
                        ),
                        new OA\Property(
                            property: 'published_at',
                            type: 'string',
                            format: 'date-time',
                            nullable: true,
                            example: '2026-08-01T10:30:00+00:00',
                        ),
                        new OA\Property(
                            property: 'download_url',
                            type: 'string',
                            format: 'uri',
                            example: 'https://lidup.app/download/latest',
                        ),
                        new OA\Property(
                            property: 'available',
                            description: 'Whether the installer currently exists in release storage.',
                            type: 'boolean',
                            example: true,
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'No published current release exists.'),
            new OA\Response(response: 429, description: 'Too many update checks.'),
        ],
    )]
    public function latest(): JsonResponse
    {
        $release = Release::query()
            ->available()
            ->where('is_current', true)
            ->latest('published_at')
            ->firstOrFail();

        return response()->json([
            'version' => $release->version,
            'minimum_os' => $release->minimum_os,
            'release_notes' => $release->release_notes,
            'published_at' => $release->published_at?->toIso8601String(),
            'download_url' => route('download.latest'),
            'available' => Storage::disk('local')->exists($release->file_path),
        ]);
    }
}
