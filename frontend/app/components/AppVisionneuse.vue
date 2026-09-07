<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Visionneuse plein écran.
 *
 * Elle ne figure dans aucune maquette : elle emprunte donc le vocabulaire du
 * reste du site, surtitre en or et Space Mono capitales espacées, titre en
 * Cormorant, commandes réduites à des traits fins. Le fond est nettement plus
 * sombre que le voile du menu mobile, pour que la photographie prime.
 */
const props = defineProps<{ photos: Photo[] }>()

/** Index de la photographie affichée, null quand la visionneuse est fermée. */
const index = defineModel<number | null>('index', { required: true })

const panneau = ref<HTMLElement | null>(null)
const boutonFermer = ref<HTMLButtonElement | null>(null)

/** Élément qui avait le focus avant l'ouverture, pour le lui rendre ensuite. */
let origineDuFocus: HTMLElement | null = null

const ouverte = computed(() => index.value !== null)
const photo = computed(() => index.value === null ? null : props.photos[index.value] ?? null)
const plusieurs = computed(() => props.photos.length > 1)

function fermer(): void {
    index.value = null
}

/** La navigation boucle : après la dernière photographie vient la première. */
function deplacer(pas: number): void {
    if (index.value === null || props.photos.length === 0) {
        return
    }

    index.value = (index.value + pas + props.photos.length) % props.photos.length
}

/**
 * Précharge les voisines pour que la navigation ne fasse pas clignoter un
 * cadre vide entre deux photographies.
 */
function prechargerVoisines(): void {
    if (index.value === null || !import.meta.client) {
        return
    }

    for (const pas of [-1, 1]) {
        const voisine = props.photos[(index.value + pas + props.photos.length) % props.photos.length]

        if (voisine) {
            const image = new Image()
            image.src = urlMedia(voisine.contentUrl)
        }
    }
}

function surTouche(evenement: KeyboardEvent): void {
    if (!ouverte.value) {
        return
    }

    if (evenement.key === 'Escape') {
        evenement.preventDefault()
        fermer()

        return
    }

    if (evenement.key === 'ArrowLeft') {
        evenement.preventDefault()
        deplacer(-1)

        return
    }

    if (evenement.key === 'ArrowRight') {
        evenement.preventDefault()
        deplacer(1)

        return
    }

    // Le focus reste captif : sans cela, Tab partirait sur la page masquée.
    if (evenement.key !== 'Tab' || !panneau.value) {
        return
    }

    const focusables = panneau.value.querySelectorAll<HTMLElement>('button:not([disabled])')

    if (focusables.length === 0) {
        return
    }

    const premier = focusables[0]!
    const dernier = focusables[focusables.length - 1]!

    if (evenement.shiftKey && document.activeElement === premier) {
        evenement.preventDefault()
        dernier.focus()
    }
    else if (!evenement.shiftKey && document.activeElement === dernier) {
        evenement.preventDefault()
        premier.focus()
    }
}

onMounted(() => document.addEventListener('keydown', surTouche))

onBeforeUnmount(() => {
    document.removeEventListener('keydown', surTouche)
    document.body.style.overflow = ''
})

watch(ouverte, async (estOuverte) => {
    document.body.style.overflow = estOuverte ? 'hidden' : ''

    if (estOuverte) {
        origineDuFocus = document.activeElement as HTMLElement | null
        await nextTick()
        boutonFermer.value?.focus()
        prechargerVoisines()
    }
    else {
        origineDuFocus?.focus()
        origineDuFocus = null
    }
})

watch(index, () => {
    if (ouverte.value) {
        prechargerVoisines()
    }
})
</script>

