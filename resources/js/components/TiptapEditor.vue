<script setup lang="ts">
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import StarterKit from '@tiptap/starter-kit';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import {
    Bold,
    Italic,
    Strikethrough,
    Heading2,
    Heading3,
    List,
    ListOrdered,
    Quote,
    Code,
    Link as LinkIcon,
    Image as ImageIcon,
    Undo2,
    Redo2,
} from 'lucide-vue-next';
import { watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-primary underline underline-offset-2',
            },
        }),
        Image.configure({
            HTMLAttributes: {
                class: 'rounded-lg max-w-full h-auto',
            },
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm dark:prose-invert max-w-none min-h-[240px] rounded-b-md border border-t-0 border-input bg-background px-4 py-3 focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;
        if (editor.value.getHTML() === value) return;
        editor.value.commands.setContent(value, { emitUpdate: false });
    },
);

function addLink() {
    if (!editor.value) return;
    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('URL', previousUrl ?? '');
    if (url === null) return;
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

function addImage() {
    if (!editor.value) return;
    const url = window.prompt('Image URL');
    if (url) editor.value.chain().focus().setImage({ src: url }).run();
}
</script>

<template>
    <div v-if="editor" class="flex flex-col">
        <div
            class="flex flex-wrap items-center gap-1 rounded-t-md border border-input bg-muted/40 px-2 py-1.5"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('bold') }"
                @click="editor.chain().focus().toggleBold().run()"
                aria-label="Bold"
            >
                <Bold class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('italic') }"
                @click="editor.chain().focus().toggleItalic().run()"
                aria-label="Italic"
            >
                <Italic class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('strike') }"
                @click="editor.chain().focus().toggleStrike().run()"
                aria-label="Strikethrough"
            >
                <Strikethrough class="size-4" />
            </Button>
            <div class="mx-1 h-5 w-px bg-border" />
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('heading', { level: 2 }) }"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                aria-label="Heading 2"
            >
                <Heading2 class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('heading', { level: 3 }) }"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                aria-label="Heading 3"
            >
                <Heading3 class="size-4" />
            </Button>
            <div class="mx-1 h-5 w-px bg-border" />
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('bulletList') }"
                @click="editor.chain().focus().toggleBulletList().run()"
                aria-label="Bullet List"
            >
                <List class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('orderedList') }"
                @click="editor.chain().focus().toggleOrderedList().run()"
                aria-label="Ordered List"
            >
                <ListOrdered class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('blockquote') }"
                @click="editor.chain().focus().toggleBlockquote().run()"
                aria-label="Blockquote"
            >
                <Quote class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('codeBlock') }"
                @click="editor.chain().focus().toggleCodeBlock().run()"
                aria-label="Code Block"
            >
                <Code class="size-4" />
            </Button>
            <div class="mx-1 h-5 w-px bg-border" />
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                :class="{ 'bg-muted': editor.isActive('link') }"
                @click="addLink"
                aria-label="Link"
            >
                <LinkIcon class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="size-8 p-0"
                @click="addImage"
                aria-label="Image"
            >
                <ImageIcon class="size-4" />
            </Button>
            <div class="ml-auto flex items-center gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-8 p-0"
                    :disabled="!editor.can().undo()"
                    @click="editor.chain().focus().undo().run()"
                    aria-label="Undo"
                >
                    <Undo2 class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="size-8 p-0"
                    :disabled="!editor.can().redo()"
                    @click="editor.chain().focus().redo().run()"
                    aria-label="Redo"
                >
                    <Redo2 class="size-4" />
                </Button>
            </div>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
