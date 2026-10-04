<?php

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\DomainFlow;
use App\Models\User;
use App\Services\SduiPayloadTransformer;
use App\Services\SduiSchemaValidator;

test('sdui payload transformer dynamically auto-populates moduleQuery and preserves sort order', function () {
    $user = User::factory()->create();

    $domain = Domain::create([
        'name' => 'Army Test Prep',
        'slug' => 'army-prep',
        'code' => 'PMA_TEST',
        'is_active' => true,
    ]);

    // Create 3 content modules with distinct sort orders
    $module1 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Verbal Intelligence Test 2',
        'slug' => 'verbal-intelligence-test-2',
        'module_type' => 'preparation',
        'sort_order' => 10,
        'is_active' => true,
        'content_meta' => ['description' => 'Verbal module description'],
    ]);

    $module2 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Verbal Intelligence Test 1',
        'slug' => 'verbal-intelligence-test-1',
        'module_type' => 'preparation',
        'sort_order' => 5,
        'is_active' => true,
        'content_meta' => ['description' => 'Verbal module 1 description'],
    ]);

    $module3 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Abstract Pattern Logic',
        'slug' => 'abstract-pattern-logic',
        'module_type' => 'preparation',
        'sort_order' => 20,
        'is_active' => true,
        'content_meta' => ['description' => 'Abstract module description'],
    ]);

    $flow = DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'SubjectGrid',
            'props' => [
                'columns' => 2,
                'moduleQuery' => [
                    'module_type' => 'preparation',
                    'slug_contains' => 'verbal',
                ],
            ],
        ],
    ]);

    $transformer = new SduiPayloadTransformer;
    $payload = $transformer->transform($domain, $user);

    $layout = $payload['layout'];

    // Verify SubjectGrid has auto-populated props.items
    expect($layout['props'])->toHaveKey('items');
    expect($layout['props']['items'])->toHaveCount(2);

    // Verify sorting order: verbal-intelligence-test-1 (sort_order = 5) should come before verbal-intelligence-test-2 (sort_order = 10)
    expect($layout['props']['items'][0]['moduleId'])->toBe($module2->id);
    expect($layout['props']['items'][1]['moduleId'])->toBe($module1->id);

    // Verify generated items use the same shape as manually authored cards.
    expect($layout['props']['items'][0]['title'])->toBe('Verbal Intelligence Test 1');
    expect($layout['props']['items'][0]['description'])->toBe('Verbal module 1 description');
    expect($layout['props']['items'][0]['actionLabel'])->toBe('Start Practice');

    // Validate both the authoring schema and hydrated runtime schema.
    $validator = new SduiSchemaValidator;
    expect($validator->validate($flow->ui_schema)['valid'])->toBeTrue();
    expect($validator->validate($layout)['valid'])->toBeTrue();
});

it('excludes inactive modules and applies merge modes with deduplication', function () {
    $user = User::factory()->create();
    $domain = Domain::create([
        'name' => 'Merge Test', 'slug' => 'merge-test', 'code' => 'MERGE', 'is_active' => true,
    ]);

    $active = ContentModule::create([
        'domain_id' => $domain->id, 'title' => 'Active Verbal', 'slug' => 'active-verbal',
        'module_type' => 'preparation', 'sort_order' => 1, 'is_active' => true, 'content_meta' => [],
    ]);
    ContentModule::create([
        'domain_id' => $domain->id, 'title' => 'Inactive Verbal', 'slug' => 'inactive-verbal',
        'module_type' => 'preparation', 'sort_order' => 2, 'is_active' => false, 'content_meta' => [],
    ]);

    DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'SubjectGrid',
            'props' => [
                'items' => [
                    ['moduleId' => $active->id, 'title' => 'Manual Active'],
                    ['moduleId' => $active->id, 'title' => 'Duplicate Manual'],
                    ['title' => 'Presentation-only item'],
                ],
                'moduleQuery' => [
                    'module_type' => 'preparation',
                    'slug_contains' => 'verbal',
                    'merge' => 'append',
                ],
            ],
        ],
    ]);

    $layout = (new SduiPayloadTransformer)->transform($domain, $user)['layout'];
    $items = $layout['props']['items'];

    // append: manual items first, deduplicated by moduleId, then dynamic items.
    // The active module already appears in manual items, so the query result is
    // not duplicated. The presentation-only item (no moduleId) is retained.
    expect($items)->toHaveCount(2);
    expect($items[0]['title'])->toBe('Manual Active');
    expect($items[1]['title'])->toBe('Presentation-only item');
});

