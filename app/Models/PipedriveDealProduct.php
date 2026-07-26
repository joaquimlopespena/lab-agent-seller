<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PipedriveDealProduct extends Pivot
{
    protected $table = 'pipedrive_deal_products';

    protected $fillable = [
        'pipedrive_deal_id',
        'pipedrive_product_id',
        'quantity',
        'unit_price',
        'discount_percentage',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $dealProduct): void {
            $dealCompanyId = $dealProduct->deal()->value('company_id');
            $productCompanyId = $dealProduct->product()->value('company_id');

            if ($dealCompanyId === null || $productCompanyId === null || (int) $dealCompanyId !== (int) $productCompanyId) {
                throw new DomainException('Deal and product must belong to the same company.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(PipedriveDeal::class, 'pipedrive_deal_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PipedriveProduct::class, 'pipedrive_product_id');
    }
}
