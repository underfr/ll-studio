<script setup lang="ts">
/**
 * Gabarit du site public : en-tête collant puis contenu de la page.
 *
 * Les maquettes ne prévoient pas de pied de page — la dernière section de
 * chaque écran est un appel à l'action pleine largeur. Rien n'est donc rendu
 * après <main>.
 */
</script>

<template>
    <div class="page">
        <a class="lien-evitement" href="#contenu">Aller au contenu</a>

        <AppEnTete />

        <main id="contenu" tabindex="-1">
            <slot />
        </main>
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
