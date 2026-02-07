<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import Card from '../components/Card.vue';

type User = { id: number; name: string };
type Entry = { id: number; user_id: number; user: User; buy_in: string; cash_out: string; notes?: string };
type SessionGroup = {
    id: number;
    name: string;
    code: string;
    session_date: string;
    organizer_id: number;
    organizer: User;
    entries: Entry[];
    created_at: string;
};

const props = defineProps<{
    session: SessionGroup;
    userEntry: Entry | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Poker Nights', href: '/sessions' },
    { title: props.session.name, href: '#' },
];

const showEntryForm = ref(false);
const formData = ref({
    buy_in: props.userEntry?.buy_in ?? '',
    cash_out: props.userEntry?.cash_out ?? '',
    notes: props.userEntry?.notes ?? '',
});
const isSubmitting = ref(false);
const joinCode = ref('');
const showJoinForm = ref(!props.userEntry);

const currentUserEntry = computed(() => props.userEntry);

const totalResults = computed(() => {
    return props.session.entries.map((entry) => ({
        ...entry,
        profit: parseFloat(entry.cash_out) - parseFloat(entry.buy_in),
    }));
});

async function submitEntry() {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    try {
        const response = await fetch(`/sessions/${props.session.code}/entry`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                buy_in: parseFloat(formData.value.buy_in),
                cash_out: parseFloat(formData.value.cash_out),
                notes: formData.value.notes || null,
            }),
        });

        if (response.ok) {
            showEntryForm.value = false;
            router.reload({ only: ['session', 'userEntry'] });
        } else {
            alert('Failed to save entry');
        }
    } catch (err) {
        alert('Error saving entry');
    } finally {
        isSubmitting.value = false;
    }
}

function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(typeof value === 'string' ? parseFloat(value) : value);
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head :title="session.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">{{ session.name }}</h1>
                        <p class="text-sm text-neutral-400">{{ formatDate(session.session_date) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <code class="rounded-lg bg-black/30 px-3 py-2 font-mono text-sm text-neutral-300">{{ session.code }}</code>
                        <button
                            @click="() => navigator.clipboard.writeText(session.code)"
                            class="rounded-lg bg-neutral-700 px-3 py-2 text-sm hover:bg-neutral-600"
                        >
                            Copy Code
                        </button>
                    </div>
                </div>
            </div>

            <!-- User Entry Section -->
            <div class="rounded-xl border border-sidebar-border/70 bg-neutral-900/30 p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Your Results</h2>
                    <button
                        @click="showEntryForm = !showEntryForm"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        {{ currentUserEntry ? 'Edit Results' : 'Add Results' }}
                    </button>
                </div>

                <div v-if="currentUserEntry && !showEntryForm" class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-lg bg-black/30 p-4">
                        <p class="mb-1 text-xs uppercase text-neutral-400">Buy-In</p>
                        <p class="text-2xl font-bold">{{ formatCurrency(currentUserEntry.buy_in) }}</p>
                    </div>
                    <div class="rounded-lg bg-black/30 p-4">
                        <p class="mb-1 text-xs uppercase text-neutral-400">Cash-Out</p>
                        <p class="text-2xl font-bold">{{ formatCurrency(currentUserEntry.cash_out) }}</p>
                    </div>
                    <div
                        class="rounded-lg bg-black/30 p-4"
                        :class="parseFloat(currentUserEntry.cash_out) - parseFloat(currentUserEntry.buy_in) >= 0 ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500'"
                    >
                        <p class="mb-1 text-xs uppercase text-neutral-400">Profit/Loss</p>
                        <p
                            class="text-2xl font-bold"
                            :class="parseFloat(currentUserEntry.cash_out) - parseFloat(currentUserEntry.buy_in) >= 0 ? 'text-green-500' : 'text-red-500'"
                        >
                            {{ formatCurrency(parseFloat(currentUserEntry.cash_out) - parseFloat(currentUserEntry.buy_in)) }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-black/30 p-4">
                        <p class="mb-1 text-xs uppercase text-neutral-400">Notes</p>
                        <p class="line-clamp-2 text-sm">{{ currentUserEntry.notes || '—' }}</p>
                    </div>
                </div>

                <!-- Entry Form -->
                <form v-if="showEntryForm" @submit.prevent="submitEntry" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Buy-In ($)</label>
                            <input
                                v-model="formData.buy_in"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full rounded-lg border border-sidebar-border bg-neutral-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                                placeholder="100.00"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Cash-Out ($)</label>
                            <input
                                v-model="formData.cash_out"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full rounded-lg border border-sidebar-border bg-neutral-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                                placeholder="150.00"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Notes (optional)</label>
                        <textarea
                            v-model="formData.notes"
                            rows="3"
                            class="w-full rounded-lg border border-sidebar-border bg-neutral-800 px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
                            placeholder="Any notes about this session..."
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showEntryForm = false"
                            class="flex-1 rounded-lg border border-sidebar-border px-4 py-2 text-sm font-medium hover:bg-neutral-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Saving...' : 'Save Results' }}
                        </button>
                    </div>
                </form>

                <div v-if="!currentUserEntry && !showEntryForm" class="rounded-lg bg-black/30 p-4 text-center text-sm text-neutral-400">
                    You haven't added your results yet. Click "Add Results" to get started.
                </div>
            </div>

            <!-- All Players Results -->
            <div class="rounded-xl border border-sidebar-border/70 bg-neutral-900/30 p-6">
                <h2 class="mb-4 text-xl font-semibold">All Players</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-sidebar-border/50 text-left text-sm text-neutral-400">
                                <th class="pb-3 pr-4">Player</th>
                                <th class="pb-3 pr-4 text-right">Buy-In</th>
                                <th class="pb-3 pr-4 text-right">Cash-Out</th>
                                <th class="pb-3 text-right font-semibold">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="result in totalResults"
                                :key="result.id"
                                class="border-b border-sidebar-border/30 transition-colors hover:bg-neutral-800/30"
                            >
                                <td class="py-3 pr-4 font-medium">{{ result.user.name }}</td>
                                <td class="py-3 pr-4 text-right text-neutral-400">{{ formatCurrency(result.buy_in) }}</td>
                                <td class="py-3 pr-4 text-right text-neutral-400">{{ formatCurrency(result.cash_out) }}</td>
                                <td
                                    class="py-3 text-right font-semibold"
                                    :class="result.profit >= 0 ? 'text-green-500' : 'text-red-500'"
                                >
                                    {{ formatCurrency(result.profit) }}
                                </td>
                            </tr>
                            <tr v-if="!session.entries.length">
                                <td colspan="4" class="py-8 text-center text-neutral-400">
                                    No players have added their results yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
