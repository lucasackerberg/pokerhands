<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const cards = [
    { image: '/images/aceofspades.jpg', alt: 'Ace of Spades', rotation: -20, delay: 100 },
    { image: '/images/kingofspades.jpg', alt: 'King of Spades', rotation: -10, delay: 200 },
    { image: '/images/aceofspades.jpg', alt: 'Queen of Hearts', rotation: 0, delay: 300 },
    { image: '/images/kingofspades.jpg', alt: 'Jack of Diamonds', rotation: 10, delay: 400 },
    { image: '/images/aceofspades.jpg', alt: 'Ten of Clubs', rotation: 20, delay: 500 },
];
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a] lg:justify-center lg:p-8">
        <video
            src="/videos/backgroundvideo.mp4"
            autoplay
            muted
            loop
            class="blur-xs absolute inset-0 z-0 h-full w-full object-cover opacity-35"
        ></video>
        <div class="blur-xs flex h-full w-full bg-transparent"></div>
        <header class="not-has-[nav]:hidden z-50 mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Your letest hands
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>
        <div class="duration-750 starting:opacity-0 z-10 flex w-full items-start justify-center opacity-100 transition-opacity lg:grow">
            <main class="mt-32 flex w-full max-w-[335px] flex-col flex-wrap rounded-lg lg:max-w-4xl lg:flex-row gap-10">
                <h1 class="text-5xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">P 0 K E R H A N D S</h1>
                <p class="text-lg text-[#1b1b18] dark:text-[#EDEDEC]">
                    A simple poker hand evaluator that can evaluate a poker hand amongst others and determine its rank and percentage to win.
                </p>
                <p class="text-lg text-[#1b1b18] dark:text-[#EDEDEC]">
                    You also have the possibility to save your hands with an account if you wish!
                </p>
                <!-- <img src="/images/pokerhandsblabla.png" alt="Poker Hands"> -->
            </main>
            <div class="hidden lg:block absolute inset-0 pointer-events-none z-5">
                <div class="absolute top-[75%] left-1/2 -translate-x-1/2 -translate-y-1/2 transform perspective-1000 w-screen h-screen flex items-center justify-center pointer-events-none">
                    <div 
                        v-for="(card, index) in cards" 
                        :key="index"
                        :style="{ 
                            animationDelay: `${card.delay}ms`,
                            zIndex: index
                        }"
                        class="card-3d absolute bg-white p-3 rounded-lg shadow-2xl transition-all duration-500 ease-out hover:scale-110 hover:-translate-y-8 hover:z-50 cursor-pointer w-48 h-auto pointer-events-auto"
                        :class="`card-rotation-${index}`"
                    >
                        <img :src="card.image" :alt="card.alt" class="w-full h-auto select-none pointer-events-none" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Card Fan Container - Full Width -->
        <div class="h-14.5 hidden lg:block"></div>
    </div>
</template>

<style scoped>
.perspective-1000 {
    perspective: 1000px;
}

.card-3d {
    animation: dealCard 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    opacity: 0;
    transform-style: preserve-3d;
}

@keyframes dealCard {
    0% {
        opacity: 0;
        transform: translate(200px, -100px) rotate(45deg) scale(0.5);
    }
    100% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

.card-3d:hover {
    filter: brightness(1.1);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.card-3d:hover img {
    animation: cardFlipHover 0.6s ease-in-out;
}

@keyframes cardFlipHover {
    0%, 100% {
        transform: rotateY(0deg);
    }
    50% {
        transform: rotateY(10deg);
    }
}

/* Individual card rotations for fan effect */
.card-rotation-0 { 
    transform: translate(-50%, -50%) rotate(-20deg);
    left: calc(50% - 120px);
}
.card-rotation-1 { 
    transform: translate(-50%, -50%) rotate(-10deg);
    left: calc(50% - 60px);
}
.card-rotation-2 { 
    transform: translate(-50%, -50%) rotate(0deg);
    left: 50%;
}
.card-rotation-3 { 
    transform: translate(-50%, -50%) rotate(10deg);
    left: calc(50% + 60px);
}
.card-rotation-4 { 
    transform: translate(-50%, -50%) rotate(20deg);
    left: calc(50% + 120px);
}
</style>
