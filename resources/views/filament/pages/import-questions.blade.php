<x-filament-panels::page>
    <style>
        .qi-container { display: flex; flex-direction: column; gap: 1.5rem; }
        .qi-hero {
            background: #065f46; color: #fff; border-radius: 0.75rem;
            padding: 1.5rem 1.75rem; border: 1px solid #064e3b;
        }
        .qi-hero h1 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.01em; margin: 0 0 0.4rem; }
        .qi-hero p { font-size: 0.875rem; line-height: 1.6; color: #d1fae5; margin: 0; max-width: 70ch; }
        .qi-card {
            background: var(--color-white, #fff); border: 1px solid rgb(var(--gray-200));
            border-radius: 0.75rem; padding: 1.5rem 1.75rem;
        }
        .qi-card h3 { font-size: 1rem; font-weight: 700; margin: 0 0 0.6rem; }
        .qi-card p, .qi-card li { font-size: 0.85rem; line-height: 1.65; color: rgb(var(--gray-600)); }
        .qi-card ul { margin: 0.75rem 0 0; padding-left: 1.15rem; display: grid; gap: 0.35rem; }
        .qi-card code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8em; }
        .qi-code {
            background: #0b1220; color: #dbe7f3; border-radius: 0.6rem;
            padding: 1rem 1.15rem; font-size: 0.78rem; line-height: 1.7;
            overflow-x: auto; margin-top: 0.9rem; white-space: pre;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        .qi-micro { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: rgb(var(--gray-400)); font-weight: 600; }
        .qi-table { width: 100%; border-collapse: collapse; margin-top: 0.9rem; font-size: 0.85rem; }
        .qi-table th { text-align: left; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em;
            color: rgb(var(--gray-400)); padding: 0.5rem 0.6rem; border-bottom: 1px solid rgb(var(--gray-200)); }
        .qi-table td { padding: 0.55rem 0.6rem; border-bottom: 1px solid rgb(var(--gray-100)); }
        .qi-table tr:last-child td { border-bottom: none; }
        .qi-num { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .qi-badge {
            display: inline-block; font-size: 0.72rem; font-weight: 600; border-radius: 9999px;
            padding: 0.15rem 0.6rem; border: 1px solid transparent;
        }
        .qi-badge-dry { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
        .qi-badge-live { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
        .dark .qi-card {
            background: #18181b;
            border-color: #27272a;
        }
        .dark .qi-card h3 { color: #f4f4f5; }
        .dark .qi-card p, .dark .qi-card li { color: #a1a1aa; }
        .dark .qi-micro { color: #71717a; }
        .dark .qi-table th { color: #71717a; border-bottom-color: #27272a; }
        .dark .qi-table td { color: #e4e4e7; border-bottom-color: #27272a; }
    </style>

    <div class="qi-container">
        <div class="qi-hero">
            <h1>Bulk Question Import</h1>
            <p>
                Upload a JSON payload of MCQs and short-answer questions. Each subject in the file gets its own
                preparation module inside the selected domain, and every question is deduplicated by text hash
                before insert — re-running the same file never creates duplicates.
            </p>
        </div>

        {{ $this->form }}

        <div class="qi-card">
            <p class="qi-micro">Expected JSON format</p>
            <h3>File structure</h3>
            <p>
                The file must contain a <code>subjects</code> map (subject key &rarr; module title) and a
                <code>questions</code> array. Each question declares its <code>subject</code> key, its
                <code>type</code> (<code>mcq</code> or <code>short_answer</code>), and the question text.
                MCQs need exactly 4 options and an <code>answer_index</code> of 0&ndash;3; short answers need an
                <code>answer</code>. Items that fail validation are skipped and listed in the import report.
            </p>
            <div class="qi-code">{
  "meta": { "source": "PMA Academic Pack", "total": 1462 },
  "subjects": {
    "islamiyat": "Islamic Studies (Islamiat)",
    "pak-history": "Pakistan History &amp; Movement"
  },
  "questions": [
    {
      "code": "PMA-ACAD-0001",
      "subject": "islamiyat",
      "type": "mcq",
      "question": "Which Surah was revealed first?",
      "options": ["Surah Alaq", "Surah Fatiha", "Surah Baqarah", "Surah Ikhlas"],
      "answer_index": 0
    },
    {
      "code": "PMA-ACAD-0002",
      "subject": "pak-history",
      "type": "short_answer",
      "question": "Year of the Hijrah",
      "answer": "622 AD"
    }
  ]
}</div>
            <ul>
                <li><code>code</code> is optional and must be unique — empty or colliding codes are stored as <code>null</code>.</li>
                <li>Duplicate question text (exact match after normalization) is skipped, never inserted twice.</li>
                <li>Questions are processed in batches of 50 per subject inside a single transaction per batch.</li>
            </ul>
        </div>

        @if ($report = $this->getLastReport())
            <div class="qi-card">
                <p class="qi-micro">Last import report</p>
                <h3>
                    {{ $report['dry_run'] ? 'Dry-run preview' : 'Import results' }}
                    <span class="qi-badge {{ $report['dry_run'] ? 'qi-badge-dry' : 'qi-badge-live' }}">
                        {{ $report['dry_run'] ? 'DRY RUN — nothing written' : 'LIVE RUN' }}
                    </span>
                </h3>

                @if (! empty($report['errors']))
                    <p><strong>Errors:</strong> {{ implode(' ', $report['errors']) }}</p>
                @else
                    <table class="qi-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Module</th>
                                <th>Inserted</th>
                                <th>Duplicates skipped</th>
                                <th>Invalid</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['subjects'] as $key => $subject)
                                <tr>
                                    <td>{{ $subject['title'] }} <code>{{ $key }}</code></td>
                                    <td class="qi-num">{{ $subject['module_id'] ?? '—' }}</td>
                                    <td class="qi-num">{{ $subject['inserted'] }}</td>
                                    <td class="qi-num">{{ $subject['duplicates_skipped'] }}</td>
                                    <td class="qi-num">{{ $subject['invalid'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p style="margin-top: 0.9rem;">
                        Totals: <span class="qi-num">{{ $report['totals']['inserted'] }}</span> inserted,
                        <span class="qi-num">{{ $report['totals']['duplicates_skipped'] }}</span> duplicates skipped,
                        <span class="qi-num">{{ $report['totals']['invalid'] }}</span> invalid
                        of <span class="qi-num">{{ $report['totals']['processed'] }}</span> valid items.
                    </p>
                @endif
            </div>
        @elseif ($this->reportKey)
            <div class="qi-card" wire:poll.3s>
                <p class="qi-micro">Background Import in Progress</p>
                <h3>Processing questions...</h3>
                <p>The import job is executing in the background. This card will automatically update once processing finishes.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
