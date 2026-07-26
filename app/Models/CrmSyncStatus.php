<?php

namespace App\Models;

use Database\Factories\CrmSyncStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_active'])]
class CrmSyncStatus extends Model
{
    public const Failed = 'failed';

    public const Pending = 'pending';

    public const Running = 'running';

    public const Succeeded = 'succeeded';

    /** @use HasFactory<CrmSyncStatusFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(CrmSyncRun::class, 'status_id');
    }
}
