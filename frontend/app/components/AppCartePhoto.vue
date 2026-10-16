<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Tuile d'une photographie dans la galerie.
 *
 * Deux variantes, reprises des deux maquettes :
 * - « mosaique » laisse respirer l'image et ne révèle la légende qu'au survol
 * - « grille » impose un carré et affiche la légende en permanence
 *
 * La tuile est un bouton : elle ouvre la photographie dans la visionneuse.
 * Un <figure> aurait été plus parlant sémantiquement, mais le modèle de
 * contenu de <button> ne l'accepte pas ; le libellé accessible porte donc
 * l'intention à sa place.
 */
const props = withDefaults(defineProps<{
    photo: Photo
    variante?: 'mosaique' | 'grille'
    prioritaire?: boolean
    /**
     * La mosaïque d'une série se passe de légende : la catégorie et le titre
     * de l'album sont déjà annoncés par le bandeau de couverture.
     */
    avecLegende?: boolean
}>(), {
    variante: 'mosaique',
    prioritaire: false,
    avecLegende: true,
})

defineEmits<{ ouvrir: [] }>()

const enGrille = computed(() => props.variante === 'grille')

/*
 * En mosaïque, la maquette ne révèle voile et légende qu'au survol. Le
 * masquage est réservé aux appareils qui savent survoler : sur écran tactile
 * la légende resterait autrement inaccessible.
 */
const revelation = computed(() => enGrille.value
    ? ''
    : 'transition-opacity [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 [@media(hover:hover)]:group-focus-visible:opacity-100')
</script>

<template>
    <!--
        En mosaïque, l'image reste dans le flux : c'est elle qui donne sa
        hauteur à la tuile, donc chaque photographie garde ses proportions.
        En grille, la tuile impose un carré et l'image le remplit en recadrant.
    -->
    <button
        type="button"
        class="tuile group relative block w-full cursor-pointer overflow-hidden rounded-box bg-base-200 text-left shadow-sm"
        :class="enGrille ? 'tuile--grille aspect-square' : 'tuile--mosaique'"
        :aria-label="`Agrandir : ${photo.title}`"
        @click="$emit('ouvrir')"
    >
        <img
            class="tuile__photo block w-full transition-transform duration-600 ease-[cubic-bezier(0.32,0.72,0,1)] group-hover:scale-103"
            :class="enGrille ? 'absolute inset-0 h-full object-cover' : 'h-auto'"
            :src="urlMedia(photo.contentUrl)"
            :alt="photo.alt"
            :loading="prioritaire ? 'eager' : 'lazy'"
            decoding="async"
        >

        <span
            v-if="avecLegende"
            class="tuile__voile pointer-events-none absolute inset-0 bg-linear-to-b from-neutral/0 from-55% to-neutral/85"
            :class="revelation"
        />

        <!-- La légende est posée sur l'image, jamais en dessous. -->
        <span
            v-if="avecLegende"
            class="tuile__legende pointer-events-none absolute flex flex-col"
            :class="[enGrille ? 'inset-x-4.5 bottom-4.5' : 'inset-x-3.5 bottom-3.5', revelation]"
        >
            <span v-if="photo.category" class="tuile__categorie font-mono text-[9px] uppercase tracking-[2px] text-primary">{{ photo.category.name }}</span>
            <span
                class="tuile__titre font-display font-medium leading-tight text-neutral-content"
                :class="enGrille ? 'text-2xl' : 'text-xl'"
            >{{ photo.title }}</span>
        </span>
    </button>
</template>
