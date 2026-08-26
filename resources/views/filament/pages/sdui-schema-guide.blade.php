<x-filament-panels::page>
    <style>
        .sdui-guide-container {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
        }
        .dark .sdui-guide-container {
            color: #f8fafc;
        }
        .sdui-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
        }
        .dark .sdui-card {
            background-color: #0f172a;
            border-color: #1e293b;
        }
        .sdui-header-banner {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            border-radius: 0.875rem;
            padding: 1.5rem;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .sdui-tab-btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            border: none;
            display: inline-flex;
            items-center: center;
            gap: 0.5rem;
        }
        .sdui-tab-btn.active {
            background-color: #f59e0b;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }
        .sdui-tab-btn.inactive {
            background-color: rgba(255, 255, 255, 0.15);
            color: #e0e7ff;
        }
        .sdui-tab-btn.inactive:hover {
            background-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
        .sdui-editor {
            width: 100%;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.8125rem;
            line-height: 1.5;
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background-color: #090d16;
            color: #38bdf8;
            outline: none;
            resize: vertical;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
        }
        .sdui-editor:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }
        .sdui-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .sdui-badge-success {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .dark .sdui-badge-success {
            background-color: rgba(22, 101, 52, 0.3);
            color: #4ade80;
            border-color: rgba(34, 197, 94, 0.3);
        }
        .sdui-badge-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .dark .sdui-badge-error {
            background-color: rgba(153, 27, 27, 0.3);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .sdui-tree-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.6rem;
            border-radius: 0.375rem;
            background-color: #1e293b;
            border: 1px solid #334155;
            margin-bottom: 0.375rem;
        }
        .sdui-tree-depth-indicator {
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            background-color: #0f172a;
            padding: 0.1rem 0.4rem;
            border-radius: 0.25rem;
        }
        .sdui-btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .sdui-btn-primary {
            background-color: #f59e0b;
            color: #ffffff;
        }
        .sdui-btn-primary:hover {
            background-color: #d97706;
        }
        .sdui-btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }
        .dark .sdui-btn-secondary {
            background-color: #334155;
            color: #f1f5f9;
        }
        .sdui-btn-secondary:hover {
            background-color: #cbd5e1;
        }
        .dark .sdui-btn-secondary:hover {
            background-color: #475569;
        }
        .sdui-code-block {
            background-color: #090d16;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 0.75rem;
            overflow-x: auto;
            max-height: 250px;
            border: 1px solid #1e293b;
        }
    </style>

    <div x-data="sduiSchemaGuide()" class="sdui-guide-container space-y-6">
        
        <!-- Header Banner & Tab Navigation -->
        <div class="sdui-header-banner flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span style="font-size: 1.5rem;">🧩</span>
                    <h1 class="text-xl font-bold tracking-tight text-white">
                        Server-Driven UI (SDUI) Schema Guide & Live Validator
                    </h1>
                </div>
                <p class="text-xs text-indigo-200">
                    Use this visual tool to validate JSON schemas, detect property typos (e.g. <code class="bg-red-900/60 text-red-200 px-1 rounded font-mono">prop</code> vs <code class="bg-emerald-900/60 text-emerald-200 px-1 rounded font-mono">props</code>), inspect nested layout node trees, and copy production templates.
                </p>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="activeTab = 'validator'" :class="['sdui-tab-btn', activeTab === 'validator' ? 'active' : 'inactive']">
                    <span>🔍</span> Live Validator
                </button>
                <button type="button" @click="activeTab = 'templates'" :class="['sdui-tab-btn', activeTab === 'templates' ? 'active' : 'inactive']">
                    <span>⚡</span> 1-Click Templates
                </button>
                <button type="button" @click="activeTab = 'components'" :class="['sdui-tab-btn', activeTab === 'components' ? 'active' : 'inactive']">
                    <span>📚</span> Component Docs
                </button>
            </div>
        </div>

        <!-- ==================== TAB 1: LIVE VALIDATOR ==================== -->
        <div x-show="activeTab === 'validator'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                
                <!-- Editor Box -->
                <div class="sdui-card space-y-3">
                    <div class="flex items-center justify-between" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="text-sm font-bold flex items-center gap-2" style="font-size: 0.95rem; font-weight: 700;">
                            <span>📝</span> Input SDUI JSON Schema
                        </h3>
                        <div class="flex items-center gap-2" style="display: flex; gap: 0.5rem;">
                            <button type="button" @click="formatJson()" class="sdui-btn-action sdui-btn-secondary">
                                ✨ Auto-Format
                            </button>
                            <button type="button" @click="clearJson()" class="sdui-btn-action sdui-btn-secondary" style="color: #ef4444;">
                                🗑 Clear
                            </button>
                        </div>
                    </div>

                    <textarea
                        x-model="jsonInput"
                        @input="validateJson()"
                        rows="22"
                        class="sdui-editor"
                        placeholder="Paste your SDUI JSON schema here to inspect and validate..."></textarea>
                </div>

                <!-- Validation & Tree Inspector -->
                <div class="sdui-card space-y-4">
                    <div class="flex items-center justify-between border-b pb-3" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
                        <h3 class="text-sm font-bold flex items-center gap-2" style="font-size: 0.95rem; font-weight: 700;">
                            <span>📊</span> Validation Report & Tree Inspector
                        </h3>
                        <div x-show="jsonInput.trim()">
                            <span x-show="validation.valid" class="sdui-badge sdui-badge-success">
                                ✓ Schema Valid
                            </span>
                            <span x-show="!validation.valid" class="sdui-badge sdui-badge-error">
                                ⚠ Errors Detected
                            </span>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div x-show="!jsonInput.trim()" style="text-align: center; padding: 3rem 1rem; color: #64748b;">
                        <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">👈</span>
                        <p style="font-size: 0.875rem;">Paste a JSON schema in the left editor box to run live structural validation.</p>
                    </div>

                    <!-- Error Alert -->
                    <template x-if="validation.errors.length > 0">
                        <div style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; padding: 1rem; color: #991b1b;">
                            <h4 style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.375rem;">
                                <span>🚨</span> Critical Errors & Key Typos (<span x-text="validation.errors.length"></span>)
                            </h4>
                            <ul style="list-style-type: disc; padding-left: 1.25rem; font-size: 0.8125rem; line-height: 1.5;">
                                <template x-for="(err, idx) in validation.errors" :key="idx">
                                    <li x-text="err" style="margin-bottom: 0.25rem;"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <!-- Stats Overview -->
                    <div x-show="jsonInput.trim() && validation.valid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.75rem; text-align: center;">
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; display: block;">Total Schema Nodes</span>
                            <span style="font-size: 1.5rem; font-weight: 800; color: #0f172a;" x-text="validation.nodeCount"></span>
                        </div>
                        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.75rem; text-align: center;">
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600; display: block;">Max Tree Depth</span>
                            <span style="font-size: 1.5rem; font-weight: 800; color: #0f172a;" x-text="validation.maxDepth"></span>
                        </div>
                    </div>

                    <!-- Visual Node Tree -->
                    <div x-show="flatTree.length > 0" style="margin-top: 1rem; border-top: 1px solid #e2e8f0; padding-top: 0.75rem;">
                        <h4 style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem;">
                            🌳 Schema Hierarchy Tree
                        </h4>
                        <div style="background-color: #090d16; border-radius: 0.5rem; padding: 0.75rem; max-height: 280px; overflow-y: auto;">
                            <template x-for="(nodeItem, nIdx) in flatTree" :key="nIdx">
                                <div class="sdui-tree-item" :style="'margin-left: ' + ((nodeItem.depth - 1) * 16) + 'px'">
                                    <span class="sdui-tree-depth-indicator" x-text="'L' + nodeItem.depth"></span>
                                    <span style="font-weight: 700; color: #38bdf8; font-size: 0.8125rem;" x-text="nodeItem.type"></span>
                                    <span x-show="nodeItem.label" style="color: #94a3b8; font-size: 0.75rem;" x-text="'(' + nodeItem.label + ')'"></span>
                                    <span x-show="nodeItem.moduleId" style="background-color: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 0.6875rem; padding: 0.1rem 0.4rem; border-radius: 0.25rem; font-weight: 600; margin-left: auto;">
                                        Module #<span x-text="nodeItem.moduleId"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ==================== TAB 2: 1-CLICK TEMPLATES ==================== -->
        <div x-show="activeTab === 'templates'" class="space-y-6">
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                @foreach($this->getTemplates() as $key => $template)
                    <div class="sdui-card space-y-3">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                    <span>⚙️</span> {{ $template['name'] }}
                                </h3>
                                <p style="font-size: 0.8125rem; color: #64748b; margin-top: 0.25rem;">
                                    {{ $template['description'] }}
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" @click="loadTemplate({{ json_encode(json_encode($template['json'], JSON_PRETTY_PRINT)) }})" class="sdui-btn-action sdui-btn-secondary">
                                    🔍 Load in Inspector
                                </button>
                                <button type="button" @click="copyToClipboard({{ json_encode(json_encode($template['json'], JSON_PRETTY_PRINT)) }})" class="sdui-btn-action sdui-btn-primary">
                                    📋 Copy JSON Snippet
                                </button>
                            </div>
                        </div>

                        <pre class="sdui-code-block"><code>{{ json_encode($template['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- ==================== TAB 3: COMPONENT DOCS ==================== -->
        <div x-show="activeTab === 'components'" class="space-y-6">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                
                <!-- PhaseStepperLayout -->
                <div class="sdui-card space-y-2">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                        <h4 style="font-weight: 700; font-size: 0.9375rem;">PhaseStepperLayout</h4>
                        <span style="background-color: #fef3c7; color: #d97706; font-size: 0.6875rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.25rem;">Structural</span>
                    </div>
                    <p style="font-size: 0.78125rem; color: #64748b;">Renders multi-phase steppers for exams (e.g., PMA Initial Verbal -> Academic -> ISSB).</p>
                    <div style="font-size: 0.75rem; line-height: 1.5;">
                        <p style="font-weight: 700; color: #334155;">Mandatory Props:</p>
                        <ul style="list-style-type: disc; padding-left: 1rem; font-family: monospace; color: #475569;">
                            <li><code style="color: #f59e0b;">phases</code>: Array of phase objects</li>
                        </ul>
                        <p style="font-weight: 700; color: #334155; margin-top: 0.375rem;">Phase Keys:</p>
                        <ul style="list-style-type: disc; padding-left: 1rem; font-family: monospace; color: #475569;">
                            <li><code style="color: #10b981;">number</code>: Step number</li>
                            <li><code style="color: #10b981;">title</code>: Phase heading</li>
                            <li><code style="color: #10b981;">content</code>: Nested SDUI node</li>
                        </ul>
                    </div>
                </div>

                <!-- TabbedLayout -->
                <div class="sdui-card space-y-2">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                        <h4 style="font-weight: 700; font-size: 0.9375rem;">TabbedLayout</h4>
                        <span style="background-color: #fef3c7; color: #d97706; font-size: 0.6875rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.25rem;">Structural</span>
                    </div>
                    <p style="font-size: 0.78125rem; color: #64748b;">Renders horizontal sub-navigation tabs containing nested layout nodes.</p>
                    <div style="font-size: 0.75rem; line-height: 1.5;">
                        <p style="font-weight: 700; color: #334155;">Mandatory Props:</p>
                        <ul style="list-style-type: disc; padding-left: 1rem; font-family: monospace; color: #475569;">
                            <li><code style="color: #f59e0b;">tabs</code>: Array of tab objects</li>
                        </ul>
                        <p style="font-weight: 700; color: #334155; margin-top: 0.375rem;">Tab Keys:</p>
                        <ul style="list-style-type: disc; padding-left: 1rem; font-family: monospace; color: #475569;">
                            <li><code style="color: #10b981;">id</code>: Tab identifier string</li>
                            <li><code style="color: #10b981;">label</code>: Tab title label</li>
                            <li><code style="color: #10b981;">content</code>: Nested SDUI node</li>
                        </ul>
                    </div>
                </div>

                <!-- SubjectGrid -->
                <div class="sdui-card space-y-2">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                        <h4 style="font-weight: 700; font-size: 0.9375rem;">SubjectGrid</h4>
                        <span style="background-color: #fef3c7; color: #d97706; font-size: 0.6875rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.25rem;">Structural</span>
                    </div>
                    <p style="font-size: 0.78125rem; color: #64748b;">Renders subject tiles grid (1, 2, 3, or 4 columns) launching tests directly.</p>
                    <div style="font-size: 0.75rem; line-height: 1.5;">
                        <p style="font-weight: 700; color: #334155;">Props (Provide items OR moduleQuery):</p>
                        <ul style="list-style-type: disc; padding-left: 1rem; font-family: monospace; color: #475569;">
                            <li><code style="color: #f59e0b;">items</code>: Manual array of items</li>
                            <li><code style="color: #10b981;">moduleQuery</code>: Object to auto-populate (e.g. <code style="color: #475569;">{"module_type": "preparation", "slug_contains": "verbal"}</code>)</li>
                            <li><code style="color: #f59e0b;">columns</code>: Grid columns (default: 3)</li>
                        </ul>
                    </div>
                </div>

                <!-- McqPlayer -->
                <div class="sdui-card space-y-2">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                        <h4 style="font-weight: 700; font-size: 0.9375rem;">McqPlayer</h4>
                        <span style="background-color: #dcfce7; color: #15803d; font-size: 0.6875rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.25rem;">Interactive</span>
                    </div>
                    <p style="font-size: 0.78125rem; color: #64748b;">Full-featured timed MCQ engine with gating, timer, review dialog, and results.</p>
                    <div style="font-size: 0.75rem; line-height: 1.5;">
                        <p style="font-weight: 700; color: #334155;">Mandatory Key:</p>
                        <ul style="list-style-type: disc; padding-left: 1rem; font-family: monospace; color: #475569;">
                            <li><code style="color: #f59e0b;">moduleId</code>: ContentModule ID</li>
                        </ul>
                    </div>
                </div>

                <!-- PdfViewer -->
                <div class="sdui-card space-y-2">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                        <h4 style="font-weight: 700; font-size: 0.9375rem;">PdfViewer</h4>
                        <span style="background-color: #dcfce7; color: #15803d; font-size: 0.6875rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.25rem;">Interactive</span>
                    </div>
                    <p style="font-size: 0.78125rem; color: #64748b;">Embedded PDF reader for syllabus, notes, and past paper documents.</p>
                    <div style="font-size: 0.75rem; line-height: 1.5;">
                        <p style="font-weight: 700; color: #334155;">Mandatory Key:</p>
                        <ul style="list-style-type: disc; padding-left: 1rem; font-family: monospace; color: #475569;">
                            <li><code style="color: #f59e0b;">moduleId</code> or <code style="color: #f59e0b;">props.fileUrl</code></li>
                        </ul>
                    </div>
                </div>

                <!-- ResourceList -->
                <div class="sdui-card space-y-2">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                        <h4 style="font-weight: 700; font-size: 0.9375rem;">ResourceList</h4>
                        <span style="background-color: #fef3c7; color: #d97706; font-size: 0.6875rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 0.25rem;">Structural</span>
                    </div>
                    <p style="font-size: 0.78125rem; color: #64748b;">Vertical list of download links or module launchers for candidates.</p>
                    <div style="font-size: 0.75rem; line-height: 1.5;">
                        <p style="font-weight: 700; color: #334155;">Props (Provide items OR moduleQuery):</p>
                        <ul style="list-style-type: disc; padding-left: 1rem; font-family: monospace; color: #475569;">
                            <li><code style="color: #f59e0b;">items</code>: Manual array of resource items</li>
                            <li><code style="color: #10b981;">moduleQuery</code>: Object to auto-populate (e.g. <code style="color: #475569;">{"module_type": "pdf_repo"}</code>)</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Alpine.js Live Validator Controller -->
    <script>
        function sduiSchemaGuide() {
            return {
                activeTab: 'validator',
                jsonInput: `{\n  "type": "PhaseStepperLayout",\n  "props": {\n    "currentPhase": 1,\n    "phases": [\n      {\n        "number": 1,\n        "title": "Phase 1: Initial Intelligence",\n        "content": {\n          "type": "TabbedLayout",\n          "props": {\n            "defaultTab": "verbal",\n            "tabs": [\n              {\n                "id": "verbal",\n                "label": "Verbal Test",\n                "content": {\n                  "type": "McqPlayer",\n                  "moduleId": 1\n                }\n              }\n            ]\n          }\n        }\n      }\n    ]\n  }\n}`,
                validation: {
                    valid: true,
                    errors: [],
                    warnings: [],
                    nodeCount: 0,
                    maxDepth: 0,
                },
                flatTree: [],

                init() {
                    this.validateJson();
                },

                formatJson() {
                    try {
                        const parsed = JSON.parse(this.jsonInput);
                        this.jsonInput = JSON.stringify(parsed, null, 2);
                        this.validateJson();
                    } catch (e) {
                        alert('Invalid JSON syntax: ' + e.message);
                    }
                },

                clearJson() {
                    this.jsonInput = '';
                    this.validateJson();
                },

                loadTemplate(jsonString) {
                    try {
                        const parsed = JSON.parse(jsonString);
                        this.jsonInput = JSON.stringify(parsed, null, 2);
                        this.activeTab = 'validator';
                        this.validateJson();
                    } catch (e) {
                        console.error('Template parse error', e);
                    }
                },

                copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        alert('JSON snippet copied to clipboard!');
                    });
                },

                validateJson() {
                    const registeredComponents = ['EmptyStateLayout', 'TabbedLayout', 'SubjectGrid', 'PhaseStepperLayout', 'ResourceList', 'VerticalStack', 'McqPlayer', 'PdfViewer', 'SubjectiveUpload', 'ProgressCard'];
                    const typoMap = { 'prop': 'props', 'types': 'type', 'module_id': 'moduleId', 'tab': 'tabs', 'phase': 'phases', 'item': 'items', 'child': 'children' };

                    this.validation.errors = [];
                    this.validation.warnings = [];
                    this.validation.nodeCount = 0;
                    this.validation.maxDepth = 0;
                    this.flatTree = [];

                    if (!this.jsonInput.trim()) {
                        this.validation.valid = true;
                        return;
                    }

                    let parsed = null;
                    try {
                        parsed = JSON.parse(this.jsonInput);
                    } catch (e) {
                        this.validation.valid = false;
                        this.validation.errors.push('JSON Syntax Error: ' + e.message);
                        return;
                    }

                    this.inspectNode(parsed, 1, registeredComponents, typoMap);
                    this.validation.valid = this.validation.errors.length === 0;
                },

                inspectNode(node, depth, registeredComponents, typoMap) {
                    if (!node || typeof node !== 'object' || Array.isArray(node)) {
                        this.validation.errors.push(`Node at depth ${depth} must be a valid JSON object.`);
                        return;
                    }

                    this.validation.nodeCount++;
                    if (depth > this.validation.maxDepth) {
                        this.validation.maxDepth = depth;
                    }

                    // Check for key typos
                    Object.keys(typoMap).forEach(typo => {
                        if (typo in node) {
                            this.validation.errors.push(`Typo detected at depth ${depth}: Key '${typo}' should be '${typoMap[typo]}'.`);
                        }
                    });

                    // Check 'type'
                    if (!node.type || typeof node.type !== 'string') {
                        this.validation.errors.push(`Node at depth ${depth} missing required string property 'type'.`);
                    } else if (!registeredComponents.includes(node.type)) {
                        this.validation.errors.push(`Unknown component type '${node.type}' at depth ${depth}.`);
                    }

                    // Check 'props' typos
                    if (node.props && typeof node.props === 'object') {
                        Object.keys(typoMap).forEach(typo => {
                            if (typo in node.props) {
                                this.validation.errors.push(`Typo in 'props' at depth ${depth}: Key '${typo}' should be '${typoMap[typo]}'.`);
                            }
                        });
                    }

                    // Push to tree inspector
                    this.flatTree.push({
                        depth: depth,
                        type: node.type || 'Unknown',
                        label: node.props?.title || node.props?.defaultTab || '',
                        moduleId: node.moduleId || node.props?.moduleId || null,
                    });

                    const props = node.props || {};

                    // Recursive checks
                    if (node.type === 'TabbedLayout' && Array.isArray(props.tabs)) {
                        props.tabs.forEach((tab, idx) => {
                            if (tab && tab.content) {
                                this.inspectNode(tab.content, depth + 1, registeredComponents, typoMap);
                            }
                        });
                    }

                    if (node.type === 'PhaseStepperLayout' && Array.isArray(props.phases)) {
                        props.phases.forEach((phase, idx) => {
                            if (phase && phase.content) {
                                this.inspectNode(phase.content, depth + 1, registeredComponents, typoMap);
                            }
                        });
                    }

                    if (node.type === 'VerticalStack' && Array.isArray(props.children)) {
                        props.children.forEach(child => {
                            this.inspectNode(child, depth + 1, registeredComponents, typoMap);
                        });
                    }

                    if (node.type === 'SubjectGrid' && Array.isArray(props.items)) {
                        props.items.forEach(item => {
                            if (item && item.type) {
                                this.inspectNode(item, depth + 1, registeredComponents, typoMap);
                            }
                        });
                    }
                }
            };
        }
    </script>
</x-filament-panels::page>
