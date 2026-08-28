<?php

use App\Filament\Resources\ContentModules\Pages\CreateContentModule;
use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('content module auto-assigns next sort_order sequentially on create', function () {
    $domain = Domain::create([
        'name' => 'Auto Sort Domain',
        'slug' => 'auto-sort-domain',
        'code' => 'ASD',
        'is_active' => true,
    ]);

    $mod1 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Module 1',
        'slug' => 'mod-1',
        'module_type' => 'preparation',
        'is_active' => true,
    ]);

    $mod2 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Module 2',
        'slug' => 'mod-2',
        'module_type' => 'preparation',
        'is_active' => true,
    ]);

    $mod3 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Module 3',
        'slug' => 'mod-3',
        'module_type' => 'pdf_repo',
        'is_active' => true,
    ]);

    expect($mod1->sort_order)->toBe(1)
        ->and($mod2->sort_order)->toBe(2)
        ->and($mod3->sort_order)->toBe(3);
});

test('deleting a content module re-indexes remaining modules without gaps', function () {
    $domain = Domain::create([
        'name' => 'Reindex Domain',
        'slug' => 'reindex-domain',
        'code' => 'RD',
        'is_active' => true,
    ]);

    $mod1 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'M1',
        'slug' => 'm1',
        'module_type' => 'preparation',
        'sort_order' => 1,
    ]);
    $mod2 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'M2',
        'slug' => 'm2',
        'module_type' => 'preparation',
        'sort_order' => 2,
    ]);
    $mod3 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'M3',
        'slug' => 'm3',
        'module_type' => 'preparation',
        'sort_order' => 3,
    ]);
    $mod4 = ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'M4',
        'slug' => 'm4',
        'module_type' => 'pdf_repo',
        'sort_order' => 4,
    ]);

    // Delete M2 in the middle
    $mod2->delete();

    $mod1->refresh();
    $mod3->refresh();
    $mod4->refresh();

    expect($mod1->sort_order)->toBe(1)
        ->and($mod3->sort_order)->toBe(2)
        ->and($mod4->sort_order)->toBe(3);
});

test('filament create form auto-populates next sort_order when domain is chosen', function () {
    $domain = Domain::create([
        'name' => 'Form Domain',
        'slug' => 'form-domain',
        'code' => 'FD',
        'is_active' => true,
    ]);

    ContentModule::create([
        'domain_id' => $domain->id,
        'title' => 'Existing 1',
        'slug' => 'ex-1',
        'module_type' => 'preparation',
        'sort_order' => 1,
    ]);

    Livewire::test(CreateContentModule::class)
        ->set('data.domain_id', $domain->id)
        ->assertSet('data.sort_order', 2);
});
