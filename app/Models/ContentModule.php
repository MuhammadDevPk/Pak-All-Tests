<?php

namespace App\Models;

use App\Concerns\CachesTableData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentModule extends Model
{
    use CachesTableData;

    protected $guarded = [];

    protected $casts = [
        'content_meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentModule $module) {
            if (($module->sort_order === null || (int) $module->sort_order <= 0) && $module->domain_id) {
                $module->sort_order = (static::where('domain_id', $module->domain_id)->max('sort_order') ?? 0) + 1;
            }
        });

        static::deleted(function (ContentModule $module) {
            if ($module->domain_id) {
                static::reorderDomainModules($module->domain_id);
            }
        });
    }

    /**
     * Compact and re-index sort_order for all modules in a domain sequentially (1, 2, 3...).
     */
    public static function reorderDomainModules(?int $domainId): void
    {
        if (! $domainId) {
            return;
        }

        $modules = static::where('domain_id', $domainId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($modules as $index => $item) {
            $expected = $index + 1;
            if ($item->sort_order !== $expected) {
                $item->updateQuietly(['sort_order' => $expected]);
            }
        }
    }

    /**
     * @return BelongsTo<Domain, $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * @return BelongsToMany<QuestionBank, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuestionBank::class, 'module_questions', 'module_id', 'question_id')
            ->withPivot(['sort_order', 'marks'])
            ->orderByPivot('sort_order', 'asc');
    }
}
