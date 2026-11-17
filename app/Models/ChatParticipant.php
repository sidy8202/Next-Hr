<?php
namespace App\Models;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ChatParticipant extends Model {
    use BelongsToCompany;
    protected $guarded = ['id', 'company_id'];
    protected $table = 'chat_conversation_participants';
    protected function casts(): array { return ['last_read_at' => 'datetime']; }
    public function conversation(): BelongsTo { return $this->belongsTo(ChatConversation::class, 'conversation_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
