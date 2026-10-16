<script setup lang="ts">
/**
 * Dernière section de la page, juste avant le pied de page.
 */
</script>

<template>
    <section class="appel hero border-t border-base-300 bg-base-200 px-5.5 py-12 bureau:px-10 bureau:py-24">
        <div class="hero-content w-full flex-col gap-6 p-0 text-center">
            <p class="font-display text-[clamp(40px,5.5vw,80px)] font-medium italic leading-none tracking-[-1px]">
                Créons quelque chose d'inoubliable.
            </p>

            <NuxtLink to="/contact" class="btn btn-primary w-full tablette:w-auto">Prendre contact</NuxtLink>
        </div>
    </section>
</template>
