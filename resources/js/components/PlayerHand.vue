<script setup lang="ts">
import Card from './Card.vue';

type CardType = { rank: string; suit: string };

const props = defineProps<{
  player: { id: string; name?: string; cards: (CardType | null)[] };
  onPick: (playerId: string, slot: number) => void;
}>();

function handlePick(slot: number) {
  // defensive: ensure prop exists
  if (props.onPick) props.onPick(props.player.id, slot);
}
</script>

<template>
  <div class="flex items-center gap-3 p-2">
    <div class="w-32">
      <div class="font-semibold">{{ props.player.name ?? 'Player' }}</div>
    </div>
    <div class="flex gap-2">
      <Card :card="props.player.cards[0] ?? undefined" @click="() => handlePick(0)" />
      <Card :card="props.player.cards[1] ?? undefined" @click="() => handlePick(1)" />
    </div>
  </div>
</template>
