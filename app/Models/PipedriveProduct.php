<?php

namespace App\Models;

use Database\Factories\PipedriveProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['company_id', 'crm_connection_id', 'pipedrive_id', 'name', 'code', 'unit_price', 'currency', 'is_active', 'raw_payload', 'synced_at'])]
class PipedriveProduct extends PipedriveModel
{
    /** @use HasFactory<PipedriveProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'is_active' => 'boolean',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(PipedriveDeal::class, 'pipedrive_deal_products')
            ->using(PipedriveDealProduct::class)
            ->withPivot(['quantity', 'unit_price', 'discount_percentage'])
            ->withTimestamps();
    }
}
