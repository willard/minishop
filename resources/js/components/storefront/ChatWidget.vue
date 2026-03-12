<script setup lang="ts">
import { MessageCircle, X, Loader2, Send } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
import { useChat } from '@/composables/useChat';

const { messages, isOpen, isStreaming, sendMessage, openChat, closeChat } =
    useChat();

const inputText = ref('');
const messagesContainer = ref<HTMLDivElement | null>(null);

watch(
    messages,
    () => {
        nextTick(() => {
            if (messagesContainer.value) {
                messagesContainer.value.scrollTop =
                    messagesContainer.value.scrollHeight;
            }
        });
    },
    { deep: true },
);

function handleSend(): void {
    const text = inputText.value.trim();
    if (!text || isStreaming.value) {
        return;
    }
    inputText.value = '';
    sendMessage(text);
}

function handleKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend();
    }
}
</script>

<template>
    <!-- Floating button -->
    <button
        class="fixed bottom-6 right-6 z-[80] flex size-14 items-center justify-center rounded-full shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl"
        style="background-color: #c05c3a; color: #fff"
        aria-label="Open support chat"
        @click="openChat"
    >
        <MessageCircle class="size-6" />
    </button>

    <!-- Chat panel -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed bottom-20 right-4 z-[80] flex w-80 flex-col overflow-hidden rounded-2xl shadow-2xl sm:w-96"
            style="
                max-height: 500px;
                background-color: #f9f6f0;
                color: #1c1a17;
            "
        >
            <!-- Header -->
            <div
                class="flex shrink-0 items-center justify-between border-b px-4 py-3"
                style="border-color: rgba(28, 26, 23, 0.1)"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex size-7 items-center justify-center rounded-full"
                        style="background-color: #c05c3a"
                    >
                        <MessageCircle class="size-3.5 text-white" />
                    </div>
                    <span
                        class="text-sm font-semibold"
                        style="font-family: 'Cormorant Garamond', serif"
                    >
                        Support Chat
                    </span>
                </div>
                <button
                    class="rounded-full p-1.5 transition-colors hover:bg-black/5"
                    aria-label="Close chat"
                    @click="closeChat"
                >
                    <X class="size-4" />
                </button>
            </div>

            <!-- Messages -->
            <div
                ref="messagesContainer"
                class="flex flex-1 flex-col gap-3 overflow-y-auto p-4"
            >
                <!-- Welcome message -->
                <div
                    v-if="messages.length === 0"
                    class="rounded-xl px-3 py-2 text-sm"
                    style="background-color: rgba(0, 0, 0, 0.05)"
                >
                    Hi there! 👋 How can I help you today?
                </div>

                <template v-for="msg in messages" :key="msg.id">
                    <!-- User message -->
                    <div v-if="msg.role === 'user'" class="flex justify-end">
                        <div
                            class="max-w-[80%] rounded-xl px-3 py-2 text-sm"
                            style="
                                background-color: #1c1a17;
                                color: #f9f6f0;
                            "
                        >
                            {{ msg.content }}
                        </div>
                    </div>

                    <!-- Assistant message -->
                    <div v-else class="flex justify-start">
                        <div
                            class="max-w-[80%] rounded-xl px-3 py-2 text-sm"
                            style="background-color: rgba(0, 0, 0, 0.05)"
                        >
                            <!-- Typing indicator -->
                            <div
                                v-if="
                                    isStreaming &&
                                    msg.content === '' &&
                                    msg ===
                                        messages[messages.length - 1]
                                "
                                class="flex items-center gap-1 py-0.5"
                            >
                                <span
                                    class="size-1.5 animate-bounce rounded-full"
                                    style="
                                        background-color: rgba(28, 26, 23, 0.4);
                                        animation-delay: 0ms;
                                    "
                                />
                                <span
                                    class="size-1.5 animate-bounce rounded-full"
                                    style="
                                        background-color: rgba(28, 26, 23, 0.4);
                                        animation-delay: 150ms;
                                    "
                                />
                                <span
                                    class="size-1.5 animate-bounce rounded-full"
                                    style="
                                        background-color: rgba(28, 26, 23, 0.4);
                                        animation-delay: 300ms;
                                    "
                                />
                            </div>
                            <span v-else>{{ msg.content }}</span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Input -->
            <div
                class="shrink-0 border-t p-3"
                style="border-color: rgba(28, 26, 23, 0.1)"
            >
                <div class="flex items-center gap-2">
                    <input
                        v-model="inputText"
                        type="text"
                        placeholder="Ask anything..."
                        class="flex-1 rounded-full border bg-transparent px-4 py-2 text-sm focus:outline-none"
                        style="border-color: rgba(28, 26, 23, 0.2)"
                        :disabled="isStreaming"
                        @keydown="handleKeydown"
                    />
                    <button
                        class="flex size-9 shrink-0 items-center justify-center rounded-full transition-opacity"
                        style="background-color: #c05c3a; color: #fff"
                        :disabled="isStreaming || !inputText.trim()"
                        :class="{
                            'opacity-50 cursor-not-allowed':
                                isStreaming || !inputText.trim(),
                        }"
                        aria-label="Send message"
                        @click="handleSend"
                    >
                        <Loader2
                            v-if="isStreaming"
                            class="size-4 animate-spin"
                        />
                        <Send v-else class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
