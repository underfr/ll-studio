<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Visionneuse plein écran, sur la « modal » de daisyUI et l'élément natif
 * <dialog> (issue #68).
 *
 * Ouvert par showModal(), le dialogue passe au-dessus de tout, rend le reste
 * de la page inerte, et daisyUI bloque le défilement. Le reste est écrit ici
 * (RG-LBX) : navigation aux flèches qui boucle, préchargement des voisines,
 * fermeture au clic hors de la photographie, focus gardé dans le dialogue et
 * rendu à son point de départ.
 */
const props = defineProps<{ photos: Photo[] }>()

/** Index de la photographie affichée, null quand la visionneuse est fermée. */
const index = defineModel<number | null>('index', { required: true })

const dialogue = ref<HTMLDialogElement | null>(null)
const boutonFermer = ref<HTMLButtonElement | null>(null)

/** Élément qui avait le focus avant l'ouverture, pour le lui rendre ensuite. */
let origineDuFocus: HTMLElement | null = null

const ouverte = computed(() => index.value !== null)
const photo = computed(() => index.value === null ? null : props.photos[index.value] ?? null)
const plusieurs = computed(() => props.photos.length > 1)

function fermer(): void {
    index.value = null
}

/**
 * Ferme dès que le clic tombe ailleurs que sur la photographie, sa légende
 * ou une commande.
 *
 * Le test porte sur la cible réelle du clic plutôt que sur un calque de fond :
 * la scène et ses marges recouvrent le voile, si bien qu'un simple gestionnaire
 * sur celui-ci laissait une large zone morte autour de l'image.
 */
function surClic(evenement: MouseEvent): void {
    const cible = evenement.target as HTMLElement | null

    if (cible?.closest('.visionneuse__photo, .visionneuse__legende, button')) {
        return
    }

    fermer()
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

    // Le focus reste captif : le dialogue natif laisserait Tab partir vers
    // l'interface du navigateur après la dernière commande.
    piegerTabulation(evenement, dialogue.value)
}

onMounted(() => document.addEventListener('keydown', surTouche))

onBeforeUnmount(() => document.removeEventListener('keydown', surTouche))

watch(ouverte, async (estOuverte) => {
    if (estOuverte) {
        origineDuFocus = document.activeElement as HTMLElement | null
        await nextTick()
        // showModal n'existe pas dans tous les environnements de test.
        if (typeof dialogue.value?.showModal === 'function' && !dialogue.value.open) {
            dialogue.value.showModal()
        }
        boutonFermer.value?.focus()
        prechargerVoisines()
    }
    else {
        // Tant que le dialogue modal est ouvert, le reste de la page est
        // inerte et ne peut pas recevoir le focus : on le ferme d'abord.
        if (dialogue.value?.open) {
            dialogue.value.close()
        }
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
    <!--
        La touche Échap déclenche « cancel » sur un dialogue natif : on le
        laisse passer par fermer(), pour que l'état et le focus suivent.
    -->
    <dialog
        v-if="ouverte && photo"
        ref="dialogue"
        class="visionneuse modal bg-neutral/95"
        aria-modal="true"
        :aria-label="`Photographie : ${photo.title}`"
        @cancel.prevent="fermer"
        @click="surClic"
    >
        <div class="flex h-dvh w-screen flex-col">
            <!--
                Sur ce fond sombre, les états de survol, de focus et d'appui
                sont redéfinis : ceux de daisyUI, pensés pour un fond clair,
                posaient un aplat crème sous une icône claire.
            -->
            <div class="flex items-center justify-between px-5.5 py-4 bureau:px-10">
                <p class="visionneuse__compteur font-mono text-[11px] tracking-[2px] text-neutral-content/60">
                    {{ (index ?? 0) + 1 }} / {{ photos.length }}
                </p>

                <button
                    ref="boutonFermer"
                    type="button"
                    class="btn btn-circle btn-ghost text-neutral-content hover:bg-neutral-content/10 focus-visible:bg-neutral-content/10 active:bg-neutral-content/20"
                    aria-label="Fermer la visionneuse"
                    @click="fermer"
                >
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <!--
                À partir de 900 px, une marge horizontale est réservée aux
                flèches, pour que la photographie ne passe jamais dessous.
            -->
            <figure class="m-0 flex min-h-0 flex-1 flex-col items-center justify-center gap-5 px-5.5 pb-2 bureau:px-26 bureau:pb-6">
                <!--
                    La boîte de l'image épouse l'image rendue, au lieu de
                    s'étirer sur toute la scène : un clic juste à côté de la
                    photographie ferme bien la visionneuse.
                -->
                <img
                    :key="photo['@id']"
                    class="visionneuse__photo h-auto max-h-full min-h-0 w-auto max-w-full rounded-box object-contain"
                    :src="urlMedia(photo.contentUrl)"
                    :alt="photo.alt"
                    decoding="async"
                >

                <figcaption class="visionneuse__legende flex max-w-[720px] flex-col gap-1.5 text-center">
                    <p v-if="photo.category" class="visionneuse__categorie font-mono text-[10px] uppercase tracking-[3px] text-primary">
                        {{ photo.category.name }}
                    </p>
                    <p class="font-display text-[clamp(24px,3vw,34px)] font-medium leading-tight text-neutral-content">
                        {{ photo.title }}
                    </p>
                    <p v-if="photo.description" class="visionneuse__description text-sm leading-relaxed text-neutral-content/70">
                        {{ photo.description }}
                    </p>
                </figcaption>
            </figure>

            <!--
                Sur mobile les flèches se rangent sous la légende : posées sur
                les côtés, elles empiéteraient sur une photographie qui occupe
                déjà toute la largeur. À partir de 900 px, elles reprennent
                leur place latérale ; « contents » efface la boîte du groupe.
            -->
            <div v-if="plusieurs" class="flex justify-center gap-4.5 pb-6.5 bureau:contents">
                <button
                    type="button"
                    class="btn btn-circle btn-outline btn-lg border-neutral-content/30 text-neutral-content hover:border-neutral-content hover:bg-neutral-content/10 focus-visible:border-neutral-content focus-visible:bg-neutral-content/10 active:bg-neutral-content/20 bureau:absolute bureau:top-1/2 bureau:left-6 bureau:-translate-y-1/2"
                    aria-label="Photographie précédente"
                    @click="deplacer(-1)"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="btn btn-circle btn-outline btn-lg border-neutral-content/30 text-neutral-content hover:border-neutral-content hover:bg-neutral-content/10 focus-visible:border-neutral-content focus-visible:bg-neutral-content/10 active:bg-neutral-content/20 bureau:absolute bureau:top-1/2 bureau:right-6 bureau:-translate-y-1/2"
                    aria-label="Photographie suivante"
                    @click="deplacer(1)"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                    </svg>
                </button>
            </div>
        </div>
    </dialog>
</template>
