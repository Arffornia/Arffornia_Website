<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a draft's history (spec 019). `type` is open, edit, undo, redo, discard or publish. An
 * edit's `state` is applied, undone, or dropped (undone, then overtaken by a new edit: no redo).
 *
 * @property int $id
 * @property int $draft_id
 * @property ?int $user_id
 * @property string $type
 * @property ?string $action
 * @property ?string $subject
 * @property ?array $changes
 * @property ?string $state
 * @property ?int $target_id
 * @property int $revision
 */
class ProgressionDraftEntry extends Model
{
    public const UPDATED_AT = null;

    public const APPLIED = 'applied';

    public const UNDONE = 'undone';

    public const DROPPED = 'dropped';

    protected $fillable = ['draft_id', 'user_id', 'type', 'action', 'subject', 'changes', 'state', 'target_id', 'revision'];

    protected $casts = ['changes' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
