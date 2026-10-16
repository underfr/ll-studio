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
    const detail = "Cette série n'existe pas."

    // `detail` est la phrase montrée au visiteur par app/error.vue ;
    // `statusMessage` reste la formulation technique, côté journaux et HTTP.
    throw createError({
        statusCode: 404,
        statusMessage: detail,
        data: { detail },
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
        <header class="couverture hero relative min-h-[380px] items-end justify-items-start overflow-hidden bg-neutral bureau:min-h-[470px]">
            <img
                v-if="couverture"
                class="couverture__photo absolute inset-0 size-full object-cover"
                :src="urlMedia(couverture.contentUrl)"
                :alt="couverture.alt"
                fetchpriority="high"
                decoding="async"
            >
            <div class="absolute inset-0 bg-linear-to-b from-neutral/40 to-neutral/90" />

            <div class="hero-content relative w-full max-w-none flex-col items-start gap-4 px-5.5 pb-9 bureau:px-10 bureau:pb-11">
                <NuxtLink
                    to="/albums"
                    class="link link-hover font-mono text-[11px] uppercase tracking-[2px] text-neutral-content/80 hover:text-neutral-content"
                >
                    ← Retour aux albums
                </NuxtLink>

                <div class="flex flex-col gap-2">
                    <p class="font-mono text-[11px] uppercase tracking-[4px] text-primary">
                        {{ album.category.name }} · {{ nombrePhotos }}
                        {{ nombrePhotos > 1 ? 'photos' : 'photo' }}
                    </p>

                    <h1 class="font-display text-[clamp(44px,7.2vw,104px)] font-semibold leading-[0.95] tracking-[-2px] text-neutral-content">
                        {{ album.title }}
                    </h1>

                    <p v-if="album.description" class="max-w-[560px] text-[clamp(15px,1.1vw,16px)] leading-relaxed text-neutral-content/85">
                        {{ album.description }}
                    </p>
                </div>
            </div>
        </header>

        <div class="px-5.5 pt-10 pb-20 bureau:px-10 bureau:pt-13 bureau:pb-25">
            <!-- Même mosaïque que la galerie, sans légende : la couverture a
                 déjà annoncé la catégorie et le titre de la série. -->
            <div v-if="liste.length" class="columns-1 gap-3.5 *:mb-3.5 *:break-inside-avoid tablette:columns-2 bureau:columns-3">
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

            <p v-else class="serie__vide alert">
                Cette série ne contient encore aucune photographie publiée.
            </p>

            <AppVisionneuse v-model:index="photoOuverte" :photos="liste" />
        </div>
    </article>
</template>
