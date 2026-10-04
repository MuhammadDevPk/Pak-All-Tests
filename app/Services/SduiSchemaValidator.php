<?php

namespace App\Services;

class SduiSchemaValidator
{
    /**
     * List of registered Vue components in componentRegistry.js
     */
    public const REGISTERED_COMPONENTS = [
        'EmptyStateLayout',
        'TabbedLayout',
        'SubjectGrid',
        'PhaseStepperLayout',
        'ResourceList',
        'VerticalStack',
        'McqPlayer',
        'StudyQaPlayer',
        'PdfViewer',
        'SubjectiveUpload',
        'ProgressCard',
    ];

    /**
     * Known common key typos and their correct replacements.
     */
    public const TYPO_MAP = [
        'prop' => 'props',
        'types' => 'type',
        'module_id' => 'moduleId',
        'tab' => 'tabs',
        'phase' => 'phases',
        'item' => 'items',
        'child' => 'children',
    ];

    /**
     * Validate an SDUI JSON array schema and return an array of errors/warnings.
     *
     * @param  array<string, mixed>  $schema
     * @return array{valid: bool, errors: string[], warnings: string[], node_count: int, max_depth: int}
     */
    public function validate(array $schema): array
    {
        $errors = [];
        $warnings = [];
        $nodeCount = 0;
        $maxDepth = 0;

        $this->inspectNode($schema, 1, $errors, $warnings, $nodeCount, $maxDepth);

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
            'node_count' => $nodeCount,
            'max_depth' => $maxDepth,
        ];
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     */
    private function inspectNode(mixed $node, int $depth, array &$errors, array &$warnings, int &$nodeCount, int &$maxDepth): void
    {
        if (! is_array($node)) {
            $errors[] = "Node at depth {$depth} is not a valid JSON object.";

            return;
        }

        $nodeCount++;
        if ($depth > $maxDepth) {
            $maxDepth = $depth;
        }

        // Check for common key typos at current node level
        foreach (self::TYPO_MAP as $typo => $correct) {
            if (array_key_exists($typo, $node)) {
                $errors[] = "Typo detected at depth {$depth}: Found key '{$typo}' which should be '{$correct}'.";
            }
        }

        // Validate mandatory 'type' attribute
        if (! isset($node['type']) || ! is_string($node['type'])) {
            $errors[] = "Node at depth {$depth} is missing mandatory 'type' string property.";
        } else {
            $type = $node['type'];
            if (! in_array($type, self::REGISTERED_COMPONENTS, true)) {
                $errors[] = "Unknown component type '{$type}' at depth {$depth}. Must be one of: ".implode(', ', self::REGISTERED_COMPONENTS);
            }
        }

        // Validate 'props' object
        if (isset($node['props'])) {
            if (! is_array($node['props'])) {
                $errors[] = "Node at depth {$depth} has 'props' defined, but it is not an object/array.";
            } else {
                // Check typos inside props
                foreach (self::TYPO_MAP as $typo => $correct) {
                    if (array_key_exists($typo, $node['props'])) {
                        $errors[] = "Typo detected in 'props' at depth {$depth}: Found key '{$typo}' which should be '{$correct}'.";
                    }
                }
            }
        }

        $type = $node['type'] ?? '';
        $props = $node['props'] ?? [];

        if (is_array($props) && array_key_exists('moduleQuery', $props)) {
            $this->validateModuleQuery($props['moduleQuery'], $depth, $errors);
        }

        // Type-specific schema validations
        switch ($type) {
            case 'TabbedLayout':
                if (! isset($props['tabs']) || ! is_array($props['tabs'])) {
                    $errors[] = "Component 'TabbedLayout' at depth {$depth} requires 'props.tabs' array.";
                } else {
                    foreach ($props['tabs'] as $i => $tab) {
                        if (! is_array($tab) || ! isset($tab['id'], $tab['label'])) {
                            $errors[] = "'TabbedLayout' tab #{$i} at depth {$depth} must be an object with 'id' and 'label'.";
                        }
                        if (isset($tab['content']) && is_array($tab['content'])) {
                            $this->inspectNode($tab['content'], $depth + 1, $errors, $warnings, $nodeCount, $maxDepth);
                        }
                    }
                }
                break;

            case 'PhaseStepperLayout':
                if (! isset($props['phases']) || ! is_array($props['phases'])) {
                    $errors[] = "Component 'PhaseStepperLayout' at depth {$depth} requires 'props.phases' array.";
                } else {
                    foreach ($props['phases'] as $i => $phase) {
                        if (! is_array($phase) || ! isset($phase['number'], $phase['title'])) {
                            $errors[] = "'PhaseStepperLayout' phase #{$i} at depth {$depth} must be an object with 'number' and 'title'.";
                        }
                        if (isset($phase['content']) && is_array($phase['content'])) {
                            $this->inspectNode($phase['content'], $depth + 1, $errors, $warnings, $nodeCount, $maxDepth);
                        }
                    }
                }
                break;

            case 'SubjectGrid':
                if (! isset($props['items']) && ! isset($props['moduleQuery'])) {
                    $errors[] = "Component 'SubjectGrid' at depth {$depth} requires either 'props.items' array or 'props.moduleQuery' configuration.";
                } elseif (isset($props['items']) && is_array($props['items'])) {
                    foreach ($props['items'] as $i => $item) {
                        if (is_array($item)) {
                            // Check item node if recursive component or module item
                            if (isset($item['type'])) {
                                $this->inspectNode($item, $depth + 1, $errors, $warnings, $nodeCount, $maxDepth);
                            } elseif (! isset($item['moduleId']) && ! isset($item['title'])) {
                                $warnings[] = "SubjectGrid item #{$i} at depth {$depth} has neither 'moduleId' nor 'title'.";
                            }
                        }
                    }
                }
                break;

            case 'ResourceList':
                if (! isset($props['items']) && ! isset($props['moduleQuery'])) {
                    $errors[] = "Component 'ResourceList' at depth {$depth} requires either 'props.items' array or 'props.moduleQuery' configuration.";
                }
                break;

            case 'VerticalStack':
                if (! isset($props['children']) || ! is_array($props['children'])) {
                    $errors[] = "Component 'VerticalStack' at depth {$depth} requires 'props.children' array.";
                } else {
                    foreach ($props['children'] as $i => $child) {
                        $this->inspectNode($child, $depth + 1, $errors, $warnings, $nodeCount, $maxDepth);
                    }
                }
                break;

            case 'McqPlayer':
            case 'StudyQaPlayer':
            case 'PdfViewer':
            case 'SubjectiveUpload':
                if (! isset($node['moduleId']) && ! isset($props['moduleId'])) {
                    $warnings[] = "Interactive component '{$type}' at depth {$depth} should specify 'moduleId' to bind to content module.";
                }
                break;
        }
    }

