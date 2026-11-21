<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A frozen progression model (spec 017). `document` is the exact JSON served by
 * GET /api/progression/model; `etag` is its sha256.
 *
 * @property int $id
 * @property int $version
 * @property string $status
 * @property int $schema_version
 * @property string $document
 * @property string $etag
 */
class ModelSnapshot extends Model
{
    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = ['version', 'status', 'schema_version', 'document', 'etag', 'published_at', 'published_by', 'notes'];

    protected $casts = ['published_at' => 'datetime'];
}
