<?php

namespace App\Filament\Resources\DomainFlows\Support;

/**
 * UiSchemaCompiler
 *
 * Converts between Filament Repeater form state and the canonical SDUI
 * JSON structure stored in domain_flows.ui_schema.
 *
 * compile()   → form state array  ➜  SDUI JSON array
 * decompile() → SDUI JSON array   ➜  form state array
 */
class UiSchemaCompiler
{
    // ─── Public API ────────────────────────────────────────────────────────

    /**
     * Compile Filament Repeater state → canonical SDUI JSON.
     *
     * @param  array<string, mixed>  $builderState
     * @return array<string, mixed>
     */
    public static function compile(array $builderState): array
    {
        $phases = [];

        foreach (($builderState['phases'] ?? []) as $index => $phase) {
            $phases[] = [
                'number' => $index + 1,
                'title' => $phase['title'] ?? '',
                'subtitle' => $phase['subtitle'] ?? '',
                'content' => self::compileContentNode($phase),
            ];
        }

        return [
            'type' => 'PhaseStepperLayout',
            'props' => [
                'currentPhase' => 1,
                'phases' => $phases,
            ],
        ];
    }

    /**
     * Decompile canonical SDUI JSON → Filament Repeater state.
     *
     * @param  array<string, mixed>  $uiSchema
     * @return array<string, mixed>
     */
    public static function decompile(array $uiSchema): array
    {
        $phases = [];

        foreach ($uiSchema['props']['phases'] ?? [] as $phase) {
            $phases[] = array_merge(
                [
                    'title' => $phase['title'] ?? '',
                    'subtitle' => $phase['subtitle'] ?? '',
                ],
                self::decompileContentNode($phase['content'] ?? [])
            );
        }

        return ['phases' => $phases];
    }

