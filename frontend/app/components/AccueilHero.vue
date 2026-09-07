<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Bandeau d'ouverture. La photographie de fond est la plus récente publiée :
 * l'accueil se renouvelle donc à chaque ajout, sans intervention.
 */
defineProps<{ photo?: Photo }>()
</script>

<template>
    <section class="hero">
        <img
            v-if="photo"
            class="hero__photo"
            :src="urlMedia(photo.contentUrl)"
            :alt="photo.alt"
            fetchpriority="high"
            decoding="async"
        >
        <div class="hero__voile" />

        <div class="hero__copy">
            <p class="hero__kicker">Photographe · Multimédia</p>

            <h1 class="hero__titre">La lumière raconte ce que les mots taisent.</h1>

            <p class="hero__accroche">
                Nature, spectacle, espace, automobile, événements : je capture l'instant
                où la scène bascule dans le sublime.
            </p>

            <div class="hero__actions">
                <NuxtLink to="/galerie" class="bouton bouton--or">Voir la galerie</NuxtLink>
                <NuxtLink to="/contact" class="bouton bouton--contour">Me réserver</NuxtLink>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero {
    position: relative;
    display: flex;
    min-height: 660px;
    flex-direction: column;
    justify-content: flex-end;
    padding: 0 var(--gouttiere) 40px;
    overflow: hidden;
    background-color: var(--texte-principal);
}

@media (min-width: 900px) {
    .hero {
        min-height: 820px;
        padding-bottom: 56px;
    }
}

.hero__photo {
    position: absolute;
    width: 100%;
    height: 100%;
    inset: 0;
    object-fit: cover;
}

/*
 * Le dégradé diffère entre les deux maquettes : sur mobile le texte occupe
 * une plus grande part de l'image, le voile y est donc plus dense en bas.
 *
 * Réserve : la maquette suppose une photo de couverture sombre, alors que
 * celle-ci vient de l'API et peut être très lumineuse. Le surtitre en or à
 * 10 px devient alors difficile à lire. Le contraste réel est à mesurer et à
 * corriger dans l'audit d'accessibilité (issue #33), probablement par un
 * voile local sous le bloc de texte.
 */
.hero__voile {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgb(18 16 12 / 40%) 0%,
        rgb(18 16 12 / 0%) 40%,
        rgb(18 16 12 / 92%) 100%
    );
}

@media (min-width: 900px) {
    .hero__voile {
        background: linear-gradient(
            to bottom,
            rgb(18 16 12 / 55%) 0%,
            rgb(18 16 12 / 5%) 35%,
            rgb(18 16 12 / 85%) 100%
        );
    }
}

.hero__copy {
    position: relative;
    display: flex;
    max-width: 1100px;
    flex-direction: column;
    gap: 16px;
}

.hero__kicker {
    color: var(--or-remplissage);
    font-family: var(--police-ui);
    font-size: clamp(10px, 1vw, 12px);
    letter-spacing: clamp(3px, 0.4vw, 5px);
    text-transform: uppercase;
}

.hero__titre {
    max-width: 14ch;
    color: var(--texte-sur-sombre);
    font-size: clamp(52px, 8vw, 115px);
    font-weight: 600;
    letter-spacing: -2px;
    line-height: 0.93;
}

.hero__accroche {
    max-width: 520px;
    color: rgb(244 240 232 / 92%);
    font-size: clamp(15px, 1.2vw, 17px);
    line-height: 1.55;
}

.hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 6px;
}

/* Sur mobile la maquette empile un seul bouton pleine largeur. */
@media (max-width: 599px) {
    .hero__actions {
        flex-direction: column;
    }

    .hero__actions :deep(.bouton) {
        justify-content: center;
    }
}
</style>
