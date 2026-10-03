<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    domain: {
        type: Object,
        required: true,
    },
    activeModule: {
        type: Object,
        default: null,
    },
    activeQuestion: {
        type: Object,
        default: null,
    },
});

const isOpen = ref(false);
const inputMessage = ref('');
const messages = ref([]);
const isStreaming = ref(false);
const isLoadingHistory = ref(false);
const currentStreamingMessage = ref('');
const messagesContainer = ref(null);
const inputRef = ref(null);

// Resizable Chat Drawer State
const drawerWidth = ref(440);
const drawerHeight = ref(620);
const isResizing = ref(false);
const isMaximized = ref(false);
const savedDimensions = ref({ width: 440, height: 620 });

// Pointer drag tracking
let dragType = null; // 'top' | 'left' | 'corner'
let startPointerX = 0;
let startPointerY = 0;
let startW = 440;
let startH = 620;

// Floating Action Button (FAB) Drag State
const fabPosition = ref({ x: null, y: null });
const isDraggingFab = ref(false);
let fabDragStartX = 0;
let fabDragStartY = 0;
let fabInitialX = 0;
let fabInitialY = 0;
let fabHasMoved = false;
const fabRef = ref(null);

const fabStyle = computed(() => {
    if (fabPosition.value.x === null || fabPosition.value.y === null) {
        return {
            bottom: '5.5rem',
            right: '1.5rem',
        };
    }
    return {
        top: `${fabPosition.value.y}px`,
        left: `${fabPosition.value.x}px`,
        bottom: 'auto',
        right: 'auto',
    };
});

const drawerStyle = computed(() => {
    const base = {
        width: `${drawerWidth.value}px`,
        height: `${drawerHeight.value}px`,
        maxWidth: 'calc(100vw - 1.25rem)',
        maxHeight: 'calc(100vh - 6.5rem)',
    };

    if (typeof window === 'undefined' || fabPosition.value.x === null || fabPosition.value.y === null) {
        return {
            ...base,
            bottom: '10rem',
            right: '1.5rem',
        };
    }

    const fabY = fabPosition.value.y;
    const fabX = fabPosition.value.x;

    const style = { ...base };

    if (fabY > window.innerHeight / 2) {
        style.bottom = `${Math.max(16, window.innerHeight - fabY + 12)}px`;
    } else {
        style.top = `${Math.max(16, fabY + 64)}px`;
    }

    const rightOffset = window.innerWidth - (fabX + 180);
    if (rightOffset < 16) {
        style.right = '1rem';
    } else if (rightOffset + drawerWidth.value > window.innerWidth) {
        style.left = '1rem';
    } else {
        style.right = `${Math.max(16, rightOffset)}px`;
    }

    return style;
});

const onFabPointerDown = (e) => {
    if (e.button !== 0 && e.pointerType === 'mouse') return;

    const fabEl = fabRef.value;
    if (!fabEl) return;

    const rect = fabEl.getBoundingClientRect();
    fabDragStartX = e.clientX;
    fabDragStartY = e.clientY;
    fabInitialX = rect.left;
    fabInitialY = rect.top;
    fabHasMoved = false;
    isDraggingFab.value = true;

    try {
        fabEl.setPointerCapture(e.pointerId);
    } catch (_) {}
};

const onFabPointerMove = (e) => {
    if (!isDraggingFab.value) return;

    const deltaX = e.clientX - fabDragStartX;
    const deltaY = e.clientY - fabDragStartY;

    if (!fabHasMoved && (Math.abs(deltaX) > 4 || Math.abs(deltaY) > 4)) {
        fabHasMoved = true;
    }

    if (fabHasMoved) {
        const fabEl = fabRef.value;
        const width = fabEl ? fabEl.offsetWidth : 170;
        const height = fabEl ? fabEl.offsetHeight : 56;

        let newX = fabInitialX + deltaX;
        let newY = fabInitialY + deltaY;

        const padding = 12;
        newX = Math.max(padding, Math.min(newX, window.innerWidth - width - padding));
        newY = Math.max(padding, Math.min(newY, window.innerHeight - height - padding));

        fabPosition.value = { x: Math.round(newX), y: Math.round(newY) };
    }
};

