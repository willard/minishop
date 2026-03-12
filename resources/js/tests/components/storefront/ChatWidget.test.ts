import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import ChatWidget from '@/components/storefront/ChatWidget.vue';
import { useChat } from '@/composables/useChat';

vi.mock('@/composables/useChat', () => ({
    useChat: vi.fn(),
}));

describe('ChatWidget', () => {
    const messagesRef = ref<any[]>([]);
    const isOpenRef = ref(false);
    const isStreamingRef = ref(false);
    const conversationIdRef = ref<string | null>(null);

    let mockSendMessage: ReturnType<typeof vi.fn>;
    let mockOpenChat: ReturnType<typeof vi.fn>;
    let mockCloseChat: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        messagesRef.value = [];
        isOpenRef.value = false;
        isStreamingRef.value = false;
        conversationIdRef.value = null;

        mockSendMessage = vi.fn();
        mockOpenChat = vi.fn();
        mockCloseChat = vi.fn();

        (useChat as any).mockReturnValue({
            messages: messagesRef,
            isOpen: isOpenRef,
            isStreaming: isStreamingRef,
            conversationId: conversationIdRef,
            sendMessage: mockSendMessage,
            openChat: mockOpenChat,
            closeChat: mockCloseChat,
        });
    });

    it('renders the floating chat button', () => {
        const wrapper = mount(ChatWidget);
        const button = wrapper.find('button[aria-label="Open support chat"]');
        expect(button.exists()).toBe(true);
    });

    it('does not render the chat panel when closed', () => {
        isOpenRef.value = false;
        const wrapper = mount(ChatWidget);
        expect(wrapper.find('[aria-label="Close chat"]').exists()).toBe(false);
    });

    it('shows panel and welcome message when open', () => {
        isOpenRef.value = true;
        const wrapper = mount(ChatWidget);
        expect(wrapper.text()).toContain('Support Chat');
        expect(wrapper.text()).toContain('Hi there');
    });

    it('renders user and assistant messages', () => {
        isOpenRef.value = true;
        messagesRef.value = [
            { id: '1', role: 'user', content: 'Hello there' },
            { id: '2', role: 'assistant', content: 'Hi! How can I help?' },
        ];

        const wrapper = mount(ChatWidget);
        expect(wrapper.text()).toContain('Hello there');
        expect(wrapper.text()).toContain('Hi! How can I help?');
    });

    it('calls sendMessage when send button is clicked', async () => {
        isOpenRef.value = true;
        const wrapper = mount(ChatWidget);

        const input = wrapper.find('input[type="text"]');
        await input.setValue('What are your products?');

        const sendButton = wrapper.find('button[aria-label="Send message"]');
        await sendButton.trigger('click');

        expect(mockSendMessage).toHaveBeenCalledWith('What are your products?');
    });

    it('calls closeChat when X button is clicked', async () => {
        isOpenRef.value = true;
        const wrapper = mount(ChatWidget);

        const closeButton = wrapper.find('button[aria-label="Close chat"]');
        await closeButton.trigger('click');

        expect(mockCloseChat).toHaveBeenCalled();
    });

    it('disables send button while streaming', () => {
        isOpenRef.value = true;
        isStreamingRef.value = true;

        const wrapper = mount(ChatWidget);
        const sendButton = wrapper.find('button[aria-label="Send message"]');
        expect(sendButton.attributes('disabled')).toBeDefined();
    });
});
