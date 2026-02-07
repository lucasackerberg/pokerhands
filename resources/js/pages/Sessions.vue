<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

type SessionGroup = {
    id: number;
    name: string;
    code: string;
    session_date: string;
    organizer_id: number;
    organizer: { id: number; name: string };
    entries: Array<{ id: number; user_id: number; user: { name: string }; buy_in: string; cash_out: string }>;
    created_at: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Poker Nights', href: '/sessions' },
];

const props = defineProps<{
    sessions: SessionGroup[];
    canOrganize: boolean;
}>();

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id ?? 0);

const showCreateModal = ref(false);
const formData = ref({
    name: '',
    session_date: new Date().toISOString().split('T')[0],
    description: '',
});
const isSubmitting = ref(false);

async function createSession() {
    if (isSubmitting.value || !formData.value.name.trim()) return;

    isSubmitting.value = true;
    try {
        const response = await fetch('/sessions', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                name: formData.value.name,
                session_date: formData.value.session_date,
                description: formData.value.description || null,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            showCreateModal.value = false;
            formData.value = {
                name: '',
                session_date: new Date().toISOString().split('T')[0],
                description: '',
            };
            router.reload({ only: ['sessions'] });
        } else {
            alert('Failed to create session');
        }
    } catch (err) {
        alert('Error creating session');
    } finally {
        isSubmitting.value = false;
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function isOrganizer(session: SessionGroup): boolean {
    return session.organizer_id === currentUserId.value;
}
</script>

<template>
    <Head title="Poker Nights" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Poker Nights</h1>
                    <p class="text-sm text-neutral-400">{{ canOrganize ? 'Create or join a poker session' : 'Join poker sessions' }}</p>
                </div>
                <button
                    v-if="canOrganize"
                    @click="showCreateModal = true"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    + Create Night
                </button>
            </div>

            <!-- Sessions Grid -->
            <div v-if="sessions.length" class="grid gap-4 md:grid-cols-2">
                <div
                    v-for="session in sessions"
                    :key="session.id"
                    class="rounded-xl border border-sidebar-border/70 bg-neutral-900/30 p-4 hover:bg-neutral-800/30"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">{{ session.name }}</h3>
                            <p class="text-xs text-neutral-400">{{ formatDate(session.session_date) }}</p>
                        </div>
                        <div v-if="isOrganizer(session)" class="rounded bg-blue-600/20 px-2 py-1 text-xs font-medium text-blue-400">
                            Organizer
                        </div>
                    </div>

                    <div class="mb-4 flex items-center gap-2 rounded-lg bg-black/30 p-2">
                        <code class="flex-1 text-sm font-mono text-neutral-300">{{ session.code }}</code>
                        <button
                            @click="() => navigator.clipboard.writeText(session.code)"
                            class="rounded px-2 py-1 text-xs text-neutral-400 hover:bg-neutral-700"
                        >
                            Copy
                        </button>
                    </div>

                    <div class="mb-4">
                        <p class="mb-2 text-xs uppercase text-neutral-400">Players ({{ session.entries.length }})</p>
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="entry in session.entries"
                                :key="entry.id"
                                class="rounded-full bg-neutral-700 px-3 py-1 text-xs"
                            >
                                {{ entry.user.name }}
                            </div>
                        </div>
                    </div>

                    <a
                        :href="`/sessions/${session.code}`"
                        class="inline-block rounded-lg bg-neutral-700 px-4 py-2 text-sm font-medium hover:bg-neutral-600"
                    >
                        View Details
                    </a>
                </div>
            </div>

            <div v-else class="rounded-xl border border-dashed border-sidebar-border/50 p-8 text-center text-neutral-400">
                <p class="mb-2">No poker nights yet.</p>
                <p class="text-sm">Create one to get started!</p>
            </div>
        </div>

        <!-- Create Session Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div class="w-full max-w-md rounded-xl border border-sidebar-border bg-neutral-900 p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-semibold">Create Poker Night</h3>
                    <button @click="showCreateModal = false" class="text-neutral-400 hover:text-white">✕</button>
                </div>

                <form @submit.prevent="createSession" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Night Name</label>
                        <input
                            v-model="formData.name"
                            type="text"
                            required
                            class="w-full rounded-lg border border-sidebar-border bg-neutral-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                            placeholder="e.g., Weekend Poker Night"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Date</label>
                        <input
                            v-model="formData.session_date"
                            type="date"
                            required
                            class="w-full rounded-lg border border-sidebar-border bg-neutral-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Description (optional)</label>
                        <textarea
                            v-model="formData.description"
                            rows="3"
                            class="w-full rounded-lg border border-sidebar-border bg-neutral-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                            placeholder="Add any details..."
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="flex-1 rounded-lg border border-sidebar-border px-4 py-2 text-sm font-medium hover:bg-neutral-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Creating...' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
