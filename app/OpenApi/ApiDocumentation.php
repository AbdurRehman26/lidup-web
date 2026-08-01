<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'LidUp API',
    description: 'Validate LidUp license keys, manage Mac device activations, report completed tasks, and check for app updates.',
)]
#[OA\Server(
    url: '/api/v1',
    description: 'Current LidUp application',
)]
#[OA\SecurityScheme(
    securityScheme: 'licenseKey',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'LidUp activation key',
    description: 'Use the complete activation key generated for the user.',
)]
#[OA\Tag(
    name: 'License',
    description: 'License entitlement validation',
)]
#[OA\Tag(
    name: 'Activation',
    description: 'Mac device activation management',
)]
#[OA\Tag(
    name: 'Tasks',
    description: 'Task completion notifications',
)]
#[OA\Tag(
    name: 'Releases',
    description: 'Public macOS app update metadata',
)]
class ApiDocumentation {}
