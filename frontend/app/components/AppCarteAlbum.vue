<script setup lang="ts">
import type { Album } from '~/types/api'

/**
 * Carte d'une série : la photo de couverture en fond, un voile dégradé, et
 * la légende posée en bas. Le composant sert la page d'accueil et servira
 * la grille de la galerie (issue #20), d'où sa mise en facteur.
 */
defineProps<{
    album: Album
    /** La première carte d'une page est chargée sans attendre le défilement. */
    prioritaire?: boolean
}>()
</script>

<template>
    <NuxtLink :to="`/albums/${album.slug}`" class="carte">
        <img
            v-if="album.coverPhoto"
            class="carte__photo"
            :src="urlMedia(album.coverPhoto.contentUrl)"
            :alt="album.coverPhoto.alt"
            :loading="prioritaire ? 'eager' : 'lazy'"
            decoding="async"
        >
        <span class="carte__voile" />

        <span class="carte__legende">
            <span class="carte__categorie">
                {{ album.category.name }} · {{ album.photoCount }}
            </span>
            <span class="carte__titre">{{ album.title }}</span>
        </span>
    </NuxtLink>
</template>

<style scoped>
.carte {
    position: relative;
    display: flex;
    /* La maquette mobile pose des cartes en 3/2, la desktop en 4/5. */
    aspect-ratio: 3 / 2;
    flex-direction: column;
    justify-content: flex-end;
    padding: 16px;
    overflow: hidden;
    background-color: var(--fond-surface-teintee);
}

@media (min-width: 900px) {
    .carte {
        aspect-ratio: 4 / 5;
        padding: 22px;
    }
}

.carte__photo {
    position: absolute;
    width: 100%;
    height: 100%;
    inset: 0;
    object-fit: cover;
    transition: transform 600ms cubic-bezier(0.32, 0.72, 0, 1);
}

.carte:hover .carte__photo,
.carte:focus-visible .carte__photo {
    transform: scale(1.04);
}

.carte__voile {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgb(18 16 12 / 0%) 45%,
        rgb(18 16 12 / 90%) 100%
    );
}

.carte__legende {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.carte__categorie {
    color: var(--or-remplissage);
    font-family: var(--police-ui);
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.carte__titre {
    color: var(--texte-sur-sombre);
    font-family: var(--police-titre);
    font-size: 28px;
    font-weight: 500;
    line-height: 1.05;
}

@media (min-width: 900px) {
    .carte__categorie {
        font-size: 10px;
    }

    .carte__titre {
        font-size: 30px;
    }
}
</style>
