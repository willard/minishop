import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/Storefront/SupportChatController';

export interface ChatMessage {
    id: string;
    role: 'user' | 'assistant';
    content: string;
}

const messages = ref<ChatMessage[]>([]);
const conversationId = ref<string | null>(null);
const isOpen = ref(false);
const isStreaming = ref(false);

export function useChat() {
    async function sendMessage(text: string): Promise<void> {
        messages.value.push({
            id: crypto.randomUUID(),
            role: 'user',
            content: text,
        });

        const assistantMsg: ChatMessage = {
            id: crypto.randomUUID(),
            role: 'assistant',
            content: '',
        };
        messages.value.push(assistantMsg);
        isStreaming.value = true;

        const csrf =
            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.content ?? '';

        try {
            const response = await fetch(store.url(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    message: text,
                    conversation_id: conversationId.value,
                }),
            });

            const reader = response.body!.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) {
                    break;
                }
                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() ?? '';

                for (const line of lines) {
                    if (!line.startsWith('data: ')) {
                        continue;
                    }
                    const raw = line.slice(6);
                    if (raw === '[DONE]') {
                        return;
                    }
                    try {
                        const event = JSON.parse(raw) as {
                            type: string;
                            delta?: string;
                            id?: string;
                        };
                        if (event.type === 'text_delta' && event.delta) {
                            assistantMsg.content += event.delta;
                        }
                        if (event.type === 'conversation_id' && event.id) {
                            conversationId.value = event.id;
                        }
                    } catch {
                        // ignore unparseable lines
                    }
                }
            }
        } finally {
            isStreaming.value = false;
        }
    }

    function openChat(): void {
        isOpen.value = true;
    }

    function closeChat(): void {
        isOpen.value = false;
    }

    return {
        messages,
        conversationId,
        isOpen,
        isStreaming,
        sendMessage,
        openChat,
        closeChat,
    };
}
