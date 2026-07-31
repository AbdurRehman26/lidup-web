<?php

namespace App\Data;

final readonly class TransactionalEmail
{
    /**
     * @param  array<int, string>  $to
     * @param  array<int, string>  $replyTo
     */
    public function __construct(
        public array $to,
        public string $subject,
        public string $html,
        public array $replyTo = [],
    ) {}
}
