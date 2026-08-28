<?php

use App\Filament\Resources\DomainFlows\Pages\EditDomainFlow;
use App\Filament\Resources\DomainFlows\Support\UiSchemaCompiler;
use App\Models\Domain;
use App\Models\DomainFlow;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('UiSchemaCompiler compiles repeater state to canonical SDUI json', function () {
    $builderState = [
        'phases' => [
            [
                'title' => 'Intelligence Phase',
                'subtitle' => 'Verbal & Non-verbal',
                'content_type' => 'TabbedLayout',
                'default_tab' => 'verbal',
                'tabs' => [
                    [
                        'id' => 'verbal',
                        'label' => 'Verbal Intelligence',
                        'badge' => 'Active',
                        'content_type' => 'SubjectGrid',
                        'source_mode' => 'moduleQuery',
                        'columns' => 2,
                        'module_type' => 'preparation',
                        'slug_contains' => 'verbal',
                        'merge' => 'replace',
                    ],
                ],
            ],
            [
                'title' => 'Study Materials',
                'subtitle' => 'Reference notes',
                'content_type' => 'ResourceList',
                'resource_title' => 'PDF Handouts',
                'source_mode' => 'moduleQuery',
                'module_type' => 'pdf_repo',
                'slug_contains' => '',
                'merge' => 'replace',
            ],
        ],
    ];

    $compiled = UiSchemaCompiler::compile($builderState);

    expect($compiled['type'])->toBe('PhaseStepperLayout')
        ->and($compiled['props']['phases'])->toHaveCount(2)
        ->and($compiled['props']['phases'][0]['title'])->toBe('Intelligence Phase')
        ->and($compiled['props']['phases'][0]['number'])->toBe(1)
        ->and($compiled['props']['phases'][0]['content']['type'])->toBe('TabbedLayout')
        ->and($compiled['props']['phases'][0]['content']['props']['tabs'][0]['content']['type'])->toBe('SubjectGrid')
        ->and($compiled['props']['phases'][0]['content']['props']['tabs'][0]['content']['props']['moduleQuery']['module_type'])->toBe('preparation')
        ->and($compiled['props']['phases'][1]['content']['type'])->toBe('ResourceList')
        ->and($compiled['props']['phases'][1]['content']['props']['title'])->toBe('PDF Handouts');
});

test('UiSchemaCompiler roundtrips canonical SDUI JSON losslessly', function () {
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
                        'type' => 'SubjectGrid',
                        'props' => [
                            'columns' => 2,
                            'moduleQuery' => [
                                'module_type' => 'preparation',
                                'slug_contains' => 'academic',
                                'merge' => 'replace',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $decompiled = UiSchemaCompiler::decompile($original);
    $recompiled = UiSchemaCompiler::compile($decompiled);

    expect($recompiled)->toEqual($original);
});

test('admin can view and edit domain flow via visual form', function () {
    $domain = Domain::create([
        'name' => 'Test Flow Domain',
        'slug' => 'test-flow-domain',
        'code' => 'TFD',
        'is_active' => true,
    ]);

    $flow = DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'PhaseStepperLayout',
            'props' => [
                'currentPhase' => 1,
                'phases' => [
                    [
                        'number' => 1,
                        'title' => 'Initial Phase',
                        'subtitle' => 'Test',
                        'content' => [
                            'type' => 'SubjectGrid',
                            'props' => [
                                'columns' => 2,
                                'moduleQuery' => [
                                    'module_type' => 'preparation',
                                    'merge' => 'replace',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'learning_schema' => [
            'passing_percentage' => 75,
            'time_limit_minutes' => 60,
        ],
    ]);

    Livewire::test(EditDomainFlow::class, ['record' => $flow->id])
        ->assertSuccessful()
        ->assertSet('data.passing_percentage', 75)
        ->assertSet('data.time_limit_minutes', 60)
        ->call('save')
        ->assertHasNoErrors();

    $flow->refresh();
    expect($flow->learning_schema['passing_percentage'])->toBe(75)
        ->and($flow->ui_schema['props']['phases'][0]['title'])->toBe('Initial Phase');
});
