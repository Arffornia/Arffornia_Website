<?php

namespace App\Progression\Editor;

use App\Progression\Finding;

/** A publish that did not happen; nothing was written. `reason` is a key of api.progression_editor. */
final class PublishRefused extends \RuntimeException
{
    /** @param list<Finding> $findings */
    public function __construct(public readonly string $reason, public readonly array $findings)
    {
        parent::__construct($reason);
    }
}
