<?php

namespace App\Progression;

use App\Models\ModelSnapshot;
use Illuminate\Support\Facades\DB;

/** Published snapshots: the newest one is what the game, the site and the stats run on. */
class SnapshotStore
{
    /** @var array<int, Snapshot> */
    private array $decoded = [];

    public function latest(): ?ModelSnapshot
    {
        return ModelSnapshot::where('status', ModelSnapshot::STATUS_PUBLISHED)->orderByDesc('version')->first();
    }

    public function find(int $version): ?ModelSnapshot
    {
        return ModelSnapshot::where('version', $version)->first();
    }

    public function current(): ?Snapshot
    {
        $row = ModelSnapshot::where('status', ModelSnapshot::STATUS_PUBLISHED)->orderByDesc('version')->first(['id', 'version', 'document']);
        if ($row === null) {
            return null;
        }

        return $this->decoded[$row->version] ??= new Snapshot($row->version, json_decode($row->document, true, 512, JSON_THROW_ON_ERROR));
    }

    /** Freezes the document as the next version; the previous published one is archived. */
    public function publish(array $document, ?int $userId, ?string $notes): ModelSnapshot
    {
        return DB::transaction(function () use ($document, $userId, $notes) {
            // Postgres refuses FOR UPDATE with an aggregate; the unique version index settles a race on the first publish.
            $version = ((int) ModelSnapshot::orderByDesc('version')->lockForUpdate()->value('version')) + 1;
            $document['version'] = $version;
            $document['status'] = 'published';
            $json = json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            ModelSnapshot::where('status', ModelSnapshot::STATUS_PUBLISHED)->update(['status' => ModelSnapshot::STATUS_ARCHIVED]);

            return ModelSnapshot::create([
                'version' => $version,
                'status' => ModelSnapshot::STATUS_PUBLISHED,
                'schema_version' => $document['schema_version'],
                'document' => $json,
                'etag' => hash('sha256', $json),
                'published_at' => now(),
                'published_by' => $userId,
                'notes' => $notes,
            ]);
        });
    }
}
