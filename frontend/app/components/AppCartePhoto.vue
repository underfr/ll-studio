<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Tuile d'une photographie dans la galerie.
 *
 * Deux variantes, reprises des deux maquettes :
 * - « mosaique » laisse respirer l'image et ne révèle la légende qu'au survol
 * - « grille » impose un carré et affiche la légende en permanence
 *
 * La tuile n'est pas encore cliquable : l'ouverture en lightbox arrive à
 * l'issue #22. Elle reste donc un simple <figure>, plutôt qu'un bouton qui
 * ne ferait rien.
 */
withDefaults(defineProps<{
    photo: Photo
    variante?: 'mosaique' | 'grille'
    prioritaire?: boolean
}>(), {
    variante: 'mosaique',
    prioritaire: false,
})
</script>

<template>
    <figure class="tuile" :class="`tuile--${variante}`">
        <img
            class="tuile__photo"
            :src="urlMedia(photo.contentUrl)"
            :alt="photo.alt"
            :loading="prioritaire ? 'eager' : 'lazy'"
            decoding="async"
        >

        <span class="tuile__voile" />

        <figcaption class="tuile__legende">
            <span v-if="photo.category" class="tuile__categorie">{{ photo.category.name }}</span>
            <span class="tuile__titre">{{ photo.title }}</span>
        </figcaption>
    </figure>
</template>

<style scoped>
.tuile {
    position: relative;
    display: block;
    margin: 0;
    overflow: hidden;
    background-color: var(--fond-surface-teintee);
}

/*
 * En mosaïque, l'image reste dans le flux : c'est elle qui donne sa hauteur
 * à la tuile, donc chaque photographie garde ses proportions d'origine.
 * En grille, la tuile impose un carré et l'image le remplit en recadrant.
 */
.tuile__photo {
    display: block;
    width: 100%;
    height: auto;
    transition: transform 600ms cubic-bezier(0.32, 0.72, 0, 1);
}

.tuile--grille {
    aspect-ratio: 1;
}

.tuile--grille .tuile__photo {
    position: absolute;
    height: 100%;
    inset: 0;
    object-fit: cover;
}

.tuile:hover .tuile__photo {
    transform: scale(1.03);
}

.tuile__voile {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgb(18 16 12 / 0%) 55%,
        rgb(18 16 12 / 85%) 100%
    );
}

/* La légende est posée sur l'image, jamais en dessous. */
.tuile__legende {
    position: absolute;
    right: 14px;
    bottom: 14px;
    left: 14px;
    display: flex;
    flex-direction: column;
}

.tuile--grille .tuile__legende {
    right: 18px;
    bottom: 18px;
    left: 18px;
}

.tuile__categorie {
    color: var(--or-remplissage);
    font-family: var(--police-ui);
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.tuile__titre {
    color: var(--texte-sur-sombre);
    font-family: var(--police-titre);
    font-size: 20px;
    font-weight: 500;
    line-height: 1.1;
}

.tuile--grille .tuile__titre {
    font-size: 24px;
}

/*
 * En mosaïque, la maquette ne révèle voile et légende qu'au survol.
 * Le masquage est réservé aux appareils qui savent survoler : sur écran
 * tactile la légende resterait autrement inaccessible.
 */
@media (hover: hover) {
    .tuile--mosaique .tuile__voile,
    .tuile--mosaique .tuile__legende {
        opacity: 0;
        transition: opacity var(--transition-courte);
    }

    .tuile--mosaique:hover .tuile__voile,
    .tuile--mosaique:hover .tuile__legende,
    .tuile--mosaique:focus-within .tuile__voile,
    .tuile--mosaique:focus-within .tuile__legende {
        opacity: 1;
    }
}
</style>
