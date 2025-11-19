<?php

namespace App\Enums;

enum StatusPixEnum: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case CONFIRMED = 'CONFIRMED';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pix created, awaiting payment',
            self::PROCESSING => 'Pix created, awaiting payment',
            self::CONFIRMED => 'Payment confirmed',
            self::PAID => 'Payment completed successfully',
            self::CANCELLED => 'Payment cancelled by subacquirer',
            self::FAILED => 'Payment processing error',
        };
    }

    public static function fromSubacquirer(string $status, string $subacquirer): self
    {
        return match($subacquirer) {
            'subadq_a' => match(strtoupper($status)) {
                'CONFIRMED' => self::CONFIRMED,
                'PAID' => self::PAID,
                default => self::from(strtoupper($status)) ?? self::PENDING,
            },
            'subadq_b' => match(strtoupper($status)) {
                'PAID' => self::PAID,
                default => self::from(strtoupper($status)) ?? self::PENDING,
            },
            default => self::PENDING,
        };
    }
}
