<?php

namespace App\Exceptions;

use RuntimeException;

class StaleModelException extends RuntimeException
{
    public function __construct(
        public readonly string $resource,
        public readonly int|string $resourceId,
    ) {
        parent::__construct("The {$resource} was modified by another request.");
    }
}
