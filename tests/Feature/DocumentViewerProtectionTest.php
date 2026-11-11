<?php

use App\Filament\Resources\DomainFlows\Support\UiSchemaCompiler;
use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\DomainFlow;
use App\Models\User;
use App\Services\SduiPayloadTransformer;

test('UiSchemaCompiler compiles manual ResourceList items with View Document action label', function () {
    $builderState = [
        'phases' => [
            [
                'title' => 'Reference Notes',
                'subtitle' => 'Handouts',
                'content_type' => 'ResourceList',
                'resource_title' => 'Official Handouts',
                'source_mode' => 'manual',
                'manual_items' => [
                    [
                        'module_id' => '101',
                        'title' => 'Verbal Intelligence Handout',
                        'file_size' => '2.5 MB',
                        'file_type' => 'PDF Document',
                        'action_label' => '', // Empty should default to View Document
                    ],
                    [
                        'module_id' => '102',
                        'title' => 'Non-Verbal Guidelines',
                        'file_size' => '1.2 MB',
                        'file_type' => 'PDF Document',
                        'action_label' => 'Read Guide', // Explicit label preserved
                    ],
                ],
            ],
        ],
    ];

    $compiled = UiSchemaCompiler::compile($builderState);
    $items = $compiled['props']['phases'][0]['content']['props']['items'];

    expect($items)->toHaveCount(2)
        ->and($items[0]['actionLabel'])->toBe('View Document')
        ->and($items[1]['actionLabel'])->toBe('Read Guide')
        ->and($items[0]['actionLabel'])->not->toBe('Download');
});

test('UiSchemaCompiler decompile and compile roundtrip preserves View Document default', function () {
    $original = [
        'type' => 'PhaseStepperLayout',
        'props' => [
            'currentPhase' => 1,
            'phases' => [
                [
                    'number' => 1,
                    'title' => 'Phase 1',
                    'subtitle' => 'Sub 1',
                    'content' => [
                        'type' => 'ResourceList',
                        'props' => [
                            'title' => 'Official Syllabus',
                            'items' => [
                                [
                                    'moduleId' => 42,
                                    'title' => 'Past Paper Booklet',
                                    'fileSize' => '5.0 MB',
                                    'fileType' => 'PDF Document',
                                    'actionLabel' => 'View Document',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $decompiled = UiSchemaCompiler::decompile($original);
    expect($decompiled['phases'][0]['manual_items'][0]['action_label'])->toBe('View Document');

    $recompiled = UiSchemaCompiler::compile($decompiled);
    expect($recompiled['props']['phases'][0]['content']['props']['items'][0]['actionLabel'])->toBe('View Document')
        ->and($recompiled['props']['phases'][0]['content']['props']['items'][0]['actionLabel'])->not->toBe('Download');
});

test('SduiPayloadTransformer provisions pdf_repo modules with View PDF action label and clean metadata', function () {
    $user = User::factory()->create();
    $uid = uniqid();

    $domain = Domain::create([
        'name' => 'Intelligence Test Track '.$uid,
        'slug' => 'intelligence-test-track-'.$uid,
        'code' => 'ITT_'.$uid,
        'is_active' => true,
    ]);

    $pdfModule = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Intelligence Screening Guidelines',
        'slug' => 'intelligence-screening-guidelines-'.$uid,
        'module_type' => 'pdf_repo',
        'display_mode' => 'pdf_repo',
        'is_active' => true,
        'content_meta' => [
            'description' => 'Reference guidelines for psychological and medical screening.',
            'file_url' => 'https://drive.google.com/file/d/test123xyz/view?usp=sharing',
            'files' => [
                [
                    'title' => 'Verbal Intelligence Notes',
                    'file_url' => 'https://drive.google.com/file/d/test123xyz/view?usp=sharing',
                    'file_type' => 'PDF Document',
                    'file_size' => '3.4 MB',
                ],
            ],
        ],
    ]);

    DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'ResourceList',
            'props' => [
                'title' => 'Screening Documents',
                'moduleQuery' => [
                    'module_type' => 'pdf_repo',
                    'merge' => 'replace',
                ],
            ],
        ],
        'learning_schema' => [
            'passing_percentage' => 70,
        ],
    ]);

    $transformed = (new SduiPayloadTransformer)->transform($domain, $user);
    $items = $transformed['layout']['props']['items'] ?? [];

    expect($items)->toHaveCount(1)
        ->and($items[0]['moduleId'])->toBe($pdfModule->id)
        ->and($items[0]['actionLabel'])->toBe('View PDF')
        ->and($items[0]['actionLabel'])->not->toBe('Download')
        ->and($items[0]['displayMode'])->toBe('pdf_repo')
        ->and($items[0]['files'])->toHaveCount(1)
        ->and($items[0]['files'][0]['title'])->toBe('Verbal Intelligence Notes');
});