it('appends dynamic modules that are not already present in manual items', function () {
    $user = User::factory()->create();
    $domain = Domain::create([
        'name' => 'Append Test', 'slug' => 'append-test', 'code' => 'APPEND', 'is_active' => true,
    ]);

    $manual = ContentModule::create([
        'domain_id' => $domain->id, 'title' => 'Manual Verbal', 'slug' => 'manual-only',
        'module_type' => 'preparation', 'sort_order' => 1, 'is_active' => true, 'content_meta' => [],
    ]);
    $dynamic = ContentModule::create([
        'domain_id' => $domain->id, 'title' => 'Dynamic Verbal', 'slug' => 'dynamic-verbal',
        'module_type' => 'preparation', 'sort_order' => 2, 'is_active' => true, 'content_meta' => [],
    ]);

    DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'SubjectGrid',
            'props' => [
                'items' => [
                    ['moduleId' => $manual->id, 'title' => 'Manual Verbal'],
                ],
                'moduleQuery' => [
                    'module_type' => 'preparation',
                    'slug_contains' => 'dynamic',
                    'merge' => 'append',
                ],
            ],
        ],
    ]);

    $items = (new SduiPayloadTransformer)->transform($domain, $user)['layout']['props']['items'];

    expect($items)->toHaveCount(2);
    expect($items[0]['moduleId'])->toBe($manual->id);
    expect($items[1]['moduleId'])->toBe($dynamic->id)->and($items[1]['title'])->toBe('Dynamic Verbal');
});

it('prepends dynamic modules before manual items', function () {
    $user = User::factory()->create();
    $domain = Domain::create([
        'name' => 'Prepend Test', 'slug' => 'prepend-test', 'code' => 'PREPEND', 'is_active' => true,
    ]);

    $manual = ContentModule::create([
        'domain_id' => $domain->id, 'title' => 'Manual Verbal', 'slug' => 'manual-only',
        'module_type' => 'preparation', 'sort_order' => 1, 'is_active' => true, 'content_meta' => [],
    ]);
    $dynamic = ContentModule::create([
        'domain_id' => $domain->id, 'title' => 'Dynamic Verbal', 'slug' => 'dynamic-verbal',
        'module_type' => 'preparation', 'sort_order' => 2, 'is_active' => true, 'content_meta' => [],
    ]);

    DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'SubjectGrid',
            'props' => [
                'items' => [
                    ['moduleId' => $manual->id, 'title' => 'Manual Verbal'],
                ],
                'moduleQuery' => [
                    'module_type' => 'preparation',
                    'slug_contains' => 'dynamic',
                    'merge' => 'prepend',
                ],
            ],
        ],
    ]);

    $items = (new SduiPayloadTransformer)->transform($domain, $user)['layout']['props']['items'];

    expect($items)->toHaveCount(2);
    expect($items[0]['moduleId'])->toBe($dynamic->id);
    expect($items[1]['moduleId'])->toBe($manual->id);
});

it('replace mode ignores manual items entirely', function () {
    $user = User::factory()->create();
    $domain = Domain::create([
        'name' => 'Replace Test', 'slug' => 'replace-test', 'code' => 'REPLACE', 'is_active' => true,
    ]);

    $dynamic = ContentModule::create([
        'domain_id' => $domain->id, 'title' => 'Dynamic Verbal', 'slug' => 'dynamic-verbal',
        'module_type' => 'preparation', 'sort_order' => 1, 'is_active' => true, 'content_meta' => [],
    ]);

    DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'SubjectGrid',
            'props' => [
                'items' => [
                    ['moduleId' => 999999, 'title' => 'Stale Manual'],
                ],
                'moduleQuery' => [
                    'module_type' => 'preparation',
                    'slug_contains' => 'verbal',
                    'merge' => 'replace',
                ],
            ],
        ],
    ]);

    $items = (new SduiPayloadTransformer)->transform($domain, $user)['layout']['props']['items'];

    expect($items)->toHaveCount(1);
    expect($items[0]['moduleId'])->toBe($dynamic->id);
});

it('rejects unsupported moduleQuery configuration', function () {
    $result = (new SduiSchemaValidator)->validate([
        'type' => 'ResourceList',
        'props' => [
            'moduleQuery' => [
                'merge' => 'sideways',
                'unknown' => true,
            ],
        ],
    ]);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toContain("Unsupported moduleQuery key 'unknown' at depth 1.");
});

