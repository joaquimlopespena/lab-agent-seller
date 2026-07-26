<?php

namespace App\Models;

use Database\Factories\PipedriveActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'crm_connection_id', 'pipedrive_deal_id', 'pipedrive_person_id', 'pipedrive_organization_id', 'owner_pipedrive_user_id', 'pipedrive_id', 'subject', 'due_at', 'is_done', 'raw_payload', 'synced_at'])]
class PipedriveActivity extends PipedriveModel
{
    /** @use HasFactory<PipedriveActivityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'is_done' => 'boolean',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PipedriveDeal, $this> */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(PipedriveDeal::class, 'pipedrive_deal_id');
    }

    /** @return BelongsTo<PipedrivePerson, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(PipedrivePerson::class, 'pipedrive_person_id');
    }

    /** @return BelongsTo<PipedriveOrganization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(PipedriveOrganization::class, 'pipedrive_organization_id');
    }

    /** @return BelongsTo<PipedriveUser, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(PipedriveUser::class, 'owner_pipedrive_user_id');
    }

    /** @return list<string> */
    protected function tenantRelationships(): array
    {
        return [...parent::tenantRelationships(), 'deal', 'person', 'organization', 'owner'];
    }
}
