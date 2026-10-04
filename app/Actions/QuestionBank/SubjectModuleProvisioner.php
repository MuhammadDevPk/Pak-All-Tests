<?php

declare(strict_types=1);

namespace App\Actions\QuestionBank;

use App\Models\ContentModule;
use App\Models\Domain;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Canonical subject-module provisioning for the PMA Academic question packs.
 *
 * Single source of truth for subject keys, module titles, slugs and the
 * firstOrCreate lookup keys, shared by the bulk import job and one-off
 * repair jobs so the two can never diverge.
 */
final class SubjectModuleProvisioner
{
    /**
     * Canonical subject key => module title map. Mirrors the `subjects` map
     * of the PMA Academic import payload.
     *
     * @var array<string, string>
     */
    public const SUBJECTS = [
        'islamiyat' => 'Islamic Studies (Islamiat)',
        'pak-history' => 'Pakistan History & Movement',
        'geography' => 'Geography (Pakistan & World)',
        'gk' => 'General Knowledge & Everyday Science',
        'current-affairs' => 'Current Affairs & Defense/Security',
    ];

    /**
     * Deterministic slug for a subject module inside a domain.
     */
    public static function slugFor(string $title, int $domainId): string
    {
        return Str::slug($title)."-{$domainId}";
    }

    /**
     * @throws \InvalidArgumentException when the subject key is unknown
     */
    public static function titleFor(string $subjectKey): string
    {
        if (! isset(self::SUBJECTS[$subjectKey])) {
            throw new \InvalidArgumentException("Unknown PMA subject key: {$subjectKey}");
        }

        return self::SUBJECTS[$subjectKey];
    }

    /**
     * Find-or-create with full Eloquent events (fires the sort_order default
     * hook, table-cache invalidation and the vectorization hook). Used by the
     * bulk import path, where module vectorization is intended.
     *
     * @param  string|null  $title  explicit title; defaults to the canonical title for the key
     * @param  array<string, mixed>  $attributes  optional attributes (dashboard_section, display_mode, description, etc.)
     */
    public static function findOrCreate(
        Domain $domain,
        string $subjectKey,
        ?string $title = null,
        array $attributes = []
    ): ContentModule {
        $resolvedTitle = $title ?? self::titleFor($subjectKey);
        $slug = self::slugFor($resolvedTitle, $domain->id);

        $moduleType = (string) ($attributes['module_type'] ?? 'preparation');
        $dashboardSection = isset($attributes['dashboard_section']) && filled($attributes['dashboard_section'])
            ? (string) $attributes['dashboard_section']
            : null;
        $displayMode = isset($attributes['display_mode']) && filled($attributes['display_mode'])
            ? (string) $attributes['display_mode']
            : null;

        $contentMeta = ['import_subject_key' => $subjectKey];
        if (isset($attributes['description']) && filled($attributes['description'])) {
            $contentMeta['description'] = trim((string) $attributes['description']);
        }

        $module = ContentModule::firstOrCreate(
            ['domain_id' => $domain->id, 'slug' => $slug],
            [
                'title' => $resolvedTitle,
                'module_type' => $moduleType,
                'dashboard_section' => $dashboardSection,
                'display_mode' => $displayMode,
                'is_active' => true,
                'content_meta' => $contentMeta,
            ]
        );

        $updates = [];
        if ($dashboardSection !== null && $module->dashboard_section !== $dashboardSection) {
            $updates['dashboard_section'] = $dashboardSection;
        }
        if ($displayMode !== null && $module->display_mode !== $displayMode) {
            $updates['display_mode'] = $displayMode;
        }
        if (isset($attributes['description']) && filled($attributes['description'])) {
            $currentMeta = is_array($module->content_meta) ? $module->content_meta : [];
            if (($currentMeta['description'] ?? null) !== trim((string) $attributes['description'])) {
                $currentMeta['description'] = trim((string) $attributes['description']);
                $updates['content_meta'] = $currentMeta;
            }
        }

        if (! empty($updates)) {
            $module->updateQuietly($updates);
        }

        return $module;
    }

    /**
     * Find-or-create WITHOUT firing Eloquent model events.
     *
     * Used by repair/linking jobs that must not dispatch vectorization jobs
     * ("linking only"). Replicates the `creating` hook's sort_order default
     * explicitly and flushes the table cache manually so Filament's cached
     * table data stays coherent.
     */
    public static function findOrCreateQuietly(Domain $domain, string $subjectKey): ContentModule
    {
        $title = self::titleFor($subjectKey);
        $slug = self::slugFor($title, $domain->id);

        $existing = ContentModule::where('domain_id', $domain->id)
            ->where('slug', $slug)
            ->first();

        if ($existing instanceof ContentModule) {
            return $existing;
        }

        // Mirrors ContentModule::creating sort_order default (max + 1 per domain).
        $sortOrder = (int) (ContentModule::where('domain_id', $domain->id)->max('sort_order') ?? 0) + 1;

        try {
            $id = DB::table('content_modules')->insertGetId([
                'domain_id' => $domain->id,
                'slug' => $slug,
                'title' => $title,
                'module_type' => 'preparation',
                'sort_order' => $sortOrder,
                'is_active' => true,
                'content_meta' => json_encode(['import_subject_key' => $subjectKey]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Lost a create race: re-read the winner instead of failing.
            if ($e->getCode() === '23505') {
                $winner = ContentModule::where('domain_id', $domain->id)
                    ->where('slug', $slug)
                    ->first();

                if ($winner instanceof ContentModule) {
                    return $winner;
                }
            }

            throw $e;
        }

        // The query-builder insert bypassed model events: invalidate the
        // cached table data explicitly (CachesTableData would do this on saved).
        ContentModule::flushCache();

        return ContentModule::findOrFail($id);
    }
}