it('filters modules by dashboard_section and display_mode in moduleQuery', function () {
    $user = User::factory()->create();
    $uid = uniqid();
    $domain = Domain::create([
        'name' => 'PMA Test Track '.$uid,
        'slug' => 'pma-test-track-'.$uid,
        'code' => 'PMA_'.$uid,
        'is_active' => true,
    ]);

    $academic = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Islamic Studies',
        'slug' => 'islamic-studies-'.$domain->id,
        'module_type' => 'preparation',
        'dashboard_section' => 'academic',
        'display_mode' => 'mcq_test',
        'sort_order' => 1,
        'is_active' => true,
        'content_meta' => [],
    ]);

    $physical = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Physical Test Details',
        'slug' => 'physical-test-details-'.$domain->id,
        'module_type' => 'preparation',
        'dashboard_section' => 'physical',
        'display_mode' => 'study_qa',
        'sort_order' => 2,
        'is_active' => true,
        'content_meta' => [],
    ]);

    DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'SubjectGrid',
            'props' => [
                'moduleQuery' => [
                    'dashboard_section' => 'physical',
                    'merge' => 'replace',
                ],
            ],
        ],
    ]);

    $items = (new SduiPayloadTransformer)->transform($domain, $user)['layout']['props']['items'];

    expect($items)->toHaveCount(1)
        ->and($items[0]['moduleId'])->toBe($physical->id)
        ->and($items[0]['dashboardSection'])->toBe('physical')
        ->and($items[0]['displayMode'])->toBe('study_qa')
        ->and($items[0]['actionLabel'])->toBe('View Details & Q&A');
});

it('validates dashboard_section, display_mode, and limit as supported moduleQuery keys in SduiSchemaValidator', function () {
    $result = (new SduiSchemaValidator)->validate([
        'type' => 'SubjectGrid',
        'props' => [
            'moduleQuery' => [
                'dashboard_section' => 'physical',
                'display_mode' => 'study_qa',
                'limit' => 5,
                'merge' => 'replace',
            ],
        ],
    ]);

    expect($result['valid'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

it('enforces limit in SduiPayloadTransformer and validates limit constraints in SduiSchemaValidator', function () {
    $user = User::factory()->create();
    $domain = Domain::create([
        'name' => 'Limit Test Domain',
        'code' => 'PMA_LIMIT_'.uniqid(),
        'slug' => 'pma-limit-test-'.uniqid(),
        'is_active' => true,
    ]);

    DomainFlow::create([
        'domain_id' => $domain->id,
        'ui_schema' => [
            'type' => 'SubjectGrid',
            'props' => [
                'moduleQuery' => [
                    'dashboard_section' => 'academic',
                    'limit' => 2,
                    'merge' => 'replace',
                ],
            ],
        ],
    ]);

    for ($i = 1; $i <= 4; $i++) {
        ContentModule::create([
            'domain_id' => $domain->id,
            'title' => "Academic Subject {$i}",
            'slug' => "academic-subject-{$i}-".uniqid(),
            'module_type' => 'preparation',
            'dashboard_section' => 'academic',
            'sort_order' => $i,
            'is_active' => true,
        ]);
    }

    $items = (new SduiPayloadTransformer)->transform($domain, $user)['layout']['props']['items'];

    expect($items)->toHaveCount(2);

    $invalidResult = (new SduiSchemaValidator)->validate([
        'type' => 'SubjectGrid',
        'props' => [
            'moduleQuery' => [
                'limit' => -1,
            ],
        ],
    ]);

    expect($invalidResult['valid'])->toBeFalse()
        ->and($invalidResult['errors'][0])->toContain('must be a positive integer');
});

it('validates StudyQaPlayer in SduiSchemaValidator', function () {
    $validResult = (new SduiSchemaValidator)->validate([
        'type' => 'StudyQaPlayer',
        'props' => [
            'moduleId' => 410,
        ],
    ]);

    expect($validResult['valid'])->toBeTrue()
        ->and($validResult['warnings'])->toBeEmpty();

    $warningResult = (new SduiSchemaValidator)->validate([
        'type' => 'StudyQaPlayer',
        'props' => [],
    ]);

    expect($warningResult['warnings'])->toHaveCount(1)
        ->and($warningResult['warnings'][0])->toContain("should specify 'moduleId'");
});

it('resolves subjective module_type to essay display mode', function () {
    $module = new ContentModule([
        'module_type' => 'subjective',
    ]);

    expect($module->resolveDisplayMode())->toBe('essay');
});
