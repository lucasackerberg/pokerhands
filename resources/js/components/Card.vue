<script setup lang="ts">
type Card = {
  rank: string;
  suit: string;
};

const props = defineProps<{ card?: Card; size?: 'sm' | 'md' | 'lg' }>();

const suitMap: Record<string, string> = {
  s: '♠',
  h: '♥',
  d: '♦',
  c: '♣',
};
</script>

<template>
  <div
    @click="$emit('click')"
    class="select-none flex items-center justify-center rounded-lg border bg-white p-2 text-sm shadow-sm dark:bg-[#0b0b0b] dark:text-white"
    :class="{
      'w-12 h-16': size === 'sm',
      'w-14 h-18': size === 'md',
      'w-20 h-28': size === 'lg',
    }"
  >
    <template v-if="card">
      <div class="flex flex-col items-center">
        <div class="font-semibold">{{ card.rank }}</div>
        <div :class="card.suit === 'h' || card.suit === 'd' ? 'text-red-600' : ''">{{ suitMap[card.suit] }}</div>
      </div>
    </template>
    <template v-else>
      <div class="text-xs text-gray-400">Empty</div>
    </template>
  </div>
</template>

<style scoped>
.w-18 { width: 4.5rem; }
.h-18 { height: 4.5rem; }
</style>
