<script setup lang="ts">
/**
 * Bascule entre les deux modes d'affichage de la galerie.
 *
 * Comme les filtres, le mode vit dans l'URL : un lien vers la galerie en
 * grille reste un lien partageable.
 */
export type ModeAffichage = 'mosaique' | 'grille'

defineProps<{ actif: ModeAffichage }>()

const route = useRoute()

function versMode(mode: ModeAffichage) {
    const query = { ...route.query }

    if (mode === 'grille') {
        query.vue = 'grille'
    }
    else {
        delete query.vue
    }

    return { path: '/galerie', query }
}
</script>

<template>
    <div class="bascule">
        <span class="bascule__intitule">Affichage</span>

        <div class="bascule__groupe">
            <NuxtLink
                class="bascule__option"
                :class="{ 'bascule__option--actif': actif === 'mosaique' }"
                :to="versMode('mosaique')"
                :aria-current="actif === 'mosaique' ? 'true' : undefined"
            >
                Mosaïque
            </NuxtLink>
            <NuxtLink
                class="bascule__option"
                :class="{ 'bascule__option--actif': actif === 'grille' }"
                :to="versMode('grille')"
                :aria-current="actif === 'grille' ? 'true' : undefined"
            >
                Grille
            </NuxtLink>
        </div>
    </div>
</template>

<style scoped>
.bascule {
    display: flex;
    align-items: center;
    gap: 10px;
}

.bascule__intitule {
    color: rgb(18 16 12 / 40%);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.bascule__groupe {
    display: flex;
    overflow: hidden;
    border: 1px solid rgb(18 16 12 / 18%);
    border-radius: 2px;
}

.bascule__option {
    padding: 9px 16px;
    color: var(--texte-principal);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 1px;
    transition: background-color var(--transition-courte);
}

.bascule__option:hover {
    background-color: var(--voile-leger);
}

.bascule__option--actif,
.bascule__option--actif:hover {
    background-color: var(--or-remplissage);
}
</style>
