<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A draft of the progression model edited in the admin editor (spec 019). `document` follows
 * progression-model.schema.json; `revision` goes up on every change so two tabs cannot overwrite
 * each other; `base_hash` is the hash of the editable tables when the draft was opened.
 *
 * @property int $id
 * @property string $status
 * @property array $document
 * @property int $revision
 * @property ?int $base_version
 * @property string $base_hash
 * @property ?int $published_version
 * @property ?int $created_by
 */
class ProgressionDraft extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DISCARDED = 'discarded';

    protected $fillable = ['status', 'document', 'revision', 'base_version', 'base_hash', 'published_version', 'created_by'];

    protected $casts = ['document' => 'array'];

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
