<script setup lang="ts">
import type { Categorie } from '~/types/api'

/**
 * Chips de filtrage par catégorie, partagées par la galerie et les albums.
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
    <nav class="filtres" aria-label="Filtrer par catégorie">
        <NuxtLink
            class="chip"
            :class="{ 'chip--actif': actif === '' }"
            :to="versCategorie('')"
            :aria-current="actif === '' ? 'true' : undefined"
        >
            Tout
        </NuxtLink>

        <NuxtLink
            v-for="categorie in categories"
            :key="categorie['@id']"
            class="chip"
            :class="{ 'chip--actif': actif === categorie.slug }"
            :to="versCategorie(categorie.slug)"
            :aria-current="actif === categorie.slug ? 'true' : undefined"
        >
            {{ categorie.name }}
        </NuxtLink>
    </nav>
</template>

<style scoped>
.filtres {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.chip {
    padding: 8px 16px;
    border: 1px solid var(--bordure-chip);
    border-radius: 99px;
    color: var(--texte-secondaire);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    transition: border-color var(--transition-courte), color var(--transition-courte),
        background-color var(--transition-courte);
}

.chip:hover {
    border-color: var(--texte-principal);
    color: var(--texte-principal);
}

.chip--actif,
.chip--actif:hover {
    border-color: var(--or-remplissage);
    background-color: var(--or-remplissage);
    color: var(--texte-sur-or);
}
</style>