<template>
    <Teleport to="body">
        <div
            v-if="ouverte && photo"
            ref="panneau"
            class="visionneuse"
            role="dialog"
            aria-modal="true"
            :aria-label="`Photographie : ${photo.title}`"
        >
            <!-- Fermer en cliquant à côté de la photographie. -->
            <div class="visionneuse__fond" @click="fermer" />

            <div class="visionneuse__barre">
                <p class="visionneuse__compteur">
                    {{ (index ?? 0) + 1 }} / {{ photos.length }}
                </p>

                <button
                    ref="boutonFermer"
                    type="button"
                    class="visionneuse__fermer"
                    aria-label="Fermer la visionneuse"
                    @click="fermer"
                >
                    &times;
                </button>
            </div>

            <figure class="visionneuse__scene">
                <img
                    :key="photo['@id']"
                    class="visionneuse__photo"
                    :src="urlMedia(photo.contentUrl)"
                    :alt="photo.alt"
                    decoding="async"
                >

                <figcaption class="visionneuse__legende">
                    <p v-if="photo.category" class="visionneuse__categorie">
                        {{ photo.category.name }}
                    </p>
                    <p class="visionneuse__titre">{{ photo.title }}</p>
                    <p v-if="photo.description" class="visionneuse__description">
                        {{ photo.description }}
                    </p>
                </figcaption>
            </figure>

            <template v-if="plusieurs">
                <button
                    type="button"
                    class="visionneuse__nav visionneuse__nav--precedent"
                    aria-label="Photographie précédente"
                    @click="deplacer(-1)"
                >
                    &#8592;
                </button>
                <button
                    type="button"
                    class="visionneuse__nav visionneuse__nav--suivant"
                    aria-label="Photographie suivante"
                    @click="deplacer(1)"
                >
                    &#8594;
                </button>
            </template>
        </div>
    </Teleport>
</template>

<style scoped>
.visionneuse {
    position: fixed;
    z-index: 60;
    display: flex;
    flex-direction: column;
    inset: 0;
}

.visionneuse__fond {
    position: absolute;
    inset: 0;
    background-color: var(--scrim-visionneuse);
}

.visionneuse__barre {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px var(--gouttiere);
}

.visionneuse__compteur {
    color: rgb(244 240 232 / 55%);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 2px;
}

.visionneuse__fermer {
    padding: 0 6px;
    border: 0;
    background: none;
    color: var(--texte-sur-sombre);
    cursor: pointer;
    font-family: var(--police-texte);
    font-size: 30px;
    line-height: 1;
}

.visionneuse__scene {
    position: relative;
    display: flex;
    min-height: 0;
    flex: 1;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin: 0;
    padding: 0 var(--gouttiere) 32px;
}

.visionneuse__photo {
    max-width: 100%;
    /* La photographie occupe la place restante sans jamais pousser la
       légende hors de l'écran. */
    min-height: 0;
    flex: 1;
    object-fit: contain;
}

.visionneuse__legende {
    display: flex;
    max-width: 720px;
    flex-direction: column;
    gap: 6px;
    text-align: center;
}

.visionneuse__categorie {
    color: var(--or-remplissage);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.visionneuse__titre {
    color: var(--texte-sur-sombre);
    font-family: var(--police-titre);
    font-size: clamp(24px, 3vw, 34px);
    font-weight: 500;
    line-height: 1.1;
}

.visionneuse__description {
    color: rgb(244 240 232 / 72%);
    font-size: 14px;
    line-height: 1.6;
}

.visionneuse__nav {
    position: absolute;
    top: 50%;
    display: flex;
    width: 48px;
    height: 48px;
    align-items: center;
    justify-content: center;
    border: 1px solid rgb(244 240 232 / 30%);
    background: none;
    color: var(--texte-sur-sombre);
    cursor: pointer;
    font-size: 20px;
    transform: translateY(-50%);
    transition: border-color var(--transition-courte), background-color var(--transition-courte);
}

.visionneuse__nav:hover {
    border-color: var(--texte-sur-sombre);
    background-color: rgb(244 240 232 / 12%);
}

.visionneuse__nav--precedent {
    left: 12px;
}

.visionneuse__nav--suivant {
    right: 12px;
}

@media (min-width: 900px) {
    .visionneuse__nav--precedent {
        left: 24px;
    }

    .visionneuse__nav--suivant {
        right: 24px;
    }
}
</style>
