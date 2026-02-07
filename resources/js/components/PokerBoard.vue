<script setup lang="ts">
import { reactive, ref, onBeforeUnmount } from 'vue';
import { CardGroup, OddsCalculator } from 'poker-odds-calculator';
import PlayerHand from './PlayerHand.vue';
import Card from './Card.vue';

type CardType = { rank: string; suit: string };

type Player = { id: string; name?: string; cards: (CardType | null)[] };
type Board = { cards: (CardType | null)[] };

function ranks() {
  return ['A', 'K', 'Q', 'J', '10', '9', '8', '7', '6', '5', '4', '3', '2'];
}
function suits() {
  return ['s', 'h', 'd', 'c'];
}

function makeDeck() {
  const d: CardType[] = [];
  for (const r of ranks()) {
    for (const s of suits()) {
      d.push({ rank: r, suit: s });
    }
  }
  return d;
}

const initialState: { players: Player[]; board: Board } = {
  players: [
    { id: 'p1', name: 'You', cards: [null, null] },
    { id: 'p2', name: 'Opp 1', cards: [null, null] },
  ],
  board: { cards: [null, null, null, null, null] },
};

const state = reactive(initialState);

const showPicker = ref(false);
const pickTarget = ref<{ type: 'player' | 'board'; id?: string; slot: number } | null>(null);

function openPicker(type: 'player' | 'board', id: string | undefined, slot: number) {
  pickTarget.value = { type, id, slot };
  showPicker.value = true;
  console.debug('openPicker', pickTarget.value);
}

function cardToKey(c: CardType) {
  return `${c.rank}${c.suit}`;
}

function takenCards() {
  const taken = new Set<string>();
  for (const p of state.players) {
    for (const c of p.cards) if (c) taken.add(cardToKey(c));
  }
  for (const c of state.board.cards) if (c) taken.add(cardToKey(c));
  return taken;
}

function pickCard(card: CardType) {
  if (!pickTarget.value) return;
  const t = pickTarget.value;
  if (t.type === 'player') {
    const p = state.players.find((x) => x.id === t.id);
    if (p) p.cards[t.slot] = card;
  } else {
    state.board.cards[t.slot] = card;
  }
  showPicker.value = false;
  pickTarget.value = null;
  requestOddsDebounced();
  console.debug('pickCard', card);
}

function clearSlot(type: 'player' | 'board', id: string | undefined, slot: number) {
  if (type === 'player') {
    const p = state.players.find((x) => x.id === id);
    if (p) p.cards[slot] = null;
  } else {
    state.board.cards[slot] = null;
  }
  requestOddsDebounced();
}

const deck = makeDeck();

const equities = ref<number[] | null>(null);
const isCalculating = ref(false);
const lastDuration = ref<string | null>(null);

function cardToString(c: CardType | null) {
  if (!c) return '';
  // convert rank like '10' -> 'T' for compatibility (CardGroup expects T for ten)
  const r = c.rank === '10' ? 'T' : c.rank[0];
  return `${r}${c.suit}`;
}

function handToString(cards: (CardType | null)[]) {
  if (!cards[0] || !cards[1]) return '';
  return `${cardToString(cards[0])}${cardToString(cards[1])}`;
}

function boardToString(cards: (CardType | null)[]) {
  const set = cards.filter(Boolean) as CardType[];
  return set.map((c) => cardToString(c)).join('');
}

let lastRequestId = 0;
let pendingTimeout: any = null;
const pendingResponses = new Map<string, (value: any) => void>();
const pendingTimeouts = new Map<string, number>();

function requestOddsDebounced() {
  if (pendingTimeout) clearTimeout(pendingTimeout);
  pendingTimeout = setTimeout(() => {
    void requestOdds();
  }, 200);
}

async function requestOdds() {
  const playerStrings = state.players.map((p) => handToString(p.cards));
  // only request if all players have full hands for now
  if (playerStrings.some((s) => s === '')) {
    equities.value = null;
    return;
  }
  const boardStr = boardToString(state.board.cards);
  isCalculating.value = true;
  const start = performance.now();
  try {
    const playerGroups = playerStrings.map((p) => CardGroup.fromString(p));
    const boardGroup = boardStr ? CardGroup.fromString(boardStr) : undefined;
    const result = OddsCalculator.calculate(playerGroups, boardGroup);
    equities.value = result.equities.map((e: any) => e.getEquity());
  } catch (err: any) {
    console.error('Odds calc error:', err);
    lastDuration.value = `Calc error: ${err?.message ?? 'Unknown error'}`;
  } finally {
    isCalculating.value = false;
    const dur = performance.now() - start;
    lastDuration.value = `${dur.toFixed(0)} ms`;
  }
}

