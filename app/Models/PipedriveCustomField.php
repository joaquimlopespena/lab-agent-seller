<?php

namespace App\Models;

use Database\Factories\PipedriveCustomFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['company_id', 'crm_connection_id', 'pipedrive_id', 'pipedrive_key', 'entity_type', 'field_type', 'name', 'is_active', 'options_payload', 'raw_payload', 'synced_at'])]
class PipedriveCustomField extends PipedriveModel
{
    /** @use HasFactory<PipedriveCustomFieldFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'options_payload' => 'array',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
