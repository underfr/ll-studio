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
    <div class="galerie">
        <div class="galerie__tete">
            <div>
                <p class="section__kicker">Archive complète</p>
                <h1 class="galerie__titre">Galerie</h1>
            </div>

            <GalerieBascule :actif="modeActif" />
        </div>

        <AppFiltresCategories :categories="universDisponibles" :actif="categorieActive" base="/galerie" />

        <p class="galerie__compte" aria-live="polite">
            {{ total }} {{ total > 1 ? 'photographies' : 'photographie' }}
        </p>

        <div :class="modeActif === 'grille' ? 'grille' : 'mosaique'">
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

        <div v-if="resteAcharger" class="galerie__suite">
            <button type="button" class="bouton bouton--or" @click="nombreDemande += TRANCHE">
                Charger plus
            </button>
        </div>
    </div>
</template>

<style scoped>
.galerie {
    padding: 40px var(--gouttiere) 80px;
}

@media (min-width: 900px) {
    .galerie {
        padding: 52px var(--gouttiere) 100px;
    }
}

.galerie__tete {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 26px;
}

.galerie__titre {
    font-size: clamp(56px, 6.5vw, 90px);
    letter-spacing: -1.5px;
}

.galerie__compte {
    margin: 22px 0 16px;
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

/*
 * Mosaïque en colonnes CSS : les tuiles gardent leurs proportions d'origine
 * et se rangent d'elles-mêmes, sans mesure JavaScript ni saut de mise en page
 * au chargement des images.
 */
.mosaique {
    column-gap: 16px;
    columns: 1;
}

.mosaique > * {
    margin-bottom: 16px;
    break-inside: avoid;
}

@media (min-width: 600px) {
    .mosaique {
        columns: 2;
    }
}

@media (min-width: 900px) {
    .mosaique {
        columns: 3;
    }
}

.grille {
    display: grid;
    gap: 2px;
    grid-template-columns: repeat(2, 1fr);
}

@media (min-width: 600px) {
    .grille {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 900px) {
    .grille {
        grid-template-columns: repeat(5, 1fr);
    }
}

.galerie__suite {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}
</style>
