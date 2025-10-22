<?php

declare(strict_types=1);

namespace App\Exception;

class CartItemNotFoundException extends \Exception
{
    public function __construct(
        string $message = 'Item not found',
        int $code = 404,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
