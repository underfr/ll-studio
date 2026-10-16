<script setup lang="ts">
/**
 * En-tête commun aux pages du back-office : rubrique, titre, description et
 * emplacement pour les actions de la page (bouton d'ajout, filtres...), que
 * les écrans des itérations 8 et 9 rempliront.
 */
defineProps<{
    rubrique: string
    titre: string
}>()
</script>

<template>
    <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-[0.3em] text-secondary">
                {{ rubrique }}
            </p>
            <h1 class="mt-2 font-display text-4xl lg:text-5xl">{{ titre }}</h1>
            <p v-if="$slots.default" class="mt-3 text-base-content/70">
                <slot />
            </p>
        </div>

        <div v-if="$slots.actions" class="flex flex-wrap gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>
