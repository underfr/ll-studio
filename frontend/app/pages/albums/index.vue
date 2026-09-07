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
    <div class="albums">
        <div class="albums__tete">
            <p class="section__kicker">Séries photographiques</p>
            <h1 class="albums__titre">Albums</h1>
        </div>

        <AppFiltresCategories
            :categories="universDisponibles"
            :actif="categorieActive"
            base="/albums"
        />

        <p class="albums__compte" aria-live="polite">
            {{ total }} {{ total > 1 ? 'séries' : 'série' }}
        </p>

        <div v-if="series.length" class="albums__grille">
            <AppCarteAlbum
                v-for="(album, rang) in series"
                :key="album['@id']"
                :album="album"
                :prioritaire="rang < 4"
            />
        </div>

        <p v-else class="albums__vide">
            Aucune série publiée dans cette catégorie pour le moment.
        </p>
    </div>
</template>

<style scoped>
.albums {
    padding: 40px var(--gouttiere) 80px;
}

@media (min-width: 900px) {
    .albums {
        padding: 52px var(--gouttiere) 100px;
    }
}

.albums__tete {
    margin-bottom: 26px;
}

.albums__titre {
    font-size: clamp(56px, 6.5vw, 90px);
    letter-spacing: -1.5px;
}

.albums__compte {
    margin: 22px 0 16px;
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.albums__grille {
    display: grid;
    gap: 14px;
    grid-template-columns: 1fr;
}

@media (min-width: 600px) {
    .albums__grille {
        gap: 20px;
        /*
         * auto-fill et non auto-fit, comme sur l'accueil : deux séries seules
         * gardent la taille de carte prévue au lieu de s'étirer.
         */
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    }
}

.albums__vide {
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: 12px;
    letter-spacing: 1px;
}
</style>
