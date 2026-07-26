<?php

namespace App\Models;

use Database\Factories\CustomerSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[WithoutIncrementing]
#[Fillable(['id', 'customer_identity_id', 'active_company_id', 'token_hash', 'ip_address', 'user_agent', 'expires_at', 'last_activity_at', 'revoked_at'])]
#[Hidden(['token_hash'])]
class CustomerSession extends Model
{
    /** @use HasFactory<CustomerSessionFactory> */
    use HasFactory;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function customerIdentity(): BelongsTo
    {
        return $this->belongsTo(CustomerIdentity::class);
    }

    public function activeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'active_company_id');
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
