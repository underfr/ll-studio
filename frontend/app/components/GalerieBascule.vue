<script setup lang="ts">
/**
 * Bascule entre les deux modes d'affichage de la galerie, sur le « join » de
 * daisyUI.
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
    <div class="bascule flex items-center gap-2.5">
        <span id="intitule-affichage" class="font-mono text-[10px] uppercase tracking-[2px] text-base-content/60">Affichage</span>

        <div class="join" role="group" aria-labelledby="intitule-affichage">
            <NuxtLink
                class="btn btn-sm join-item font-mono"
                :class="actif === 'mosaique' ? 'btn-primary' : 'btn-outline'"
                :to="versMode('mosaique')"
                :aria-current="actif === 'mosaique' ? 'true' : undefined"
            >
                Mosaïque
            </NuxtLink>
            <NuxtLink
                class="btn btn-sm join-item font-mono"
                :class="actif === 'grille' ? 'btn-primary' : 'btn-outline'"
                :to="versMode('grille')"
                :aria-current="actif === 'grille' ? 'true' : undefined"
            >
                Grille
            </NuxtLink>
        </div>
    </div>
</template>