let justDragged = false;

const handleClick = (e) => {
    if (justDragged) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }
    toggleOpen();
};

const onFabPointerUp = (e) => {
    if (!isDraggingFab.value) return;

    const fabEl = fabRef.value;
    if (fabEl && fabEl.hasPointerCapture && fabEl.hasPointerCapture(e.pointerId)) {
        try {
            fabEl.releasePointerCapture(e.pointerId);
        } catch (_) {}
    }

    const wasDragged = fabHasMoved;
    isDraggingFab.value = false;

    if (wasDragged) {
        justDragged = true;
        setTimeout(() => {
            justDragged = false;
        }, 150);
        try {
            sessionStorage.setItem('ai_tutor_fab_pos', JSON.stringify(fabPosition.value));
        } catch (_) {}
    }
};

const domainSlug = computed(() => props.domain?.slug || '');

const quickChips = [
    { label: '💡 Socratic Hint', prompt: 'Can you give me a conceptual hint for this problem without spoiling the answer?' },
    { label: '🔍 Analyze Pattern', prompt: 'Break down the pattern and logical structure step-by-step.' },
    { label: '🎯 Exam Strategy', prompt: 'What is the optimal time management strategy and scoring formula for this test?' },
    { label: '❌ Rule of Elimination', prompt: 'Which choices can be immediately eliminated and why?' },
];

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const fetchHistory = async () => {
    if (!domainSlug.value) return;
    isLoadingHistory.value = true;
    try {
        const response = await fetch(`/api/domains/${domainSlug.value}/ai-tutor/messages`);
        if (response.ok) {
            const data = await response.json();
            if (data.status === 'success') {
                messages.value = data.messages || [];
                await scrollToBottom();
            }
        }
    } catch (err) {
        console.error('Error fetching tutor history:', err);
    } finally {
        isLoadingHistory.value = false;
    }
};

const toggleOpen = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        initDrawerDimensions();
        if (messages.value.length === 0) {
            fetchHistory();
        } else {
            scrollToBottom();
        }
        nextTick(() => {
            inputRef.value?.focus();
        });
    }
};

const clearHistory = async () => {
    if (!confirm('Clear your conversation history with the AI Exam Mentor for this domain?')) {
        return;
    }

    try {
        const response = await fetch(`/api/domains/${domainSlug.value}/ai-tutor/clear`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            messages.value = [];
        }
    } catch (err) {
        console.error('Error clearing tutor history:', err);
    }
};

