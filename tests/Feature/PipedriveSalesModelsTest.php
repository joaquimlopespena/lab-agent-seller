<?php

use App\Models\PipedriveActivity;
use App\Models\PipedriveDeal;
use App\Models\PipedriveProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sales models expose tenant safe optional and pivot relationships with casts', function () {
    $deal = PipedriveDeal::factory()->withSalesStructure()->withContact()->withOwner()->create();
    $product = PipedriveProduct::factory()->create([
        'company_id' => $deal->company_id,
        'crm_connection_id' => $deal->crm_connection_id,
    ]);
    $deal->products()->attach($product, [
        'quantity' => 2.5,
        'unit_price' => 19.9,
        'discount_percentage' => 5,
    ]);
    $activity = PipedriveActivity::factory()->withRelations()->create();

    expect($deal->products->first()->is($product))->toBeTrue()
        ->and($deal->products->first()->pivot->quantity)->toBe('2.5000')
        ->and($product->deals->first()->is($deal))->toBeTrue()
        ->and($deal->value)->toMatch('/^\d+\.\d{2}$/')
        ->and($deal->raw_payload)->toBeArray()
        ->and($activity->is_done)->toBeBool()
        ->and($activity->due_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($activity->company_id)->toBe($activity->deal->company_id);
});

test('a product from another tenant cannot be attached to a deal', function () {
    $deal = PipedriveDeal::factory()->create();
    $foreignProduct = PipedriveProduct::factory()->create();

    $deal->products()->attach($foreignProduct);
})->throws(DomainException::class);
