<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Collection;

class SduiPayloadTransformer
{
    /**
     * Transform Domain and flow into a dynamic SDUI Inertia payload.
     *
     * @return array<string, mixed>
     */
    public function transform(Domain $domain, User $user): array
    {
        $flow = $domain->flow;

        $uiSchema = $flow !== null ? $flow->ui_schema : [
            'type' => 'EmptyStateLayout',
            'props' => [
                'title' => 'Domain Under Construction',
                'message' => 'The curriculum and modules for this exam are being populated by the academy.',
            ],
        ];

        /** @var Collection<int|string, array<string, mixed>> $modules */
        $modules = $domain->contentModules()
            ->where('is_active', true)
            ->with(['questions' => fn ($q) => $q->select('question_bank.id', 'question_bank.question_type')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('id')
            ->map(fn ($module): array => [
                'id' => $module->id,
                'type' => $module->module_type,
                'title' => $module->title,
                'slug' => $module->slug,
                'dashboard_section' => $module->dashboard_section,
                'display_mode' => $module->resolveDisplayMode(),
                'sort_order' => $module->sort_order,
                'content_meta' => $module->content_meta ?? [],
            ]);

        return [
            'layout' => $this->hydrateSchemaNodes($uiSchema, $modules),
            'meta' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'domain' => [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'slug' => $domain->slug,
                    'path' => $domain->path,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  Collection<int|string, array<string, mixed>>  $modules
     * @return array<string, mixed>
     */
    private function hydrateSchemaNodes(array $node, Collection $modules): array
    {
        $props = is_array($node['props'] ?? null) ? $node['props'] : [];

        if (is_array($props['moduleQuery'] ?? null)) {
            $query = $props['moduleQuery'];
            $queryModules = $this->resolveModuleQuery($query, $modules);
            $dynamicItems = $queryModules->map(fn (array $module): array => $this->moduleToItem($module))->values()->all();
            $manualItems = is_array($props['items'] ?? null) ? $props['items'] : [];
            $props['items'] = $this->mergeItems($manualItems, $dynamicItems, $query['merge'] ?? 'replace');
        }

        if (isset($node['moduleId']) && $modules->has($node['moduleId'])) {
            $props['module'] = $modules->get($node['moduleId']);
        }

        if (is_array($props['items'] ?? null)) {
            foreach ($props['items'] as $index => $item) {
                if (is_array($item) && isset($item['type'])) {
                    $props['items'][$index] = $this->hydrateSchemaNodes($item, $modules);
                }
            }
        }

        foreach (['tabs', 'phases'] as $container) {
            if (! is_array($props[$container] ?? null)) {
                continue;
            }

            foreach ($props[$container] as $index => $entry) {
                if (is_array($entry) && is_array($entry['content'] ?? null)) {
                    $props[$container][$index]['content'] = $this->hydrateSchemaNodes($entry['content'], $modules);
                }
            }
        }

        if (is_array($props['children'] ?? null)) {
            foreach ($props['children'] as $index => $child) {
                if (is_array($child)) {
                    $props['children'][$index] = $this->hydrateSchemaNodes($child, $modules);
                }
            }
        }

        $node['props'] = $props;

        return $node;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  Collection<int|string, array<string, mixed>>  $modules
     * @return Collection<int, array<string, mixed>>
     */
    private function resolveModuleQuery(array $query, Collection $modules): Collection
    {
        $filtered = $modules;

        // The transformer receives active modules by default. Supporting active=false
        // is explicit and useful for administrative previews, while production callers
        // should omit it to preserve the safe default.
        if (($query['active'] ?? true) === false) {
            // Inactive modules are intentionally unavailable because they were excluded
            // at the source query. This prevents the schema from bypassing visibility.
            return collect();
        }

        if (isset($query['module_type']) && is_string($query['module_type'])) {
            $filtered = $filtered->where('type', $query['module_type']);
        }

        if (isset($query['dashboard_section']) && is_string($query['dashboard_section'])) {
            $filtered = $filtered->where('dashboard_section', $query['dashboard_section']);
        }

        if (isset($query['display_mode']) && is_string($query['display_mode'])) {
            $filtered = $filtered->where('display_mode', $query['display_mode']);
        }

        if (isset($query['slug_contains']) && is_string($query['slug_contains'])) {
            $search = mb_strtolower($query['slug_contains']);
            $filtered = $filtered->filter(
                fn (array $module): bool => str_contains(mb_strtolower((string) ($module['slug'] ?? '')), $search)
            );
        }

        if (isset($query['limit']) && is_numeric($query['limit']) && (int) $query['limit'] > 0) {
            $filtered = $filtered->take((int) $query['limit']);
        }

        return $filtered->values();
    }

    /**
     * @param  array<string, mixed>  $module
     * @return array<string, mixed>
     */
    private function moduleToItem(array $module): array
    {
        $meta = is_array($module['content_meta'] ?? null) ? $module['content_meta'] : [];
        $isPdf = $module['type'] === 'pdf_repo';
        $displayMode = (string) ($module['display_mode'] ?? 'mcq_test');
        $files = is_array($meta['files'] ?? null) ? $meta['files'] : [];

        $actionLabel = match ($displayMode) {
            'pdf_repo' => 'View PDF',
            'study_qa' => 'View Details & Q&A',
            'reading_guide' => 'Read Guidelines',
            'essay' => 'View Essay Prompt',
            default => $isPdf ? 'View PDF' : 'Start Practice',
        };

        return array_filter([
            'moduleId' => $module['id'],
            'title' => $module['title'],
            'description' => $meta['description'] ?? '',
            'durationMinutes' => $meta['duration_minutes'] ?? null,
            'dashboardSection' => $module['dashboard_section'] ?? null,
            'displayMode' => $displayMode,
            'actionLabel' => $actionLabel,
            'fileType' => $meta['file_type'] ?? null,
            'fileSize' => $meta['file_size'] ?? null,
            'fileUrl' => $meta['file_url'] ?? null,
            'files' => count($files) > 0 ? $files : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<int, mixed>  $manualItems
     * @param  array<int, array<string, mixed>>  $dynamicItems
     * @return array<int, mixed>
     */
    private function mergeItems(array $manualItems, array $dynamicItems, string $merge): array
    {
        if (! in_array($merge, ['replace', 'append', 'prepend'], true)) {
            $merge = 'replace';
        }

        if ($merge === 'replace') {
            return $this->deduplicateItems($dynamicItems);
        }

        $items = $merge === 'prepend'
            ? array_merge($dynamicItems, $manualItems)
            : array_merge($manualItems, $dynamicItems);

        return $this->deduplicateItems($items);
    }

    /**
     * Keep the first item for each module ID and retain presentation-only items.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function deduplicateItems(array $items): array
    {
        $seenModuleIds = [];
        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                $result[] = $item;

                continue;
            }

            $moduleId = $item['moduleId'] ?? null;
            if ($moduleId !== null) {
                $key = (string) $moduleId;
                if (isset($seenModuleIds[$key])) {
                    continue;
                }
                $seenModuleIds[$key] = true;
            }

            $result[] = $item;
        }

        return $result;
    }
}
