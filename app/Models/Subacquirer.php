<?php

namespace App\Models;

use App\Enums\SubacquirerTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subacquirer extends Model
{
    use HasFactory;

    protected $table = 'subacquirers';

    protected $fillable = [
        'name',
        'base_url',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pix(): HasMany
    {
        return $this->hasMany(Pix::class);
    }

    public function withdraws(): HasMany
    {
        return $this->hasMany(Withdraw::class);
    }
}
