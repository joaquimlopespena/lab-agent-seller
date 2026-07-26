<?php

use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('lookup seeding is idempotent and preserves canonical slugs', function () {
    $this->seed(LookupSeeder::class);
    $createdAt = DB::table('crm_connection_statuses')->where('slug', 'pending')->value('created_at');

    $this->travel(1)->day();
    $this->seed(LookupSeeder::class);

    $expected = [
        'crm_connection_statuses' => ['active', 'expired', 'failed', 'pending', 'revoked'],
        'crm_sync_statuses' => ['failed', 'pending', 'running', 'succeeded'],
        'conversation_statuses' => ['active'],
        'message_roles' => ['assistant', 'system', 'user'],
    ];

    foreach ($expected as $table => $slugs) {
        expect(DB::table($table)->orderBy('slug')->pluck('slug')->all())->toBe($slugs)
            ->and(DB::table($table)->count())->toBe(count($slugs));
    }

    expect(DB::table('crm_connection_statuses')->where('slug', 'pending')->value('created_at'))
        ->toBe($createdAt);
});
