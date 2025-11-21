<?php

namespace App\Models;

use App\Enums\StatusPixEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pix extends Model
{
    protected $table = 'pix';

    protected $fillable = [
        'user_id',
        'subacquirer_id',
        'transaction_id',
        'pix_id',
        'amount',
        'status',
        'payer_name',
        'payer_cpf',
        'payment_date',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
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

    public function getStatusAttribute(string $value): StatusPixEnum
    {
        return StatusPixEnum::from($value);
    }

    public function setStatusAttribute(StatusPixEnum $value): void
    {
        $this->attributes['status'] = $value->value;
    }
}
