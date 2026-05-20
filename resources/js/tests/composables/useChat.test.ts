import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/actions/Minishop/Http/Controllers/Storefront/SupportChatController', () => ({
    store: Object.assign(vi.fn(() => ({ url: '/chat', method: 'post' })), {
        url: vi.fn(() => '/chat'),
        definition: { methods: ['post'], url: '/chat' },
        post: vi.fn(),
        form: vi.fn(),
    }),
    default: { store: vi.fn() },
}));

import { useChat } from '@/composables/useChat';

function createSseStream(events: string[]) {
    const encoder = new TextEncoder();
    const data = events.map((e) => `data: ${e}\n\n`).join('');
    return new ReadableStream({
        start(controller) {
            controller.enqueue(encoder.encode(data));
            controller.close();
        },
    });
}

beforeEach(() => {
    // Reset module-level singleton refs between tests
    const { messages, conversationId, isOpen, isStreaming, closeChat } = useChat();
    messages.value = [];
    conversationId.value = null;
    isOpen.value = false;
    isStreaming.value = false;
    closeChat();
    vi.unstubAllGlobals();
});

describe('useChat', () => {
    it('starts with empty state', () => {
        const { messages, conversationId, isOpen, isStreaming } = useChat();
        expect(messages.value).toHaveLength(0);
        expect(conversationId.value).toBeNull();
        expect(isOpen.value).toBe(false);
        expect(isStreaming.value).toBe(false);
    });

    it('openChat sets isOpen to true', () => {
        const { isOpen, openChat } = useChat();
        openChat();
        expect(isOpen.value).toBe(true);
    });

    it('closeChat sets isOpen to false', () => {
        const { isOpen, openChat, closeChat } = useChat();
        openChat();
        closeChat();
        expect(isOpen.value).toBe(false);
    });

    it('sendMessage adds a user message and an assistant message', async () => {
        const { messages, sendMessage } = useChat();
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.resolve({ body: createSseStream(['[DONE]']) })),
        );

        await sendMessage('Hello');

        expect(messages.value).toHaveLength(2);
        expect(messages.value[0].role).toBe('user');
        expect(messages.value[0].content).toBe('Hello');
        expect(messages.value[1].role).toBe('assistant');
    });

    it('text_delta events accumulate into the assistant message', async () => {
        const { messages, sendMessage } = useChat();
        vi.stubGlobal(
            'fetch',
            vi.fn(() =>
                Promise.resolve({
                    body: createSseStream([
                        JSON.stringify({ type: 'text_delta', delta: 'Hello' }),
                        JSON.stringify({ type: 'text_delta', delta: ' World' }),
                        '[DONE]',
                    ]),
                }),
            ),
        );

        await sendMessage('Hi');

        expect(messages.value[1].content).toBe('Hello World');
    });

    it('conversation_id event updates conversationId', async () => {
        const { conversationId, sendMessage } = useChat();
        const uuid = '550e8400-e29b-41d4-a716-446655440000';
        vi.stubGlobal(
            'fetch',
            vi.fn(() =>
                Promise.resolve({
                    body: createSseStream([
                        JSON.stringify({ type: 'conversation_id', id: uuid }),
                        '[DONE]',
                    ]),
                }),
            ),
        );

        await sendMessage('Hi');

        expect(conversationId.value).toBe(uuid);
    });

    it('isStreaming is false after sendMessage completes', async () => {
        const { isStreaming, sendMessage } = useChat();
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.resolve({ body: createSseStream(['[DONE]']) })),
        );

        await sendMessage('Hi');

        expect(isStreaming.value).toBe(false);
    });

    it('resets isStreaming to false when fetch throws', async () => {
        const { isStreaming, sendMessage } = useChat();
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.reject(new Error('Network error'))),
        );

        try {
            await sendMessage('Hi');
        } catch {
            // expected
        }

        expect(isStreaming.value).toBe(false);
    });

    it('calls the Wayfinder store URL for the fetch request', async () => {
        const { sendMessage } = useChat();
        const mockFetch = vi.fn(() =>
            Promise.resolve({ body: createSseStream(['[DONE]']) }),
        );
        vi.stubGlobal('fetch', mockFetch);

        await sendMessage('Hi');

        expect(mockFetch).toHaveBeenCalledWith('/chat', expect.objectContaining({ method: 'POST' }));
    });

    it('ignores unparseable SSE data lines without throwing', async () => {
        const { messages, sendMessage } = useChat();
        vi.stubGlobal(
            'fetch',
            vi.fn(() =>
                Promise.resolve({
                    body: createSseStream(['not-valid-json', '[DONE]']),
                }),
            ),
        );

        await expect(sendMessage('Hi')).resolves.toBeUndefined();
        expect(messages.value).toHaveLength(2);
    });
});
