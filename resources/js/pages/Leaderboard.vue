<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';

type LeaderboardEntry = {
    id: number;
    name: string;
    total_profit: string;
    total_buy_in: string;
    total_cash_out: string;
    sessions_count: number;
};

type UserSession = {
    id: number;
    buy_in: string;
    cash_out: string;
    profit: string;
    session_date: string;
    notes?: string;
    session_name?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leaderboard', href: '/leaderboard' },
];

const props = defineProps<{
    leaderboard: LeaderboardEntry[];
    userSessions: UserSession[];
}>();

const page = usePage<SharedData>();
const currency = computed<'USD' | 'SEK'>(() => (page.props.auth.user?.currency === 'SEK' ? 'SEK' : 'USD'));

async function submitSession() {
    if (isSubmitting.value) return;
    
    isSubmitting.value = true;
    try {
        const response = await fetch('/poker-sessions', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                buy_in: parseFloat(formData.value.buy_in),
                cash_out: parseFloat(formData.value.cash_out),
                session_date: formData.value.session_date,
                notes: formData.value.notes || null,
            }),
        });

        if (response.ok) {
            showAddModal.value = false;
            formData.value = {
                buy_in: '',
                cash_out: '',
                session_date: new Date().toISOString().split('T')[0],
                notes: '',
            };
            router.reload({ only: ['leaderboard', 'userSessions'] });
        } else {
            alert('Failed to save session');
        }
    } catch (err) {
        alert('Error saving session');
    } finally {
        isSubmitting.value = false;
    }
}


function formatCurrency(value: string | number): string {
    const numericValue = typeof value === 'string' ? parseFloat(value) : value;
    const locale = currency.value === 'SEK' ? 'sv-SE' : 'en-US';
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency.value,
    }).format(numericValue);
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
    <Head title="Leaderboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Poker Leaderboard</h1>
                <p class="text-sm text-neutral-400">Track poker session results</p>
            </div>

            <!-- Leaderboard Table -->
            <div class="rounded-xl border border-sidebar-border/70 bg-neutral-900/30 dark:border-sidebar-border">
                <div class="p-4">
                    <h2 class="mb-4 text-xl font-semibold">Overall Standings</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-sidebar-border/50 text-left text-sm text-neutral-400">
                                    <th class="pb-3 pr-4">Rank</th>
                                    <th class="pb-3 pr-4">Player</th>
                                    <th class="pb-3 pr-4 text-right">Sessions</th>
                                    <th class="pb-3 pr-4 text-right">Total Buy-In</th>
                                    <th class="pb-3 pr-4 text-right">Total Cash-Out</th>
                                    <th class="pb-3 text-right font-semibold">Total Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(entry, idx) in leaderboard"
                                    :key="entry.id"
                                    class="border-b border-sidebar-border/30 transition-colors hover:bg-neutral-800/30"
                                >
                                    <td class="py-3 pr-4">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-800 font-bold">
                                            {{ idx + 1 }}
                                        </div>
                                    </td>
                                    <td class="py-3 pr-4 font-medium">{{ entry.name }}</td>
                                    <td class="py-3 pr-4 text-right text-neutral-400">{{ entry.sessions_count }}</td>
                                    <td class="py-3 pr-4 text-right text-neutral-400">{{ formatCurrency(entry.total_buy_in) }}</td>
                                    <td class="py-3 pr-4 text-right text-neutral-400">{{ formatCurrency(entry.total_cash_out) }}</td>
                                    <td
                                        class="py-3 text-right font-semibold"
                                        :class="parseFloat(entry.total_profit) >= 0 ? 'text-green-500' : 'text-red-500'"
                                    >
                                        {{ formatCurrency(entry.total_profit) }}
                                    </td>
                                </tr>
                                <tr v-if="!leaderboard.length">
                                    <td colspan="6" class="py-8 text-center text-neutral-400">
                                        No sessions recorded yet. Add your first session above!
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Your Recent Sessions -->
            <div class="rounded-xl border border-sidebar-border/70 bg-neutral-900/30 dark:border-sidebar-border">
                <div class="p-4">
                    <h2 class="mb-4 text-xl font-semibold">Your Recent Sessions</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-sidebar-border/50 text-left text-sm text-neutral-400">
                                    <th class="pb-3 pr-4">Date</th>
                                    <th class="pb-3 pr-4">Session</th>
                                    <th class="pb-3 pr-4 text-right">Buy-In</th>
                                    <th class="pb-3 pr-4 text-right">Cash-Out</th>
                                    <th class="pb-3 pr-4 text-right">Profit/Loss</th>
                                    <th class="pb-3">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="session in userSessions" :key="session.id" class="border-b border-sidebar-border/30">
                                    <td class="py-3 pr-4 text-sm">{{ formatDate(session.session_date) }}</td>
                                    <td class="py-3 pr-4 text-sm font-medium">{{ session.session_name || 'Session' }}</td>
                                    <td class="py-3 pr-4 text-right text-neutral-400">{{ formatCurrency(session.buy_in) }}</td>
                                    <td class="py-3 pr-4 text-right text-neutral-400">{{ formatCurrency(session.cash_out) }}</td>
                                    <td
                                        class="py-3 pr-4 text-right font-medium"
                                        :class="parseFloat(session.profit) >= 0 ? 'text-green-500' : 'text-red-500'"
                                    >
                                        {{ formatCurrency(session.profit) }}
                                    </td>
                                    <td class="py-3 text-sm text-neutral-400">{{ session.notes || '—' }}</td>
                                </tr>
                                <tr v-if="!userSessions.length">
                                    <td colspan="6" class="py-8 text-center text-neutral-400">
                                        No sessions yet. Join a poker night to see results here!
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
