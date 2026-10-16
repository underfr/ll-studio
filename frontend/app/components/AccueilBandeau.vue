<script setup lang="ts">
import type { Categorie } from '~/types/api'

/**
 * Bandeau défilant des univers photographiques, alimenté par les catégories
 * de l'API.
 *
 * La liste est rendue deux fois et l'animation translate d'exactement la
 * moitié de la largeur : la boucle est ainsi sans raccord visible. Le second
 * exemplaire est masqué aux lecteurs d'écran, qui liraient sinon deux fois
 * les mêmes libellés. Le défilement s'arrête si l'utilisateur a demandé à
 * réduire les animations.
 */
defineProps<{ categories: Categorie[] }>()
</script>

<template>
    <div class="bandeau overflow-hidden border-y border-base-300" aria-label="Univers photographiques">
        <div class="flex w-max animate-defilement gap-12 px-5.5 py-5 motion-reduce:animate-none bureau:px-10">
            <p
                v-for="categorie in categories"
                :key="categorie['@id']"
                class="whitespace-nowrap font-display text-[clamp(22px,2.1vw,30px)] italic text-base-content/50"
            >
                {{ categorie.name }}
            </p>
            <p
                v-for="categorie in categories"
                :key="`copie-${categorie['@id']}`"
                class="whitespace-nowrap font-display text-[clamp(22px,2.1vw,30px)] italic text-base-content/50"
                aria-hidden="true"
            >
                {{ categorie.name }}
            </p>
        </div>
    </div>
</template>
