<?php
namespace App\Models;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class ChatConversation extends Model {
    use BelongsToCompany;
    protected $guarded = ['id', 'company_id'];
    public function participants(): HasMany { return $this->hasMany(ChatParticipant::class, 'conversation_id'); }
    public function messages(): HasMany { return $this->hasMany(ChatMessage::class, 'conversation_id'); }
    public function latestMessage(): HasOne { return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany(); }
}
