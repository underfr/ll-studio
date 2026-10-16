<script setup lang="ts">
useHead({ title: 'Albums' })

const route = useRoute()

/*
 * Comme la galerie, le filtre vit dans l'URL : /albums?categorie=spectacle
 * est un lien partageable et indexable.
 */
const categorieActive = computed(() => String(route.query.categorie ?? ''))

const { data: albums } = await useAlbums(() => ({
    itemsPerPage: 24,
    'category.slug': categorieActive.value || undefined,
}))

const { data: categories } = await useCategories()

const series = computed(() => albums.value?.member ?? [])
const total = computed(() => albums.value?.totalItems ?? 0)
const universDisponibles = computed(() => categories.value?.member ?? [])
</script>

<template>
    <div class="albums px-5.5 pt-10 pb-20 bureau:px-10 bureau:pt-13 bureau:pb-25">
        <div class="mb-6.5">
            <p class="surtitre">Séries photographiques</p>
            <h1 class="font-display text-[clamp(56px,6.5vw,90px)] tracking-[-1.5px]">Albums</h1>
        </div>

        <AppFiltresCategories
            :categories="universDisponibles"
            :actif="categorieActive"
            base="/albums"
        />

        <p class="albums__compte mt-5.5 mb-4 font-mono text-[11px] uppercase tracking-[2px] text-base-content/60" aria-live="polite">
            {{ total }} {{ total > 1 ? 'séries' : 'série' }}
        </p>

        <!--
            auto-fill et non auto-fit, comme sur l'accueil : deux séries seules
            gardent la taille de carte prévue au lieu de s'étirer.
        -->
        <div
            v-if="series.length"
            class="albums__grille grid gap-3.5 tablette:grid-cols-[repeat(auto-fill,minmax(260px,1fr))] tablette:gap-5"
        >
            <AppCarteAlbum
                v-for="(album, rang) in series"
                :key="album['@id']"
                :album="album"
                :prioritaire="rang < 4"
            />
        </div>

        <p v-else class="albums__vide alert">
            Aucune série publiée dans cette catégorie pour le moment.
        </p>
    </div>
</template>
