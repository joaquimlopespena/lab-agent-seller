<?php

namespace App\Models;

use Database\Factories\CustomerMagicLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $customer_identity_id
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property-read CustomerIdentity $customerIdentity
 */
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

    /** @return BelongsTo<CustomerIdentity, $this> */
    public function customerIdentity(): BelongsTo
    {
        return $this->belongsTo(CustomerIdentity::class);
    }
}
