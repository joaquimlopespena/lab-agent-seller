<?php

namespace App\Models;

use Database\Factories\MessageRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_active'])]
class MessageRole extends Model
{
    /** @use HasFactory<MessageRoleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentConversationMessage::class, 'message_role_id');
    }
}
