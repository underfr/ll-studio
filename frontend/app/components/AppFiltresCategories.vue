<script setup lang="ts">
import type { Categorie } from '~/types/api'

/**
 * Filtres par catégorie, partagés par la galerie et les albums : des boutons
 * daisyUI, pleins pour la catégorie active.
 *
 * Ce sont des liens et non des boutons : le filtre vit dans l'URL, donc
 * chaque état de la page est partageable, indexable et navigable avec les
 * boutons précédent et suivant du navigateur.
 */
const props = defineProps<{
    categories: Categorie[]
    /** Slug actif, chaîne vide quand aucun filtre n'est appliqué. */
    actif: string
    /** Page sur laquelle les liens rebouclent. */
    base: string
}>()

const route = useRoute()

/** Conserve les autres paramètres, notamment le mode d'affichage. */
function versCategorie(slug: string) {
    const query = { ...route.query }

    if (slug) {
        query.categorie = slug
    }
    else {
        delete query.categorie
    }

    return { path: props.base, query }
}
</script>

<template>
    <nav class="filtres flex flex-wrap gap-2.5" aria-label="Filtrer par catégorie">
        <NuxtLink
            class="btn btn-sm font-mono uppercase tracking-wider"
            :class="actif === '' ? 'btn-primary' : 'btn-outline'"
            :to="versCategorie('')"
            :aria-current="actif === '' ? 'true' : undefined"
        >
            Tout
        </NuxtLink>

        <NuxtLink
            v-for="categorie in categories"
            :key="categorie['@id']"
            class="btn btn-sm font-mono uppercase tracking-wider"
            :class="actif === categorie.slug ? 'btn-primary' : 'btn-outline'"
            :to="versCategorie(categorie.slug)"
            :aria-current="actif === categorie.slug ? 'true' : undefined"
        >
            {{ categorie.name }}
        </NuxtLink>
    </nav>
</template>
