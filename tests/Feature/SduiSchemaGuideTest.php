<?php

namespace Tests\Feature;

use App\Services\SduiSchemaValidator;
use Tests\TestCase;

class SduiSchemaGuideTest extends TestCase
{
    private SduiSchemaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SduiSchemaValidator();
    }

    public function test_valid_sdui_schema_passes_validation(): void
    {
        $validSchema = [
            'type' => 'PhaseStepperLayout',
            'props' => [
                'currentPhase' => 1,
                'phases' => [
                    [
                        'number' => 1,
                        'title' => 'Initial Phase',
                        'content' => [
                            'type' => 'TabbedLayout',
                            'props' => [
                                'defaultTab' => 'v1',
                                'tabs' => [
                                    [
                                        'id' => 'v1',
                                        'label' => 'Verbal',
                                        'content' => [
                                            'type' => 'McqPlayer',
                                            'moduleId' => 10,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->validator->validate($validSchema);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertGreaterThan(0, $result['node_count']);
    }

    public function test_schema_with_prop_typo_fails_validation(): void
    {
        $invalidSchema = [
            'type' => 'SubjectGrid',
            'prop' => [ // Typo: 'prop' instead of 'props'
                'items' => [],
            ],
        ];

        $result = $this->validator->validate($invalidSchema);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString("Found key 'prop' which should be 'props'", $result['errors'][0]);
    }

    public function test_schema_with_unknown_component_type_fails_validation(): void
    {
        $invalidSchema = [
            'type' => 'UnknownComponentXYZ',
            'props' => [],
        ];

        $result = $this->validator->validate($invalidSchema);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString("Unknown component type 'UnknownComponentXYZ'", $result['errors'][0]);
    }
}
