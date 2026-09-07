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

    <section v-if="series.length" class="section">
        <div class="section__tete">
            <div>
                <p class="section__kicker">Travail sélectionné</p>
                <h2 class="section__titre">Albums en vedette</h2>
            </div>

            <NuxtLink to="/albums" class="section__report">Tout voir →</NuxtLink>
        </div>

        <div class="grille">
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

<style scoped>
.grille {
    display: grid;
    gap: 14px;
    grid-template-columns: 1fr;
}

@media (min-width: 900px) {
    .grille {
        gap: 20px;
        /*
         * auto-fill et non auto-fit : les pistes vides sont conservées, donc
         * deux séries seules gardent la taille de carte prévue au lieu de
         * s'étirer sur la moitié de l'écran chacune.
         */
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    }
}
</style>
