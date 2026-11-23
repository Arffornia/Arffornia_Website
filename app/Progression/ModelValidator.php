<?php

namespace App\Progression;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Checks a snapshot document: the JSON Schema of spec 017 (vendored from Arffornia/docs
 * docs/specs/017-progression-model/progression-model.schema.json, refresh it when the schema changes),
 * then the rules a schema cannot express, ported from docs/scripts/progression.py `lint`.
 */
class ModelValidator
{
    public const SCHEMA_ID = 'https://arffornia.github.io/docs/specs/017-progression-model/progression-model.schema.json';

    private const PLACEHOLDER_DESCRIPTION = 'A new adventure begins...';

    /** @return list<Finding> schema violations, each an error */
    public function schemaErrors(array $document): array
    {
        $validator = new Validator();
        $validator->setMaxErrors(50);
        $validator->resolver()->registerFile(self::SCHEMA_ID, resource_path('schemas/progression-model.schema.json'));

        $result = $validator->validate(json_decode(json_encode($document, JSON_THROW_ON_ERROR)), self::SCHEMA_ID);
        if ($result->isValid()) {
            return [];
        }

        $findings = [];
        foreach ((new ErrorFormatter())->format($result->error(), true) as $pointer => $messages) {
            foreach ((array) $messages as $message) {
                $findings[] = new Finding('error', 'schema', $pointer === '' ? '/' : $pointer, $message);
            }
        }

        return $findings;
    }

    /** @return list<Finding> */
    public function ruleFindings(array $document): array
    {
        $findings = [];
        $stages = array_column($document['stages'], 'number');
        $graphs = array_column($document['graphs'], 'key');
        $byKey = [];
        foreach ($document['milestones'] as $milestone) {
            if (isset($byKey[$milestone['key']])) {
                $findings[] = new Finding('error', 'duplicate_key', $milestone['key'], 'two milestones share this key');
            }
            $byKey[$milestone['key']] = $milestone;
        }

        $perStage = [];
        foreach ($document['milestones'] as $milestone) {
            $key = $milestone['key'];
            $perStage[$milestone['stage']] = ($perStage[$milestone['stage']] ?? 0) + 1;
            if (!in_array($milestone['stage'], $stages, true)) {
                $findings[] = new Finding('error', 'unknown_stage', $key, "stage {$milestone['stage']} does not exist");
            }
            if (!in_array($milestone['graph'], $graphs, true)) {
                $findings[] = new Finding('error', 'unknown_graph', $key, "graph {$milestone['graph']} does not exist");
            }
            if ($milestone['validation'] === null) {
                $findings[] = new Finding('warning', 'no_validation', $key, 'nothing to deliver: no player can complete this milestone');
            }
            $description = $milestone['description']['en'] ?? '';
            if ($description === '' || $description === self::PLACEHOLDER_DESCRIPTION) {
                $findings[] = new Finding('info', 'placeholder_description', $key, 'description is empty or the placeholder');
            }
            foreach ($milestone['requires'] as $required) {
                $parent = $byKey[$required] ?? null;
                if ($parent === null) {
                    $findings[] = new Finding('error', 'unknown_requirement', $key, "requires {$required}, which does not exist");
                    continue;
                }
                if ($parent['stage'] > $milestone['stage']) {
                    $findings[] = new Finding('error', 'requires_later_stage', $key, "stage {$milestone['stage']} requires {$required} from stage {$parent['stage']}");
                }
                if ($parent['graph'] !== $milestone['graph']) {
                    $findings[] = new Finding('info', 'cross_graph', $key, "requires {$required} from another graph");
                }
            }
        }

        array_push($findings, ...$this->cycles($byKey));

        foreach ($stages as $number) {
            if (($perStage[$number] ?? 0) === 0) {
                $findings[] = new Finding('warning', 'empty_stage', "stage {$number}", 'no milestone on this stage');
            }
        }

        return $findings;
    }

    /** @return list<Finding> */
    public function validate(array $document): array
    {
        $schemaErrors = $this->schemaErrors($document);

        return $schemaErrors !== [] ? $schemaErrors : $this->ruleFindings($document);
    }

    /** @return list<Finding> */
    private function cycles(array $byKey): array
    {
        $findings = [];
        $state = [];
        foreach (array_keys($byKey) as $start) {
            if (isset($state[$start])) {
                continue;
            }
            $stack = [[$start, 0]];
            $path = [$start];
            $state[$start] = 'open';
            while ($stack !== []) {
                [$key, $index] = $stack[array_key_last($stack)];
                $requires = $byKey[$key]['requires'];
                if ($index >= count($requires)) {
                    $state[$key] = 'done';
                    array_pop($stack);
                    array_pop($path);
                    continue;
                }
                $stack[array_key_last($stack)][1]++;
                $required = $requires[$index];
                if (!isset($byKey[$required])) {
                    continue;
                }
                if (($state[$required] ?? null) === 'open') {
                    $from = array_search($required, $path, true);
                    $cycle = array_merge(array_slice($path, $from === false ? 0 : $from), [$required]);
                    $findings[] = new Finding('error', 'cycle', $key, implode(' -> ', $cycle));
                } elseif (!isset($state[$required])) {
                    $state[$required] = 'open';
                    $stack[] = [$required, 0];
                    $path[] = $required;
                }
            }
        }

        return $findings;
    }
}
