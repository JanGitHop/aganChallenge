<?php

declare(strict_types=1);

namespace App\Exception;

class CartNotFoundException extends \Exception
{
    public function __construct(
        string $message = 'Cart not found',
        int $code = 404,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