const sendMessage = async (presetText = null) => {
    const textToSend = (presetText || inputMessage.value).trim();
    if (!textToSend || isStreaming.value || !domainSlug.value) return;

    // Add user message to local UI
    messages.value.push({
        id: Date.now(),
        role: 'user',
        content: textToSend,
        createdAt: 'Just now',
    });

    inputMessage.value = '';
    isStreaming.value = true;
    currentStreamingMessage.value = '';
    await scrollToBottom();

    // Prepare active candidate context matching agent_architecture_guidelines contract
    const activeMcq = props.activeQuestion
        ? {
              id: props.activeQuestion.id,
              statement: props.activeQuestion.question_text
                  ? props.activeQuestion.question_text
                        .replace(/<[^>]*>?/gm, '')
                        .trim()
                  : '',
              options: Array.isArray(props.activeQuestion.payload?.options)
                  ? props.activeQuestion.payload.options.map((opt, idx) => {
                        const letter = String.fromCharCode(65 + idx);
                        const rawText =
                            typeof opt === 'object'
                                ? opt?.text || ''
                                : String(opt ?? '');
                        const cleanText = rawText
                            .replace(/<[^>]*>?/gm, '')
                            .trim();
                        return `${letter}) ${cleanText}`;
                    })
                  : [],
              user_selected: props.activeQuestion.user_selected || null,
              attempts_made: props.activeQuestion.attempts_made || 0,
          }
        : null;

    const contextPayload = {
        current_domain_id: props.domain?.id || null,
        current_domain_name: props.domain?.name || null,
        active_mcq: activeMcq,
        student_level: 'intermediate',
        active_module_id: props.activeModule?.id || null,
        active_question_id: props.activeQuestion?.id || null,
        active_question_text: activeMcq?.statement || null,
    };

    const chatHistoryPayload = messages.value.slice(-6).map((m) => ({
        role: m.role,
        content: m.content,
    }));

    try {
        const response = await fetch(
            `/api/domains/${domainSlug.value}/ai-tutor/ask`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    message: textToSend,
                    context: contextPayload,
                    chat_history: chatHistoryPayload,
                    stream: true,
                }),
            },
        );

        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            messages.value.push({
                id: Date.now() + 1,
                role: 'assistant',
                content: `⚠️ ${errData.message || 'Mentor is temporarily unavailable. Please try again.'}`,
                createdAt: 'Just now',
            });
            isStreaming.value = false;
            await scrollToBottom();
            return;
        }

        // Handle SSE Stream
        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');
        let done = false;
        let buffer = '';

        while (!done) {
            const { value, done: streamDone } = await reader.read();
            done = streamDone;

            if (value) {
                buffer += decoder.decode(value, { stream: true });
                const blocks = buffer.split('\n\n');
                buffer = blocks.pop() || '';

                for (const block of blocks) {
                    if (!block.trim()) continue;

                    const blockLines = block.split('\n');
                    let currentEvent = 'chunk';
                    let dataLine = '';

                    for (const bl of blockLines) {
                        const trimmed = bl.trim();
                        if (trimmed.startsWith('event:')) {
                            currentEvent = trimmed.replace(/^event:\s*/, '').trim();
                        } else if (trimmed.startsWith('data:')) {
                            dataLine = trimmed.replace(/^data:\s*/, '').trim();
                        }
                    }

                    if (dataLine === '[DONE]' || currentEvent === 'done') {
                        done = true;
                        break;
                    }

                    if (currentEvent === 'error') {
                        try {
                            const errObj = JSON.parse(dataLine);
                            currentStreamingMessage.value += `\n\n⚠️ ${errObj.message || 'An error occurred.'}`;
                        } catch (e) {
                            currentStreamingMessage.value += `\n\n⚠️ ${dataLine}`;
                        }
                        done = true;
                        break;
                    }

                    if (dataLine && dataLine !== '{}') {
                        try {
                            const parsed = JSON.parse(dataLine);
                            const token = parsed.text ?? parsed.token ?? '';
                            if (token) {
                                currentStreamingMessage.value += token;
                                await scrollToBottom();
                            }
                        } catch (e) {
                            currentStreamingMessage.value += dataLine;
                            await scrollToBottom();
                        }
                    }
                }
            }
        }

        // Flush any trailing buffer data
        if (buffer.trim()) {
            const blockLines = buffer.split('\n');
            let dataLine = '';
            for (const bl of blockLines) {
                const trimmed = bl.trim();
                if (trimmed.startsWith('data:')) {
                    dataLine = trimmed.replace(/^data:\s*/, '').trim();
                }
            }
            if (dataLine && dataLine !== '[DONE]' && dataLine !== '{}') {
                try {
                    const parsed = JSON.parse(dataLine);
                    const token = parsed.text ?? parsed.token ?? '';
                    if (token) {
                        currentStreamingMessage.value += token;
                    }
                } catch (e) {
                    currentStreamingMessage.value += dataLine;
                }
            }
        }

        // Finalize assistant message
        if (currentStreamingMessage.value) {
            messages.value.push({
                id: Date.now() + 2,
                role: 'assistant',
                content: currentStreamingMessage.value,
                createdAt: 'Just now',
            });
            currentStreamingMessage.value = '';
        }
    } catch (err) {
        console.error('Error querying tutor:', err);
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            content: '⚠️ Network connection lost. Please check your connection and try again.',
            createdAt: 'Just now',
        });
    } finally {
        isStreaming.value = false;
        currentStreamingMessage.value = '';
        await scrollToBottom();
    }
};

// Resize and Drag Logic (Mouse cursor & Touch swipe)
const initDrawerDimensions = () => {
    if (typeof window === 'undefined') return;
    const maxW = Math.min(880, window.innerWidth - 24);
    const maxH = window.innerHeight - 110;

    drawerWidth.value = Math.min(440, maxW);
    drawerHeight.value = Math.min(620, Math.max(380, Math.floor(window.innerHeight * 0.78)));
};

