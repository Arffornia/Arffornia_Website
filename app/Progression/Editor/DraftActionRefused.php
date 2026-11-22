<?php

namespace App\Progression\Editor;

/** An editor action that cannot apply to the draft as it stands; `reason` is a key of lang/{en,fr}/api.php `progression_editor`. */
final class DraftActionRefused extends \RuntimeException
{
    public function __construct(public readonly string $reason, public readonly array $params = [])
    {
        parent::__construct($reason);
    }
}
