<script setup lang="ts">
/**
 * Logo « LL Studio » : initiales en Cormorant, mot en Space Mono or.
 *
 * Deux tailles, 26 px sur desktop et 22 px sur mobile, choisies par la prop
 * plutôt que par une requête média interne, pour que la barre qui l'accueille
 * reste maîtresse de sa mise en page.
 *
 * Il ramène à l'accueil public par défaut. Le back-office le fait pointer
 * vers son tableau de bord, avec un libellé accessible qui le dit.
 */
withDefaults(defineProps<{
    taille?: 'desktop' | 'mobile'
    cible?: string
    libelle?: string
}>(), {
    taille: 'desktop',
    cible: '/',
    libelle: 'LL Studio, retour à l\'accueil',
})
</script>

<template>
    <NuxtLink
        :to="cible"
        class="logo flex items-baseline whitespace-nowrap"
        :class="taille === 'desktop' ? 'gap-2.5' : 'gap-2'"
        :aria-label="libelle"
    >
        <span
            class="font-display font-semibold leading-none tracking-[-0.5px] text-base-content"
            :class="taille === 'desktop' ? 'text-[26px]' : 'text-[22px]'"
        >LL</span>
        <span
            class="font-mono uppercase leading-none text-secondary"
            :class="taille === 'desktop' ? 'text-[10px] tracking-[3px]' : 'text-[8px] tracking-[2px]'"
        >Studio</span>
    </NuxtLink>
</template>
