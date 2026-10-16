<script setup lang="ts">
definePageMeta({ layout: 'admin' })

useHead({ title: 'Tableau de bord' })

const { compte } = useSession()

/** Rubriques présentées en cartes, dans l'ordre de la navigation. */
const RUBRIQUES = [
    { titre: 'Photos', chemin: '/admin/photos', description: 'Ajouter, décrire, masquer ou retirer des photographies.' },
    { titre: 'Albums', chemin: '/admin/albums', description: 'Composer les séries, choisir leur couverture et leur ordre.' },
    { titre: 'Catégories', chemin: '/admin/categories', description: 'Organiser les univers qui servent de filtres sur le site.' },
    { titre: 'Messages', chemin: '/admin/messages', description: 'Lire les demandes envoyées depuis la page Contact.' },
]
</script>

<template>
    <section>
        <AdminEnTetePage rubrique="Tableau de bord" :titre="`Bonjour ${compte?.firstName ?? ''}.`">
            Choisissez une rubrique pour gérer le contenu du portfolio.
        </AdminEnTetePage>

        <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <li v-for="rubrique in RUBRIQUES" :key="rubrique.chemin">
                <NuxtLink
                    :to="rubrique.chemin"
                    class="card h-full bg-base-100 shadow-sm transition-shadow hover:shadow-md"
                >
                    <div class="card-body">
                        <h2 class="card-title font-display text-2xl">{{ rubrique.titre }}</h2>
                        <p class="text-sm text-base-content/70">{{ rubrique.description }}</p>
                    </div>
                </NuxtLink>
            </li>
        </ul>
    </section>
</template>
