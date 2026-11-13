<?php

namespace App\Exceptions;

use RuntimeException;

// Thrown when the AI cannot answer. The reason is one of: off, busy, blocked, error.
class AssistantUnavailable extends RuntimeException
{
    public function __construct(public readonly string $reason, string $detail = '')
    {
        parent::__construct($detail !== '' ? $detail : $reason);
    }
}