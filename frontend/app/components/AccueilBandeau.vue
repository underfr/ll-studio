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
 */
defineProps<{ categories: Categorie[] }>()
</script>

<template>
    <div class="bandeau" aria-label="Univers photographiques">
        <div class="bandeau__piste">
            <p v-for="categorie in categories" :key="categorie['@id']" class="bandeau__mot">
                {{ categorie.name }}
            </p>
            <p
                v-for="categorie in categories"
                :key="`copie-${categorie['@id']}`"
                class="bandeau__mot"
                aria-hidden="true"
            >
                {{ categorie.name }}
            </p>
        </div>
    </div>
</template>

<style scoped>
.bandeau {
    overflow: hidden;
    border-top: 1px solid var(--bordure-default);
    border-bottom: 1px solid var(--bordure-default);
}

.bandeau__piste {
    display: flex;
    width: max-content;
    gap: 48px;
    padding: 20px var(--gouttiere);
    animation: defilement 38s linear infinite;
}

.bandeau__mot {
    color: var(--texte-tertiaire);
    font-family: var(--police-titre);
    font-size: clamp(22px, 2.1vw, 30px);
    font-style: italic;
    white-space: nowrap;
}

@keyframes defilement {
    from {
        transform: translateX(0);
    }

    to {
        /* La piste contient deux fois la liste : la moitié exacte reboucle
           sans saut. Le décalage de la gouttière est compensé par le gap. */
        transform: translateX(calc(-50% - 24px));
    }
}

@media (prefers-reduced-motion: reduce) {
    .bandeau__piste {
        animation: none;
    }
}
</style>
