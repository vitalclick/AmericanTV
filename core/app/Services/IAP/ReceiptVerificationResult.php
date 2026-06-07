<?php

namespace App\Services\IAP;

class ReceiptVerificationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $transactionId = null,
        public readonly ?string $productId = null,
        public readonly ?\DateTimeImmutable $purchasedAt = null,
        public readonly ?\DateTimeImmutable $expiresAt = null,
        public readonly bool $isSubscription = false,
        public readonly bool $isSandbox = false,
        public readonly ?string $rawPayload = null,
        public readonly ?string $error = null,
    ) {}

    public static function failure(string $error): self
    {
        return new self(valid: false, error: $error);
    }
}
