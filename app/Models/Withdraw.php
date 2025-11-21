<?php

namespace App\Models;

use App\Enums\StatusWithdrawEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdraw extends Model
{
    protected $fillable = [
        'user_id',
        'subacquirer_id',
        'withdraw_id',
        'transaction_id',
        'amount',
        'status',
        'completed_at',
        'bank_account',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'bank_account' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subacquirer(): BelongsTo
    {
        return $this->belongsTo(Subacquirer::class);
    }

    public function getStatusAttribute(string $value): StatusWithdrawEnum
    {
        return StatusWithdrawEnum::from($value);
    }

    public function setStatusAttribute(StatusWithdrawEnum $value): void
    {
        $this->attributes['status'] = $value->value;
    }
}
