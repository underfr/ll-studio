<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Section de présentation. Le compteur « Univers » vient des catégories de
 * l'API plutôt que d'être figé : il reste juste quand le photographe en
 * ajoute une.
 */
defineProps<{
    photo?: Photo
    nombreUnivers: number
}>()
</script>

<template>
    <section class="apropos">
        <div class="apropos__texte">
            <div class="apropos__copy">
                <p class="apropos__kicker">À propos</p>

                <h2 class="apropos__titre">
                    Chaque cadrage est une décision, jamais un hasard.
                </h2>

                <p class="apropos__bio">
                    Basé entre la ville et la nuit, je travaille l'obscurité comme une
                    matière, des aurores boréales aux fosses de concert, de la Voie lactée
                    aux carrosseries chromées. Mon obsession : la texture de la lumière.
                </p>
            </div>

            <dl class="apropos__chiffres">
                <div>
                    <dd>120+</dd>
                    <dt>Séances</dt>
                </div>
                <div>
                    <dd>{{ nombreUnivers }}</dd>
                    <dt>Univers</dt>
                </div>
                <div>
                    <dd>8</dd>
                    <dt>Ans</dt>
                </div>
            </dl>
        </div>

        <div class="apropos__visuel">
            <img
                v-if="photo"
                :src="urlMedia(photo.contentUrl)"
                :alt="photo.alt"
                loading="lazy"
                decoding="async"
            >
        </div>
    </section>
</template>

<style scoped>
.apropos {
    display: grid;
    border-top: 1px solid var(--bordure-default);
    grid-template-columns: 1fr;
}

@media (min-width: 900px) {
    .apropos {
        grid-template-columns: 1fr 1fr;
    }
}

.apropos__texte {
    display: flex;
    min-height: 420px;
    flex-direction: column;
    justify-content: center;
    gap: 36px;
    padding: 56px var(--gouttiere);
    order: 2;
}

@media (min-width: 900px) {
    .apropos__texte {
        padding: 80px var(--gouttiere);
        order: 1;
    }
}

.apropos__copy {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.apropos__kicker {
    color: var(--or-texte);
    font-family: var(--police-ui);
    font-size: clamp(10px, 0.9vw, 11px);
    letter-spacing: clamp(3px, 0.35vw, 4px);
    text-transform: uppercase;
}

.apropos__titre {
    max-width: 16ch;
    font-size: clamp(34px, 3.6vw, 52px);
    letter-spacing: -1px;
    line-height: 1.05;
}

.apropos__bio {
    max-width: 440px;
    color: var(--texte-secondaire);
    font-size: clamp(15px, 1.1vw, 16px);
    line-height: 1.65;
}

.apropos__chiffres {
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
    margin: 0;
}

@media (min-width: 900px) {
    .apropos__chiffres {
        gap: 44px;
    }
}

.apropos__chiffres div {
    display: flex;
    flex-direction: column;
}

/* dd avant dt dans le flux visuel : le chiffre domine, le libellé le qualifie. */
.apropos__chiffres dd {
    margin: 0;
    color: var(--or-texte);
    font-family: var(--police-titre);
    font-size: clamp(38px, 3vw, 44px);
    font-weight: 500;
    line-height: 1;
}

.apropos__chiffres dt {
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: clamp(9px, 0.8vw, 10px);
    letter-spacing: 2px;
    text-transform: uppercase;
}

.apropos__visuel {
    min-height: 260px;
    order: 1;
}

@media (min-width: 900px) {
    .apropos__visuel {
        min-height: 420px;
        border-left: 1px solid var(--bordure-default);
        order: 2;
    }
}

.apropos__visuel img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
