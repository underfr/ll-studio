<script setup lang="ts">
const route = useRoute()
const slug = computed(() => String(route.params.slug))

/*
 * Deux requêtes indépendantes plutôt qu'une chaîne : les métadonnées de la
 * série d'un côté, ses photographies de l'autre via le filtre albums.slug
 * ouvert à l'issue #7. Elles partent en parallèle, et la pagination des
 * photos reste utilisable.
 */
const { album } = await useAlbum(() => slug.value)
const { data: photos } = await usePhotos(() => ({
    'albums.slug': slug.value,
    itemsPerPage: 48,
}))

// Un slug inconnu doit répondre 404, pas une page vide.
if (!album.value) {
    throw createError({
        statusCode: 404,
        statusMessage: "Cette série n'existe pas.",
        fatal: true,
    })
}

const liste = computed(() => photos.value?.member ?? [])

/*
 * La couverture retombe sur la première photographie de la série quand
 * aucune n'a été désignée, comme prévu au modèle de données (issue #3).
 */
const couverture = computed(() => album.value?.coverPhoto ?? liste.value[0])

const nombrePhotos = computed(() => album.value?.photoCount ?? liste.value.length)

/** Index de la photographie ouverte dans la visionneuse (issue #22). */
const photoOuverte = ref<number | null>(null)

useHead(() => ({
    title: album.value?.title ?? 'Série',
    meta: [
        {
            name: 'description',
            content: album.value?.description ?? `Série photographique ${album.value?.title ?? ''}.`,
        },
    ],
}))
</script>

<template>
    <article v-if="album">
        <header class="couverture">
            <img
                v-if="couverture"
                class="couverture__photo"
                :src="urlMedia(couverture.contentUrl)"
                :alt="couverture.alt"
                fetchpriority="high"
                decoding="async"
            >
            <div class="couverture__voile" />

            <div class="couverture__copy">
                <NuxtLink to="/albums" class="couverture__retour">← Retour aux albums</NuxtLink>

                <div class="couverture__meta">
                    <p class="couverture__categorie">
                        {{ album.category.name }} · {{ nombrePhotos }}
                        {{ nombrePhotos > 1 ? 'photos' : 'photo' }}
                    </p>

                    <h1 class="couverture__titre">{{ album.title }}</h1>

                    <p v-if="album.description" class="couverture__description">
                        {{ album.description }}
                    </p>
                </div>
            </div>
        </header>

        <div class="serie">
            <div v-if="liste.length" class="serie__mosaique">
                <AppCartePhoto
                    v-for="(photo, rang) in liste"
                    :key="photo['@id']"
                    :photo="photo"
                    variante="mosaique"
                    :avec-legende="false"
                    :prioritaire="rang < 3"
                    @ouvrir="photoOuverte = rang"
                />
            </div>

            <p v-else class="serie__vide">
                Cette série ne contient encore aucune photographie publiée.
            </p>

            <AppVisionneuse v-model:index="photoOuverte" :photos="liste" />
        </div>
    </article>
</template>

<style scoped>
.couverture {
    position: relative;
    display: flex;
    min-height: 380px;
    flex-direction: column;
    justify-content: flex-end;
    padding: 0 var(--gouttiere) 36px;
    overflow: hidden;
    background-color: var(--texte-principal);
}

@media (min-width: 900px) {
    .couverture {
        min-height: 470px;
        padding-bottom: 44px;
    }
}

.couverture__photo {
    position: absolute;
    width: 100%;
    height: 100%;
    inset: 0;
    object-fit: cover;
}

.couverture__voile {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgb(18 16 12 / 40%) 0%,
        rgb(18 16 12 / 90%) 100%
    );
}

.couverture__copy {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.couverture__retour {
    color: rgb(244 240 232 / 82%);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: color var(--transition-courte);
}

.couverture__retour:hover {
    color: var(--texte-sur-sombre);
}

.couverture__meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.couverture__categorie {
    color: var(--or-remplissage);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 4px;
    text-transform: uppercase;
}

.couverture__titre {
    color: var(--texte-sur-sombre);
    font-size: clamp(44px, 7.2vw, 104px);
    font-weight: 600;
    letter-spacing: -2px;
    line-height: 0.95;
}

.couverture__description {
    max-width: 560px;
    color: rgb(244 240 232 / 86%);
    font-size: clamp(15px, 1.1vw, 16px);
    line-height: 1.6;
}

.serie {
    padding: 40px var(--gouttiere) 80px;
}

@media (min-width: 900px) {
    .serie {
        padding: 52px var(--gouttiere) 100px;
    }
}

/*
 * Même mosaïque que la galerie, sans légende : la couverture a déjà annoncé
 * la catégorie et le titre de la série.
 */
.serie__mosaique {
    column-gap: 14px;
    columns: 1;
}

.serie__mosaique > * {
    margin-bottom: 14px;
    break-inside: avoid;
}

@media (min-width: 600px) {
    .serie__mosaique {
        columns: 2;
    }
}

@media (min-width: 900px) {
    .serie__mosaique {
        columns: 3;
    }
}

.serie__vide {
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: 12px;
    letter-spacing: 1px;
}
</style>
