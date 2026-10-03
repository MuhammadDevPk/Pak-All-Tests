<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $question_id
 * @property int|null $domain_id
 * @property int $consecutive_correct
 * @property string $status
 * @property int $revealed_count
 * @property int $failed_count
 * @property CarbonInterface|null $last_practiced_at
 * @property CarbonInterface|null $mastered_at
 * @property-read User $user
 * @property-read QuestionBank $question
 * @property-read Domain|null $domain
 */
class UserWeakQuestion extends Model
{
    protected $table = 'user_weak_questions';

    protected $guarded = [];

    protected $casts = [
        'consecutive_correct' => 'integer',
        'revealed_count' => 'integer',
        'failed_count' => 'integer',
        'last_practiced_at' => 'datetime',
        'mastered_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<QuestionBank, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    /**
     * @return BelongsTo<Domain, $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Scope query to only active (un-mastered) weak questions.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope query to mastered questions.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeMastered(Builder $query): Builder
    {
        return $query->where('status', 'mastered');
    }

    /**
     * Record that the candidate clicked "Reveal Answer" on this question.
     */
    public function recordReveal(): void
    {
        $this->revealed_count++;
        $this->consecutive_correct = 0;
        $this->status = 'active';
        $this->last_practiced_at = now();
        $this->mastered_at = null;
        $this->save();
    }

    /**
     * Record a practice attempt. If correct, increments consecutive_correct.
     * Upon reaching 2 consecutive correct answers, automatically marks as mastered.
     */
    public function recordAttempt(bool $isCorrect): bool
    {
        $this->last_practiced_at = now();

        if ($isCorrect) {
            $this->consecutive_correct++;
            if ($this->consecutive_correct >= 2) {
                $this->status = 'mastered';
                $this->mastered_at = now();
            }
        } else {
            $this->failed_count++;
            $this->consecutive_correct = 0;
            $this->status = 'active';
            $this->mastered_at = null;
        }

        $this->save();

        return $this->status === 'mastered';
    }
}