    // ─── Content Node Compilation ───────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function compileContentNode(array $node): array
    {
        return match ($node['content_type'] ?? '') {
            'TabbedLayout' => [
                'type' => 'TabbedLayout',
                'props' => [
                    'defaultTab' => $node['default_tab'] ?? (($node['tabs'][0]['id'] ?? '') ?: ''),
                    'tabs' => array_values(array_map(
                        fn ($tab) => [
                            'id' => $tab['id'] ?? '',
                            'label' => $tab['label'] ?? '',
                            'badge' => $tab['badge'] ?? '',
                            'content' => self::compileContentNode($tab),
                        ],
                        $node['tabs'] ?? []
                    )),
                ],
            ],
            'SubjectGrid' => [
                'type' => 'SubjectGrid',
                'props' => self::compileGridProps($node),
            ],
            'ResourceList' => [
                'type' => 'ResourceList',
                'props' => self::compileResourceProps($node),
            ],
            'McqPlayer' => [
                'type' => 'McqPlayer',
                'props' => [
                    'moduleId' => (int) ($node['mcq_module_id'] ?? 0),
                ],
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function decompileContentNode(array $content): array
    {
        $type = $content['type'] ?? '';
        $props = $content['props'] ?? [];
        $base = ['content_type' => $type];

        return match ($type) {
            'TabbedLayout' => array_merge($base, [
                'default_tab' => $props['defaultTab'] ?? '',
                'tabs' => array_values(array_map(
                    fn ($tab) => array_merge(
                        [
                            'id' => $tab['id'] ?? '',
                            'label' => $tab['label'] ?? '',
                            'badge' => $tab['badge'] ?? '',
                        ],
                        self::decompileContentNode($tab['content'] ?? [])
                    ),
                    $props['tabs'] ?? []
                )),
                // grid / resource defaults (hidden but needed for clean state)
                'mcq_module_id' => '',
                'source_mode' => 'moduleQuery',
                'columns' => 2,
                'resource_title' => '',
                'module_type' => 'preparation',
                'slug_contains' => '',
                'merge' => 'replace',
                'manual_items' => [],
            ]),
            'SubjectGrid' => array_merge($base, self::decompileGridProps($props), [
                'default_tab' => '',
                'tabs' => [],
                'mcq_module_id' => '',
                'resource_title' => '',
            ]),
            'ResourceList' => array_merge($base, self::decompileResourceProps($props), [
                'default_tab' => '',
                'tabs' => [],
                'mcq_module_id' => '',
                'columns' => 2,
            ]),
            'McqPlayer' => array_merge($base, [
                'mcq_module_id' => (string) ($props['moduleId'] ?? ''),
                'default_tab' => '',
                'tabs' => [],
                'source_mode' => 'moduleQuery',
                'columns' => 2,
                'resource_title' => '',
                'module_type' => 'preparation',
                'slug_contains' => '',
                'merge' => 'replace',
                'manual_items' => [],
            ]),
            default => array_merge($base, [
                'default_tab' => '',
                'tabs' => [],
                'mcq_module_id' => '',
                'source_mode' => 'moduleQuery',
                'columns' => 2,
                'resource_title' => '',
                'module_type' => 'preparation',
                'slug_contains' => '',
                'merge' => 'replace',
                'manual_items' => [],
            ]),
        };
    }

    // ─── Grid / Resource Helpers ────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function compileGridProps(array $node): array
    {
        $props = ['columns' => (int) ($node['columns'] ?? 2)];

        if (($node['source_mode'] ?? 'moduleQuery') === 'moduleQuery') {
            $q = ['module_type' => $node['module_type'] ?? 'preparation'];
            if (! empty($node['slug_contains'])) {
                $q['slug_contains'] = $node['slug_contains'];
            }
            $q['merge'] = $node['merge'] ?? 'replace';
            $props['moduleQuery'] = $q;
        } else {
            $props['items'] = array_values(array_map(
                fn ($item) => array_filter([
                    'moduleId' => (int) ($item['module_id'] ?? 0),
                    'title' => $item['title'] ?: null,
                    'description' => $item['description'] ?: null,
                    'actionLabel' => $item['action_label'] ?: 'Start',
                ]),
                $node['manual_items'] ?? []
            ));
        }

        return $props;
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private static function decompileGridProps(array $props): array
    {
        if (isset($props['moduleQuery'])) {
            return [
                'source_mode' => 'moduleQuery',
                'columns' => $props['columns'] ?? 2,
                'module_type' => $props['moduleQuery']['module_type'] ?? 'preparation',
                'slug_contains' => $props['moduleQuery']['slug_contains'] ?? '',
                'merge' => $props['moduleQuery']['merge'] ?? 'replace',
                'manual_items' => [],
            ];
        }

        return [
            'source_mode' => 'manual',
            'columns' => $props['columns'] ?? 2,
            'module_type' => '',
            'slug_contains' => '',
            'merge' => 'replace',
            'manual_items' => array_values(array_map(
                fn ($item) => [
                    'module_id' => (string) ($item['moduleId'] ?? ''),
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? '',
                    'action_label' => $item['actionLabel'] ?? 'Start',
                ],
                $props['items'] ?? []
            )),
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function compileResourceProps(array $node): array
    {
        $props = [];

        if (! empty($node['resource_title'])) {
            $props['title'] = $node['resource_title'];
        }

        if (($node['source_mode'] ?? 'moduleQuery') === 'moduleQuery') {
            $q = ['module_type' => $node['module_type'] ?? 'pdf_repo'];
            if (! empty($node['slug_contains'])) {
                $q['slug_contains'] = $node['slug_contains'];
            }
            $q['merge'] = $node['merge'] ?? 'replace';
            $props['moduleQuery'] = $q;
        } else {
            $props['items'] = array_values(array_map(
                fn ($item) => array_filter([
                    'moduleId' => (int) ($item['module_id'] ?? 0),
                    'title' => $item['title'] ?: null,
                    'fileSize' => $item['file_size'] ?: null,
                    'fileType' => $item['file_type'] ?: null,
                    'actionLabel' => $item['action_label'] ?: 'Download',
                ]),
                $node['manual_items'] ?? []
            ));
        }

        return $props;
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private static function decompileResourceProps(array $props): array
    {
        $base = ['resource_title' => $props['title'] ?? ''];

        if (isset($props['moduleQuery'])) {
            return array_merge($base, [
                'source_mode' => 'moduleQuery',
                'module_type' => $props['moduleQuery']['module_type'] ?? 'pdf_repo',
                'slug_contains' => $props['moduleQuery']['slug_contains'] ?? '',
                'merge' => $props['moduleQuery']['merge'] ?? 'replace',
                'manual_items' => [],
            ]);
        }

        return array_merge($base, [
            'source_mode' => 'manual',
            'module_type' => '',
            'slug_contains' => '',
            'merge' => 'replace',
            'manual_items' => array_values(array_map(
                fn ($item) => [
                    'module_id' => (string) ($item['moduleId'] ?? ''),
                    'title' => $item['title'] ?? '',
                    'file_size' => $item['fileSize'] ?? '',
                    'file_type' => $item['fileType'] ?? '',
                    'action_label' => $item['actionLabel'] ?? 'Download',
                ],
                $props['items'] ?? []
            )),
        ]);
    }
}
