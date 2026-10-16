<script setup lang="ts">
import type { Album } from '~/types/api'

/**
 * Carte d'une série, sur la « card » de daisyUI : la photo de couverture en
 * fond, un voile dégradé, la catégorie en badge et le titre posés en bas.
 * Elle sert l'accueil et la page des albums.
 */
defineProps<{
    album: Album
    /** La première carte d'une page est chargée sans attendre le défilement. */
    prioritaire?: boolean
}>()
</script>

<template>
    <!-- La maquette mobile pose des cartes en 3/2, la desktop en 4/5. -->
    <NuxtLink
        :to="`/albums/${album.slug}`"
        class="carte card group relative aspect-3/2 justify-end overflow-hidden bg-base-200 shadow-sm bureau:aspect-4/5"
    >
        <img
            v-if="album.coverPhoto"
            class="carte__photo absolute inset-0 size-full object-cover transition-transform duration-600 ease-[cubic-bezier(0.32,0.72,0,1)] group-hover:scale-104 group-focus-visible:scale-104"
            :src="urlMedia(album.coverPhoto.contentUrl)"
            :alt="album.coverPhoto.alt"
            :loading="prioritaire ? 'eager' : 'lazy'"
            decoding="async"
        >
        <span class="absolute inset-0 bg-linear-to-b from-neutral/0 from-45% to-neutral/90" />

        <span class="card-body relative flex-none gap-2 p-4 bureau:p-5.5">
            <span class="badge badge-primary badge-sm font-mono uppercase tracking-wider">
                {{ album.category.name }} · {{ album.photoCount }}
                {{ album.photoCount > 1 ? 'photos' : 'photo' }}
            </span>
            <span class="card-title font-display text-[28px] font-medium leading-tight text-neutral-content bureau:text-3xl">
                {{ album.title }}
            </span>
        </span>
    </NuxtLink>
</template>
