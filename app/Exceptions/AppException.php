<?php
// app/Exceptions/AppException.php
namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Http\JsonResponse;

use app\Utilities;

class AppException extends Exception
{
    protected $statusCode = 500;
    protected $errorCode = 'INTERNAL_ERROR';
    protected $message = '';
    protected $exception = null;

    public function __construct(
        int $statusCode = 500,
        ?string $message = null,
        ?Throwable $e = null,
        ?string $errorCode = null,
    ) {
        if($statusCode == 402) {
            parent::__construct($message);
            $this->exception = $this;
        }else{
            $this->exception = ($e) ? $e : $this;
        }
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode ?: self::getDefaultErrorCode($statusCode);
        if($message) $this->message = $message;
    }

    public static function getDefaultErrorCode(int $statusCode): string
    {
        return match($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            402 => 'PAYMENT_REQUIRED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            500 => 'INTERNAL_ERROR',
            default => 'UNKNOWN_ERROR'
        };
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }

    public function render(): JsonResponse
    {
        // Use your Utility class for consistent response format
        return Utilities::error($this->exception, $this->message);
    }
}