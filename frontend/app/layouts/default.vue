<script setup lang="ts">
/**
 * Gabarit du site public : en-tête collant, contenu de la page, pied de page.
 *
 * Le pied de page ne figure pas dans la maquette, qui s'arrête sur un appel à
 * l'action pleine largeur ; il en reprend le registre graphique.
 */
</script>

<template>
    <div class="page">
        <a class="lien-evitement" href="#contenu">Aller au contenu</a>

        <AppEnTete />

        <main id="contenu" tabindex="-1">
            <slot />
        </main>

        <AppPiedDePage />
    </div>
</template>

<style scoped>
.page {
    display: flex;
    min-height: 100vh;
    flex-direction: column;
}

main {
    flex: 1;
}

/* Le conteneur reçoit le focus depuis le lien d'évitement, sans anneau :
   l'utilisateur vient de cliquer, il sait déjà où il est. */
main:focus {
    outline: none;
}
</style>
