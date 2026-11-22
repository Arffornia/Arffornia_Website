<?php

namespace App\Progression\Editor;

/** The draft changed since the editor loaded it (another tab or admin). */
final class DraftConflict extends \RuntimeException
{
    public function __construct(public readonly int $revision)
    {
        parent::__construct('draft revision conflict');
    }
}
