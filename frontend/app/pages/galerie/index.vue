<script setup lang="ts">
import type { ModeAffichage } from '~/components/GalerieBascule.vue'

useHead({ title: 'Galerie' })

const route = useRoute()

/*
 * L'état de la galerie tient entièrement dans l'URL : catégorie filtrée et
 * mode d'affichage. Chaque vue est ainsi partageable, indexable et navigable
 * avec les boutons précédent et suivant.
 */
const categorieActive = computed(() => String(route.query.categorie ?? ''))
const modeActif = computed<ModeAffichage>(() => route.query.vue === 'grille' ? 'grille' : 'mosaique')

/*
 * La maquette ne prévoit aucune pagination. Plutôt que d'en inventer une,
 * la page charge une première tranche généreuse et propose d'en charger
 * davantage seulement s'il reste quelque chose à voir.
 */
const TRANCHE = 24
const nombreDemande = ref(TRANCHE)

// Changer de filtre repart de la première tranche.
watch(categorieActive, () => {
    nombreDemande.value = TRANCHE
})

const { data: photos } = await usePhotos(() => ({
    itemsPerPage: nombreDemande.value,
    'category.slug': categorieActive.value || undefined,
}))

const { data: categories } = await useCategories()

const liste = computed(() => photos.value?.member ?? [])
const total = computed(() => photos.value?.totalItems ?? 0)
const universDisponibles = computed(() => categories.value?.member ?? [])
const resteAcharger = computed(() => total.value > liste.value.length)

/** Index de la photographie ouverte dans la visionneuse (issue #22). */
const photoOuverte = ref<number | null>(null)
</script>

<template>
    <div class="galerie px-5.5 pt-10 pb-20 bureau:px-10 bureau:pt-13 bureau:pb-25">
        <div class="mb-6.5 flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="surtitre">Archive complète</p>
                <h1 class="font-display text-[clamp(56px,6.5vw,90px)] tracking-[-1.5px]">Galerie</h1>
            </div>

            <GalerieBascule :actif="modeActif" />
        </div>

        <AppFiltresCategories :categories="universDisponibles" :actif="categorieActive" base="/galerie" />

        <p class="galerie__compte mt-5.5 mb-4 font-mono text-[11px] uppercase tracking-[2px] text-base-content/60" aria-live="polite">
            {{ total }} {{ total > 1 ? 'photographies' : 'photographie' }}
        </p>

        <!--
            Mosaïque en colonnes CSS : les tuiles gardent leurs proportions
            d'origine et se rangent d'elles-mêmes, sans mesure JavaScript ni
            saut de mise en page au chargement des images.
        -->
        <div
            :class="modeActif === 'grille'
                ? 'grid grid-cols-2 gap-2 tablette:grid-cols-3 bureau:grid-cols-5'
                : 'columns-1 gap-4 *:mb-4 *:break-inside-avoid tablette:columns-2 bureau:columns-3'"
        >
            <AppCartePhoto
                v-for="(photo, rang) in liste"
                :key="photo['@id']"
                :photo="photo"
                :variante="modeActif"
                :prioritaire="rang < 3"
                @ouvrir="photoOuverte = rang"
            />
        </div>

        <AppVisionneuse v-model:index="photoOuverte" :photos="liste" />

        <div v-if="resteAcharger" class="mt-10 flex justify-center">
            <button type="button" class="btn btn-primary" @click="nombreDemande += TRANCHE">
                Charger plus
            </button>
        </div>
    </div>
</template>
