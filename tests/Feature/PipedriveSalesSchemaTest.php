<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pipedrive sales tables match monetary precision and relationship indexes', function () {
    expect(column('pipedrive_deals', 'value')['type_name'])->toBe('numeric')
        ->and(column('pipedrive_products', 'unit_price')['type_name'])->toBe('numeric')
        ->and(column('pipedrive_deal_products', 'quantity')['type'])->toContain('15,4')
        ->and(column('pipedrive_deal_products', 'discount_percentage')['type'])->toContain('5,2')
        ->and(uniqueIndexColumns('pipedrive_deal_products'))->toContain(['pipedrive_deal_id', 'pipedrive_product_id'])
        ->and(indexColumns('pipedrive_deals'))->toContain(['company_id', 'pipedrive_pipeline_id', 'pipedrive_stage_id'])
        ->and(uniqueIndexColumns('pipedrive_activities'))->toContain(['company_id', 'pipedrive_id']);
});
