<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Card from '../components/Card.vue';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import PokerBoard from '../components/PokerBoard.vue';

type CardType = { rank: string; suit: string };

type SavedGame = {
    id: number;
    name?: string | null;
    players: string[];
    board: string;
    created_at?: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const props = defineProps<{
    name?: string;
    recentGames?: SavedGame[];
}>();

function normalizeRank(rank: string) {
    return rank === 'T' ? '10' : rank;
}

function parseCard(code?: string): CardType | null {
    if (!code || code.length < 2) return null;
    const rank = normalizeRank(code[0]);
    const suit = code[1];
    if (!['s', 'h', 'd', 'c'].includes(suit)) return null;
    return { rank, suit };
}

function parsePlayers(players: string[]) {
    return (players ?? []).map((hand) => {
        if (typeof hand !== 'string') return [null, null] as (CardType | null)[];
        const first = parseCard(hand.slice(0, 2));
        const second = parseCard(hand.slice(2, 4));
        return [first, second] as (CardType | null)[];
    });
}

function parseBoard(board: string) {
    const parsed: (CardType | null)[] = [];
    const str = board ?? '';
    for (let i = 0; i < str.length; i += 2) {
        const card = parseCard(str.slice(i, i + 2));
        if (card) parsed.push(card);
        if (parsed.length === 5) break;
    }
    while (parsed.length < 5) parsed.push(null);
    return parsed;
}

const parsedGames = computed(() => {
    const games = props.recentGames ?? [];
    return games.map((game) => ({
        ...game,
        playersParsed: parsePlayers(game.players ?? []),
        boardParsed: parseBoard(game.board ?? ''),
    }));
});

const emptySlots = computed(() => Math.max(3 - parsedGames.value.length, 0));

function formatDate(value?: string | null) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <template v-if="parsedGames.length">
                    <div
                        v-for="game in parsedGames"
                        :key="game.id"
                        class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 bg-neutral-900/30 dark:border-sidebar-border"
                    >
                        <PlaceholderPattern />
                        <div class="absolute inset-0 flex flex-col justify-between p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-neutral-400">Recent hand</div>
                                    <div class="text-lg font-semibold text-white">
                                        {{ game.name || 'Untitled hand' }}
                                    </div>
                                </div>
                                <div class="text-xs text-neutral-400">
                                    {{ formatDate(game.created_at) }}
                                </div>
                            </div>

                            <div class="flex flex-col gap-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div
                                        v-for="(cards, idx) in game.playersParsed.slice(0, 3)"
                                        :key="idx"
                                        class="flex items-center gap-2 rounded-lg bg-black/40 px-2 py-1 text-xs text-neutral-200"
                                    >
                                        <Card v-for="(c, ci) in cards" :key="ci" :card="c ?? undefined" size="sm" />
                                    </div>
                                    <div v-if="game.playersParsed.length > 3" class="text-xs text-neutral-200">
                                        +{{ game.playersParsed.length - 3 }} more
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="text-xs uppercase tracking-wide text-neutral-400">Board</div>
                                    <div class="flex gap-2">
                                        <Card v-for="(c, idx) in game.boardParsed" :key="idx" :card="c ?? undefined" size="sm" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-for="slot in emptySlots"
                        :key="`placeholder-${slot}`"
                        class="relative aspect-video overflow-hidden rounded-xl border border-dashed border-sidebar-border/50 dark:border-sidebar-border"
                    >
                        <PlaceholderPattern />
                        <div class="absolute inset-0 flex items-center justify-center text-sm text-neutral-400">
                            Save a hand to fill this spot
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="col-span-full rounded-xl border border-sidebar-border/70 p-6 text-center text-sm text-neutral-400 dark:border-sidebar-border">
                        Save a game to see it here.
                    </div>
                </template>
            </div>

            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border md:min-h-min">
                <PokerBoard />
            </div>
        </div>
    </AppLayout>
</template>
