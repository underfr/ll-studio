<script setup lang="ts">
import type { Categorie } from '~/types/api'

/**
 * Bandeau défilant des univers photographiques, alimenté par les catégories
 * de l'API.
 *
 * La liste est rendue deux fois et l'animation translate d'exactement la
 * moitié de la largeur : la boucle est ainsi sans raccord visible. Le second
 * exemplaire est masqué aux lecteurs d'écran, qui liraient sinon deux fois
 * les mêmes libellés.
 *
 * Un contenu qui bouge seul plus de cinq secondes doit pouvoir être arrêté
 * (critère WCAG 2.2.2, issue #33) : le bouton de fin de bandeau met le
 * défilement en pause et le relance. Si l'utilisateur a demandé à réduire
 * les animations, rien ne défile et le bouton disparaît.
 */
defineProps<{ categories: Categorie[] }>()

const enPause = ref(false)
</script>

<template>
    <div class="bandeau relative overflow-hidden border-y border-base-300" role="group" aria-label="Univers photographiques">
        <div
            class="flex w-max animate-defilement gap-12 px-5.5 py-5 motion-reduce:animate-none bureau:px-10"
            :class="{ '[animation-play-state:paused]': enPause }"
        >
            <p
                v-for="categorie in categories"
                :key="categorie['@id']"
                class="whitespace-nowrap font-display text-[clamp(22px,2.1vw,30px)] italic text-base-content/70"
            >
                {{ categorie.name }}
            </p>
            <p
                v-for="categorie in categories"
                :key="`copie-${categorie['@id']}`"
                class="whitespace-nowrap font-display text-[clamp(22px,2.1vw,30px)] italic text-base-content/70"
                aria-hidden="true"
            >
                {{ categorie.name }}
            </p>
        </div>

        <button
            type="button"
            class="bandeau__pause btn btn-circle btn-sm absolute top-1/2 right-3 -translate-y-1/2 motion-reduce:hidden bureau:right-5"
            :aria-label="enPause ? 'Reprendre le défilement des univers' : 'Mettre en pause le défilement des univers'"
            @click="enPause = !enPause"
        >
            <svg v-if="enPause" class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M8 5.5v13l10.5-6.5z" />
            </svg>
            <svg v-else class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" />
            </svg>
        </button>
    </div>
</template>