    /**
     * @param  list<string>  $errors
     */
    private function validateModuleQuery(mixed $query, int $depth, array &$errors): void
    {
        if (! is_array($query)) {
            $errors[] = "'moduleQuery' at depth {$depth} must be an object/array.";

            return;
        }

        $allowedKeys = ['module_type', 'dashboard_section', 'display_mode', 'slug_contains', 'active', 'limit', 'merge'];
        foreach ($query as $key => $value) {
            if (! in_array($key, $allowedKeys, true)) {
                $errors[] = "Unsupported moduleQuery key '{$key}' at depth {$depth}.";
            }
        }

        foreach (['module_type', 'dashboard_section', 'display_mode', 'slug_contains'] as $key) {
            if (array_key_exists($key, $query) && (! is_string($query[$key]) || trim($query[$key]) === '')) {
                $errors[] = "moduleQuery '{$key}' at depth {$depth} must be a non-empty string.";
            }
        }

        if (array_key_exists('active', $query) && ! is_bool($query['active'])) {
            $errors[] = "moduleQuery 'active' at depth {$depth} must be boolean.";
        }

        if (array_key_exists('limit', $query) && (! is_int($query['limit']) || $query['limit'] <= 0)) {
            $errors[] = "moduleQuery 'limit' at depth {$depth} must be a positive integer.";
        }

        if (array_key_exists('merge', $query) && ! in_array($query['merge'], ['replace', 'append', 'prepend'], true)) {
            $errors[] = "moduleQuery 'merge' at depth {$depth} must be one of: replace, append, prepend.";
        }
    }
}
