<script setup lang="ts">
useHead({ title: 'Accueil' })

/*
 * Les trois collections nécessaires à la page. Elles partent en parallèle
 * pendant le rendu serveur : le HTML livré au visiteur contient déjà les
 * photographies et les séries, sans attente côté navigateur.
 */
const { data: photos } = await usePhotos(() => ({ itemsPerPage: 2 }))
const { data: albums } = await useAlbums(() => ({ itemsPerPage: 4 }))
const { data: categories } = await useCategories()

/** La plus récente ouvre la page, la suivante illustre la présentation. */
const photoHero = computed(() => photos.value?.member[0])
const photoPortrait = computed(() => photos.value?.member[1])

const series = computed(() => albums.value?.member ?? [])
const univers = computed(() => categories.value?.member ?? [])
</script>

<template>
    <AccueilHero :photo="photoHero" />

    <AccueilBandeau v-if="univers.length" :categories="univers" />

    <section v-if="series.length" class="px-5.5 py-14 bureau:px-10 bureau:py-20">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 bureau:mb-10">
            <div>
                <p class="surtitre mb-1.5">Travail sélectionné</p>
                <h2 class="font-display text-[clamp(40px,4.5vw,64px)] tracking-[-1px]">Albums en vedette</h2>
            </div>

            <NuxtLink to="/albums" class="link link-hover link-secondary font-mono text-xs uppercase tracking-wider">
                Tout voir →
            </NuxtLink>
        </div>

        <!--
            auto-fill et non auto-fit : les pistes vides sont conservées, donc
            deux séries seules gardent la taille de carte prévue au lieu de
            s'étirer sur la moitié de l'écran chacune.
        -->
        <div class="grid gap-3.5 bureau:grid-cols-[repeat(auto-fill,minmax(260px,1fr))] bureau:gap-5">
            <AppCarteAlbum
                v-for="(album, rang) in series"
                :key="album['@id']"
                :album="album"
                :prioritaire="rang === 0"
            />
        </div>
    </section>

    <AccueilApropos :photo="photoPortrait" :nombre-univers="univers.length" />

    <AccueilAppel />
</template>