const handleWindowResize = () => {
    if (typeof window === 'undefined') return;
    const maxW = Math.min(880, window.innerWidth - 24);
    const maxH = window.innerHeight - 110;
    if (drawerWidth.value > maxW) drawerWidth.value = maxW;
    if (drawerHeight.value > maxH) drawerHeight.value = maxH;

    if (fabPosition.value.x !== null && fabPosition.value.y !== null) {
        const padding = 12;
        const maxX = Math.max(padding, window.innerWidth - 180);
        const maxY = Math.max(padding, window.innerHeight - 70);
        fabPosition.value = {
            x: Math.max(padding, Math.min(fabPosition.value.x, maxX)),
            y: Math.max(padding, Math.min(fabPosition.value.y, maxY)),
        };
    }
};

const startResize = (type, event) => {
    event.preventDefault();
    event.stopPropagation();

    dragType = type;
    startPointerX = event.clientX;
    startPointerY = event.clientY;
    startW = drawerWidth.value;
    startH = drawerHeight.value;
    isResizing.value = true;

    if (event.target && event.target.setPointerCapture) {
        try {
            event.target.setPointerCapture(event.pointerId);
        } catch {
            // pointer capture fallback
        }
    }

    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onPointerUp);
    window.addEventListener('pointercancel', onPointerUp);
};

const onPointerMove = (event) => {
    if (!isResizing.value || !dragType) return;

    const minW = Math.min(320, window.innerWidth - 24);
    const maxW = Math.min(900, window.innerWidth - 24);
    const minH = 350;
    const maxH = window.innerHeight - 100;

    // deltaY: dragging UP (decreasing clientY) increases drawer height
    if (dragType === 'top' || dragType === 'corner') {
        const deltaY = startPointerY - event.clientY;
        const newH = Math.max(minH, Math.min(maxH, startH + deltaY));
        drawerHeight.value = Math.round(newH);
    }

    // deltaX: dragging LEFT (decreasing clientX) increases drawer width
    if (dragType === 'left' || dragType === 'corner') {
        const deltaX = startPointerX - event.clientX;
        const newW = Math.max(minW, Math.min(maxW, startW + deltaX));
        drawerWidth.value = Math.round(newW);
    }
};

const onPointerUp = (event) => {
    if (!isResizing.value) return;
    isResizing.value = false;
    dragType = null;

    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
    window.removeEventListener('pointercancel', onPointerUp);
};

const toggleMaximize = () => {
    isMaximized.value = !isMaximized.value;
    if (isMaximized.value) {
        savedDimensions.value = { width: drawerWidth.value, height: drawerHeight.value };
        drawerWidth.value = Math.min(860, window.innerWidth - 24);
        drawerHeight.value = window.innerHeight - 105;
    } else {
        drawerWidth.value = savedDimensions.value.width;
        drawerHeight.value = savedDimensions.value.height;
    }
};

// Keyboard shortcut (Escape to close)
const onKeydown = (e) => {
    if (e.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }
};

onMounted(() => {
    initDrawerDimensions();
    try {
        const saved = sessionStorage.getItem('ai_tutor_fab_pos');
        if (saved) {
            const parsed = JSON.parse(saved);
            if (typeof parsed.x === 'number' && typeof parsed.y === 'number') {
                const padding = 12;
                const maxX = Math.max(padding, window.innerWidth - 180);
                const maxY = Math.max(padding, window.innerHeight - 70);
                fabPosition.value = {
                    x: Math.max(padding, Math.min(parsed.x, maxX)),
                    y: Math.max(padding, Math.min(parsed.y, maxY)),
                };
            }
        }
    } catch (_) {}
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', handleWindowResize);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', handleWindowResize);
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
    window.removeEventListener('pointercancel', onPointerUp);
});

