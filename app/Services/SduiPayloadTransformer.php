<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\User;

class SduiPayloadTransformer
{
    /**
     * Transform Domain and flow into a dynamic SDUI Inertia payload.
     */
    public function transform(Domain $domain, User $user): array
    {
        $flow = $domain->flow;

        $uiSchema = $flow?->ui_schema ?? [
            'type' => 'EmptyStateLayout',
            'props' => [
                'title' => 'Domain Under Construction',
                'message' => 'The curriculum and modules for this exam are being populated by the academy.',
            ],
        ];

        $modules = $domain->contentModules
            ->keyBy('id')
            ->map(fn ($module) => [
                'id' => $module->id,
                'type' => $module->module_type,
                'title' => $module->title,
                'content_meta' => $module->content_meta,
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

    protected function hydrateSchemaNodes(array $node, $modules): array
    {
        if (isset($node['moduleId']) && $modules->has($node['moduleId'])) {
            $node['props']['module'] = $modules->get($node['moduleId']);
        }

        if (isset($node['props']['items']) && is_array($node['props']['items'])) {
            foreach ($node['props']['items'] as $index => $item) {
                $node['props']['items'][$index] = $this->hydrateSchemaNodes($item, $modules);
            }
        }

        if (isset($node['props']['tabs']) && is_array($node['props']['tabs'])) {
            foreach ($node['props']['tabs'] as $index => $tab) {
                if (isset($tab['content'])) {
                    $node['props']['tabs'][$index]['content'] = $this->hydrateSchemaNodes($tab['content'], $modules);
                }
            }
        }

        return $node;
    }
}
