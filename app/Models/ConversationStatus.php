<?php

namespace App\Models;

use Database\Factories\ConversationStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_active'])]
class ConversationStatus extends Model
{
    /** @use HasFactory<ConversationStatusFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AgentConversation::class, 'status_id');
    }
}