// Format markdown headings, bold, lists, inline code, and line breaks
const formatContent = (text) => {
    if (!text) return '';
    let html = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // Inline code `code`
    html = html.replace(/`([^`]+)`/g, '<code class="rounded bg-muted/80 px-1.5 py-0.5 font-mono text-xs text-foreground">$1</code>');

    // Bold **text**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold text-foreground">$1</strong>');

    // Headings (H1 to H4) - strip raw hash symbols and format as clean, elegant typography
    html = html.replace(/^\s*####\s+(.*)$/gm, '<h5 class="font-bold text-xs uppercase tracking-wide text-foreground mt-2 mb-0.5">$1</h5>');
    html = html.replace(/^\s*###\s+(.*)$/gm, '<h4 class="font-bold text-sm text-foreground mt-2 mb-0.5">$1</h4>');
    html = html.replace(/^\s*##\s+(.*)$/gm, '<h3 class="font-bold text-sm text-foreground mt-2.5 mb-1">$1</h3>');
    html = html.replace(/^\s*#\s+(.*)$/gm, '<h2 class="font-bold text-base text-foreground mt-3 mb-1">$1</h2>');

    // Strip any accidental remaining leading hashes (e.g. ### without space)
    html = html.replace(/^\s*#{1,6}\s*/gm, '');

    // Numbered lists e.g. "1. Step"
    html = html.replace(/^\s*(\d+)\.\s+(.*)$/gm, '<li class="ml-4 list-decimal" value="$1">$2</li>');

    // Bullet lists e.g. "- Point"
    html = html.replace(/^\s*[-*]\s+(.*)$/gm, '<li class="ml-4 list-disc">$1</li>');

    // Line breaks
    html = html.replace(/\n/g, '<br />');

    // Clean up excessive <br /> around headings and list items
    html = html.replace(/(<\/h[2-5]>)(<br \/>)+/g, '$1');
    html = html.replace(/(<br \/>)+(<h[2-5])/g, '$2');
    html = html.replace(/(<\/li>)(<br \/>)+/g, '$1');

    return html;
};
</script>

<template>
    <!-- Floating Action Button (FAB) — Draggable Anywhere on Screen -->
    <div
        ref="fabRef"
        class="fixed z-50 touch-none select-none"
        :style="fabStyle"
        @pointerdown="onFabPointerDown"
        @pointermove="onFabPointerMove"
        @pointerup="onFabPointerUp"
        @pointercancel="onFabPointerUp"
    >
        <button
            type="button"
            @click="handleClick"
            class="group relative flex h-14 items-center gap-2.5 rounded-full border border-emerald-500/30 bg-card/95 px-4 sm:px-5 py-3 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-md transition-shadow duration-200 hover:border-emerald-500 hover:bg-card hover:shadow-emerald-500/10 dark:bg-zinc-900/95 dark:hover:border-emerald-400 cursor-grab active:cursor-grabbing"
            :class="{ 'ring-2 ring-emerald-500/40': isOpen }"
            aria-label="Toggle AI Exam Mentor (Drag to reposition)"
            title="Drag to reposition anywhere on screen • Click to toggle AI Exam Mentor"
        >
            <!-- Grip Drag Indicator Dots -->
            <span class="flex flex-col gap-0.5 opacity-40 group-hover:opacity-80 transition-opacity">
                <span class="flex gap-0.5">
                    <span class="h-1 w-1 rounded-full bg-current"></span>
                    <span class="h-1 w-1 rounded-full bg-current"></span>
                </span>
                <span class="flex gap-0.5">
                    <span class="h-1 w-1 rounded-full bg-current"></span>
                    <span class="h-1 w-1 rounded-full bg-current"></span>
                </span>
                <span class="flex gap-0.5">
                    <span class="h-1 w-1 rounded-full bg-current"></span>
                    <span class="h-1 w-1 rounded-full bg-current"></span>
                </span>
            </span>

            <!-- Tactical Pulse Dot -->
            <span class="relative flex h-2.5 w-2.5">
                <span
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                ></span>
                <span
                    class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"
                ></span>
            </span>

            <!-- Icon -->
            <svg
                v-if="!isOpen"
                class="h-5 w-5 text-emerald-500 transition-transform duration-200 group-hover:scale-110 dark:text-emerald-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.8"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"
                />
            </svg>
            <svg
                v-else
                class="h-5 w-5 text-muted-foreground transition-transform duration-200 group-hover:rotate-90"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>

            <span class="tracking-tight font-medium">AI Exam Mentor</span>
        </button>
    </div>

    <!-- Sliding Chat Surface (Desktop drawer / Mobile full modal) -->
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-6 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-6 scale-95"
    >
        <div
            v-if="isOpen"
            :style="drawerStyle"
            :class="{ 'transition-none select-none': isResizing }"
            class="fixed z-50 flex flex-col rounded-2xl border border-sidebar-border bg-card/98 shadow-2xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950/98"
        >
            <!-- Top Resize & Swipe Handle (Mouse drag + Touch swipe) -->
            <div
                @pointerdown="startResize('top', $event)"
                @dblclick="toggleMaximize"
                class="group absolute -top-2 left-4 right-4 h-5 cursor-ns-resize z-30 touch-none flex items-center justify-center"
                title="Drag or swipe vertically to resize height (Double-click to toggle maximize)"
            >
                <div class="h-1 w-12 rounded-full bg-muted-foreground/35 group-hover:bg-emerald-500/80 group-active:bg-emerald-500 transition-colors"></div>
            </div>

            <!-- Left Resize & Swipe Handle (Mouse drag + Touch swipe) -->
            <div
                @pointerdown="startResize('left', $event)"
                class="group absolute top-4 bottom-4 -left-2 w-4 cursor-ew-resize z-30 touch-none flex items-center justify-center"
                title="Drag or swipe horizontally to resize width"
            >
                <div class="w-1 h-12 rounded-full bg-muted-foreground/25 group-hover:bg-emerald-500/80 group-active:bg-emerald-500 transition-colors opacity-0 group-hover:opacity-100"></div>
            </div>

            <!-- Top-Left Corner Resize Handle -->
            <div
                @pointerdown="startResize('corner', $event)"
                class="group absolute -top-2 -left-2 h-6 w-6 cursor-nwse-resize z-40 touch-none flex items-center justify-center"
                title="Drag or swipe diagonally to resize height and width"
            >
                <div class="h-2.5 w-2.5 rounded-tl-sm border-t-2 border-l-2 border-muted-foreground/40 group-hover:border-emerald-500 group-active:border-emerald-500 transition-colors"></div>
            </div>

            <!-- Main Inner Content Wrapper -->
            <div class="flex h-full w-full flex-col overflow-hidden rounded-2xl">
                <!-- Mentor Header -->
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3 dark:border-zinc-800/80"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold tracking-tight text-foreground">
                                {{ domain.name }} Mentor
                            </h3>
                            <p class="text-[11px] font-mono text-muted-foreground">
                                Socratic Guidance &bull; pgvector RAG
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <!-- Maximize / Restore Toggle -->
                        <button
                            type="button"
                            @click="toggleMaximize"
                            :title="isMaximized ? 'Restore Default Size' : 'Expand / Maximize Drawer'"
                            class="rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                        >
                            <svg v-if="!isMaximized" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="clearHistory"
                            title="Clear Conversation"
                            class="rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="isOpen = false"
                            title="Close Drawer"
                            class="rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

            <!-- Active Test Context Ribbon -->
            <div
                v-if="activeModule || activeQuestion"
                class="flex items-center gap-2 border-b border-sidebar-border/50 bg-emerald-500/5 px-4 py-1.5 text-xs text-emerald-600 dark:border-zinc-800/60 dark:text-emerald-400"
            >
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <span class="truncate font-medium">
                    {{ activeModule?.title || 'Active Module' }}
                    <span v-if="activeQuestion?.code" class="font-mono text-muted-foreground">
                        ({{ activeQuestion.code }})
                    </span>
                </span>
            </div>

            <!-- Messages Stream Area -->
            <div
                ref="messagesContainer"
                class="flex-1 overflow-y-auto p-4 space-y-3.5 text-sm leading-relaxed"
            >
                <!-- Loading State -->
                <div v-if="isLoadingHistory" class="flex justify-center py-6">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent"></div>
                </div>

                <!-- Welcome State -->
                <div
                    v-else-if="messages.length === 0 && !currentStreamingMessage"
                    class="flex flex-col items-center justify-center gap-2 py-8 text-center text-muted-foreground"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-muted/60 text-emerald-500"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-foreground">
                        Welcome to your {{ domain.name }} Mentor
                    </p>
                    <p class="max-w-[300px] text-xs leading-normal">
                        Ask about question patterns, formulas, elimination rules, or exam guidelines.
                    </p>
                </div>

                <!-- Chat History Messages -->
                <template v-for="msg in messages" :key="msg.id">
                    <!-- User Bubble -->
                    <div v-if="msg.role === 'user'" class="flex justify-end">
                        <div
                            class="max-w-[85%] rounded-2xl rounded-br-sm bg-primary px-3.5 py-2.5 text-primary-foreground shadow-sm"
                        >
                            {{ msg.content }}
                        </div>
                    </div>

                    <!-- Assistant Bubble -->
                    <div v-else class="flex justify-start">
                        <div
                            class="max-w-[90%] rounded-2xl rounded-bl-sm border border-border/70 bg-muted/40 px-3.5 py-2.5 text-foreground shadow-sm dark:bg-zinc-900/60"
                        >
                            <div class="prose prose-sm dark:prose-invert max-w-none" v-html="formatContent(msg.content)"></div>
                        </div>
                    </div>
                </template>

                <!-- Currently Streaming Token Bubble -->
                <div v-if="isStreaming && currentStreamingMessage" class="flex justify-start">
                    <div
                        class="max-w-[90%] rounded-2xl rounded-bl-sm border border-emerald-500/30 bg-muted/40 px-3.5 py-2.5 text-foreground shadow-sm dark:bg-zinc-900/60"
                    >
                        <div class="prose prose-sm dark:prose-invert max-w-none" v-html="formatContent(currentStreamingMessage)"></div>
                        <span class="inline-block h-3.5 w-1.5 animate-pulse bg-emerald-500 ml-1"></span>
                    </div>
                </div>

                <!-- Streaming Waiting Dot Animation -->
                <div v-if="isStreaming && !currentStreamingMessage" class="flex justify-start">
                    <div
                        class="flex items-center gap-1.5 rounded-2xl rounded-bl-sm border border-border/70 bg-muted/40 px-4 py-3"
                    >
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-emerald-500"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-emerald-500 [animation-delay:0.2s]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-emerald-500 [animation-delay:0.4s]"></span>
                    </div>
                </div>
            </div>

            <!-- Quick Socratic Suggestion Chips -->
            <div class="border-t border-sidebar-border/60 bg-muted/20 px-3 py-2 dark:border-zinc-800/60">
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-0.5">
                    <button
                        v-for="chip in quickChips"
                        :key="chip.label"
                        type="button"
                        :disabled="isStreaming"
                        @click="sendMessage(chip.prompt)"
                        class="whitespace-nowrap rounded-md border border-border/60 bg-card px-2.5 py-1 text-[11px] font-medium text-muted-foreground hover:border-emerald-500 hover:text-foreground disabled:opacity-50 transition-colors"
                    >
                        {{ chip.label }}
                    </button>
                </div>
            </div>

            <!-- Input Form Bar -->
            <form
                @submit.prevent="sendMessage()"
                class="border-t border-sidebar-border/80 p-3 bg-card dark:border-zinc-800 dark:bg-zinc-950"
            >
                <div class="relative flex items-center">
                    <textarea
                        ref="inputRef"
                        v-model="inputMessage"
                        :disabled="isStreaming"
                        maxlength="3000"
                        rows="1"
                        placeholder="Ask a question or request a hint..."
                        @keydown.enter.exact.prevent="sendMessage()"
                        class="w-full resize-none rounded-xl border border-input bg-background/80 py-2.5 pl-3.5 pr-11 text-sm text-foreground placeholder:text-muted-foreground focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:opacity-50"
                    ></textarea>

                    <button
                        type="submit"
                        :disabled="!inputMessage.trim() || isStreaming"
                        class="absolute right-2 flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-30 disabled:hover:bg-emerald-600 transition-colors"
                        title="Send Message"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                    </button>
                </div>
                <div class="mt-1 flex justify-between px-1 text-[10px] text-muted-foreground">
                    <span>Press Enter to send &bull; Shift+Enter for newline</span>
                    <span class="font-mono">Socratic Mode</span>
                </div>
            </form>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
