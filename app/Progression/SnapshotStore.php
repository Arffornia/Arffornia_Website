<?php

namespace App\Progression;

use App\Models\ModelSnapshot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Published snapshots: the newest one is what the game, the site and the stats run on. */
class SnapshotStore
{
    private const PUBLISH_LOCK = 17017;

    /** @var array<string, Snapshot> */
    private array $decoded = [];

    public function latest(): ?ModelSnapshot
    {
        return ModelSnapshot::where('status', ModelSnapshot::STATUS_PUBLISHED)->orderByDesc('version')->first();
    }

    public function find(int $version): ?ModelSnapshot
    {
        return ModelSnapshot::where('version', $version)->first();
    }

    /**
     * The current snapshot, read as version and etag only; the document itself is decoded once per
     * version and kept in the app cache (a frozen document never changes, the etag guards a reset database).
     */
    public function current(): ?Snapshot
    {
        $row = ModelSnapshot::where('status', ModelSnapshot::STATUS_PUBLISHED)->orderByDesc('version')->first(['version', 'etag']);
        if ($row === null) {
            return null;
        }
        $cacheKey = "progression-snapshot:{$row->version}:{$row->etag}";

        return $this->decoded[$cacheKey] ??= new Snapshot($row->version, Cache::rememberForever($cacheKey, fn () => json_decode(
            ModelSnapshot::where('version', $row->version)->value('document'), true, 512, JSON_THROW_ON_ERROR
        )));
    }

    /** Freezes the document as the next version; the previous published one is archived. */
    public function publish(array $document, ?int $userId, ?string $notes): ModelSnapshot
    {
        return DB::transaction(function () use ($document, $userId, $notes) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('SELECT pg_advisory_xact_lock(?)', [self::PUBLISH_LOCK]);
            }
            $version = ((int) ModelSnapshot::max('version')) + 1;
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

    public function firstPublishedAt(): ?string
    {
        return ModelSnapshot::min('published_at');
    }
}
