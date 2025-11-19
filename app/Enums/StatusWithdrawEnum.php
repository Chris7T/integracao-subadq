<?php

namespace App\Enums;

enum StatusWithdrawEnum: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SUCCESS = 'SUCCESS';
    case DONE = 'DONE';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Withdraw created, awaiting processing',
            self::PROCESSING => 'Withdraw created, awaiting processing',
            self::SUCCESS => 'Withdraw completed successfully',
            self::DONE => 'Withdraw completed (equivalent to SUCCESS)',
            self::FAILED => 'Withdraw processing failed',
            self::CANCELLED => 'Withdraw cancelled by subacquirer',
        };
    }

    public function toSuccess(): self
    {
        return match($this) {
            self::DONE => self::SUCCESS,
            default => $this,
        };
    }

    public static function fromSubacquirer(string $status, string $subacquirer): self
    {
        return match($subacquirer) {
            'subadq_a' => match(strtoupper($status)) {
                'SUCCESS' => self::SUCCESS,
                default => self::from(strtoupper($status)) ?? self::PENDING,
            },
            'subadq_b' => match(strtoupper($status)) {
                'DONE' => self::DONE,
                default => self::from(strtoupper($status)) ?? self::PENDING,
            },
            default => self::PENDING,
        };
    }
}

