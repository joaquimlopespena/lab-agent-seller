<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lookups = [
            'crm_connection_statuses' => ['pending' => 'Pending', 'active' => 'Active', 'failed' => 'Failed', 'revoked' => 'Revoked', 'expired' => 'Expired'],
            'crm_sync_statuses' => ['pending' => 'Pending', 'running' => 'Running', 'succeeded' => 'Succeeded', 'failed' => 'Failed'],
            'conversation_statuses' => ['active' => 'Active'],
            'message_roles' => ['user' => 'User', 'assistant' => 'Assistant', 'system' => 'System'],
        ];

        foreach ($lookups as $table => $values) {
            foreach ($values as $slug => $name) {
                $timestamp = now();

                DB::table($table)->insertOrIgnore([
                    'slug' => $slug,
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                DB::table($table)
                    ->where('slug', $slug)
                    ->update([
                        'name' => $name,
                        'is_active' => true,
                        'updated_at' => $timestamp,
                    ]);
            }
        }
    }
}
