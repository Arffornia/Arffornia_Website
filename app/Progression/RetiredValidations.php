<?php

namespace App\Progression;

/**
 * Snapshots and editor drafts saved before docs ADR 0017 may hold `obtain` or `craft` validations, which the schema
 * now refuses. Read as "nothing to deliver" so an old snapshot can still be rolled back to or reopened.
 */
final class RetiredValidations
{
    public static function strip(array $document): array
    {
        foreach ($document['milestones'] ?? [] as $i => $milestone) {
            if (is_array($milestone) && is_array($milestone['validation'] ?? null) && ($milestone['validation']['type'] ?? null) !== 'deliver') {
                $document['milestones'][$i]['validation'] = null;
            }
        }

        return $document;
    }
}
