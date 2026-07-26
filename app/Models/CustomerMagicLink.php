<?php

namespace App\Models;

use Database\Factories\CustomerMagicLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_identity_id', 'token_hash', 'expires_at', 'used_at'])]
#[Hidden(['token_hash'])]
class CustomerMagicLink extends Model
{
    /** @use HasFactory<CustomerMagicLinkFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function customerIdentity(): BelongsTo
    {
        return $this->belongsTo(CustomerIdentity::class);
    }
}