async function runBenchmark(runs = 10) {
  const playerStrings = state.players.map((p) => handToString(p.cards));
  if (playerStrings.some((s) => s === '')) {
    lastDuration.value = 'Provide full hands for all players first';
    return;
  }
  const boardStr = boardToString(state.board.cards);
  const times: number[] = [];
  isCalculating.value = true;
  for (let i = 0; i < runs; i++) {
    const start = performance.now();
    try {
      const playerGroups = playerStrings.map((p) => CardGroup.fromString(p));
      const boardGroup = boardStr ? CardGroup.fromString(boardStr) : undefined;
      OddsCalculator.calculate(playerGroups, boardGroup);
    } catch (err) {
      // ignore for benchmark
    }
    const dur = performance.now() - start;
    times.push(dur);
    // small delay to keep UI responsive
    await new Promise((r) => setTimeout(r, 50));
  }
  isCalculating.value = false;
  const avg = times.reduce((a, b) => a + b, 0) / times.length;
  const min = Math.min(...times);
  const max = Math.max(...times);
  lastDuration.value = `bench ${runs} runs — avg ${avg.toFixed(0)} ms, min ${min.toFixed(0)} ms`;
}

function addOpponent() {
  if (state.players.length >= 9) {
    alert('Maximum 9 players allowed');
    return;
  }
  const nextId = `p${state.players.length + 1}`;
  const nextNum = state.players.length;
  state.players.push({
    id: nextId,
    name: `Opp ${nextNum}`,
    cards: [null, null],
  });
}

function removeOpponent(id: string) {
  if (state.players.length <= 2) {
    alert('You need at least 2 players (You and one opponent)');
    return;
  }
  if (id === 'p1') {
    alert('Cannot remove yourself');
    return;
  }
  state.players = state.players.filter((p) => p.id !== id);
  requestOddsDebounced();
}

async function saveGame() {
  const name = prompt('Enter a name for this game (optional):');
  const playerStrings = state.players.map((p) => handToString(p.cards));
  const boardStr = boardToString(state.board.cards);
  try {
    const response = await fetch('/games', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ name, players: playerStrings, board: boardStr }),
    });
    if (response.ok) {
      alert('Game saved!');
    } else {
      alert('Failed to save game');
    }
  } catch (err) {
    alert('Error saving game');
  }
}

onBeforeUnmount(() => {
});

</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="grid gap-4 md:grid-cols-2">
      <div class="rounded-xl border p-4">
        <div class="mb-2 flex items-center justify-between">
          <h3 class="font-semibold">Players</h3>
          <button
            @click="addOpponent"
            :disabled="state.players.length >= 9"
            class="rounded px-2 py-1 text-xs bg-blue-600 text-white hover:bg-blue-700 disabled:bg-gray-500 disabled:cursor-not-allowed"
          >
            + Add Opponent
          </button>
        </div>
        <div class="flex flex-col gap-2">
          <div v-for="(p, idx) in state.players" :key="p.id" class="flex items-center justify-between gap-2">
            <PlayerHand :player="p" :onPick="(id, slot) => openPicker('player', id, slot)" />
            <div class="w-28 text-right">
              <template v-if="equities && equities[idx] !== undefined">
                <div class="text-sm font-medium">{{ equities[idx].toFixed(2) }}%</div>
              </template>
              <template v-else>
                <div class="text-sm text-gray-400">—</div>
              </template>
            </div>
            <button
              v-if="p.id !== 'p1'"
              @click="removeOpponent(p.id)"
              class="rounded px-2 py-1 text-xs bg-red-600 text-white hover:bg-red-700"
            >
              Remove
            </button>
          </div>
        </div>
      </div>
      <div class="rounded-xl border p-4">
        <h3 class="mb-2 font-semibold">Board</h3>
        <div class="flex flex-wrap gap-4">
          <div v-for="(c, idx) in state.board.cards" :key="idx">
            <Card :card="c ?? undefined" size="md" @click="() => openPicker('board', undefined, idx)" />
          </div>
        </div>
      </div>
    </div>

  <!-- Picker modal -->
    <div v-if="showPicker" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div class="max-h-[70vh] w-[720px] overflow-auto rounded bg-white p-4 dark:bg-[#0b0b0b]">
        <div class="flex items-center justify-between">
          <h4 class="font-semibold">Pick a card</h4>
          <button class="text-sm text-gray-600" @click="showPicker = false">Close</button>
        </div>
        <div class="mt-4 grid grid-cols-8 gap-2">
          <div v-for="card in deck" :key="cardToKey(card)" class="">
            <button
              class="w-full"
              :disabled="takenCards().has(cardToKey(card))"
              @click="pickCard(card)"
            >
              <Card :card="card" />
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-2 flex items-center justify-between">
      <div class="text-sm text-gray-500">
        <template v-if="isCalculating">Calculating…</template>
        <template v-else-if="equities">Calculated equities shown above</template>
        <template v-else>Enter full hands for all players to calculate equities</template>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-xs text-gray-500">{{ lastDuration ?? '' }}</div>
        <button class="rounded px-3 py-1 text-white text-sm" @click="saveGame">Save Game</button>
        <button class="px-3 py-1 text-white text-sm" @click="() => runBenchmark(8)" :disabled="isCalculating">Run benchmark</button>
      </div>
    </div>
  </div>
</template>
