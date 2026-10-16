<script setup lang="ts">
/**
 * Logo « LL Studio ».
 *
 * La maquette le décline en deux tailles : 26 px sur desktop, 22 px sur
 * mobile. La variante est pilotée par une classe plutôt que par une media
 * query interne, pour que la navbar reste maîtresse de sa mise en page.
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
    <NuxtLink :to="cible" class="logo" :class="`logo--${taille}`" :aria-label="libelle">
        <span class="logo__initiales">LL</span>
        <span class="logo__mot">Studio</span>
    </NuxtLink>
</template>

<style scoped>
.logo {
    display: flex;
    align-items: baseline;
    white-space: nowrap;
}

.logo--desktop {
    gap: 10px;
}

.logo--mobile {
    gap: 8px;
}

.logo__initiales {
    color: var(--texte-principal);
    font-family: var(--police-titre);
    font-weight: 600;
    letter-spacing: -0.5px;
    line-height: 1;
}

.logo--desktop .logo__initiales {
    font-size: 26px;
}

.logo--mobile .logo__initiales {
    font-size: 22px;
}

.logo__mot {
    color: var(--or-texte);
    font-family: var(--police-ui);
    line-height: 1;
    text-transform: uppercase;
}

.logo--desktop .logo__mot {
    font-size: 10px;
    letter-spacing: 3px;
}

.logo--mobile .logo__mot {
    font-size: 8px;
    letter-spacing: 2px;
}
</style>
