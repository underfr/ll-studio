<script setup lang="ts">
useHead({ title: 'Galerie' })

/*
 * Sonde de bout en bout des composables de l'issue #18 : elle vérifie que
 * les données traversent réellement le rendu serveur et que les URL de
 * médias pointent vers l'API. La grille responsive arrive à l'issue #20,
 * la vue détail d'une série à l'issue #21.
 */
const { data: albums } = await useAlbums(() => ({ itemsPerPage: 3 }))
const { data: photos } = await usePhotos(() => ({ itemsPerPage: 1 }))
const { data: categories } = await useCategories()
</script>

<template>
    <section class="jalon">
        <p class="jalon__rubrique">Galerie</p>
        <h1 class="jalon__titre">Toutes les séries</h1>

        <dl class="sonde">
            <div class="sonde__ligne">
                <dt>Séries publiées</dt>
                <dd>{{ albums?.totalItems ?? 0 }}</dd>
            </div>
            <div class="sonde__ligne">
                <dt>Photographies visibles</dt>
                <dd>{{ photos?.totalItems ?? 0 }}</dd>
            </div>
            <div class="sonde__ligne">
                <dt>Catégories</dt>
                <dd>{{ categories?.member.map(categorie => categorie.name).join(', ') }}</dd>
            </div>
        </dl>

        <ul class="sonde__series">
            <li v-for="album in albums?.member" :key="album['@id']">
                <img
                    v-if="album.coverPhoto"
                    :src="urlMedia(album.coverPhoto.contentUrl)"
                    :alt="album.coverPhoto.alt"
                    width="160"
                    height="107"
                >
                <span>{{ album.category.name }} · {{ album.photoCount }} photos</span>
                <strong>{{ album.title }}</strong>
            </li>
        </ul>

        <p class="jalon__note">
            Sonde temporaire des composables d'API. Grille responsive à l'issue #20,
            vue détail d'un album à l'issue #21.
        </p>
    </section>
</template>

<style scoped>
.sonde {
    margin: 40px 0 0;
    border-top: 1px solid var(--bordure-tenue);
}

.sonde__ligne {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    padding: 12px 0;
    border-bottom: 1px solid var(--bordure-tenue);
}

.sonde__ligne dt {
    min-width: 220px;
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.sonde__ligne dd {
    margin: 0;
    font-family: var(--police-ui);
    font-size: 13px;
}

.sonde__series {
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
    margin: 32px 0 0;
    padding: 0;
    list-style: none;
}

.sonde__series li {
    display: flex;
    max-width: 200px;
    flex-direction: column;
    gap: 6px;
}

.sonde__series img {
    height: auto;
    object-fit: cover;
}

.sonde__series span {
    color: var(--or-texte);
    font-family: var(--police-ui);
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.sonde__series strong {
    font-family: var(--police-titre);
    font-size: 22px;
    font-weight: 500;
    line-height: 1.1;
}
</style>
