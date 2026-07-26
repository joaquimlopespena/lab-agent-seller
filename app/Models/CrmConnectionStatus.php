<?php

namespace App\Models;

use Database\Factories\CrmConnectionStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_active'])]
class CrmConnectionStatus extends Model
{
    public const Active = 'active';

    public const Expired = 'expired';

    public const Failed = 'failed';

    public const Pending = 'pending';

    public const Revoked = 'revoked';

    /** @use HasFactory<CrmConnectionStatusFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function connections(): HasMany
    {
        return $this->hasMany(CrmConnection::class, 'status_id');
    }
}
