<script setup lang="ts">
/**
 * Gabarit du site public : en-tête collant, contenu de la page, pied de page.
 */
</script>

<template>
    <div class="page flex min-h-screen flex-col">
        <a class="lien-evitement" href="#contenu">Aller au contenu</a>

        <AppEnTete />

        <!-- Le contenu reçoit le focus depuis le lien d'évitement, sans anneau :
             l'utilisateur vient de cliquer, il sait déjà où il est. -->
        <main id="contenu" class="flex-1 focus:outline-none" tabindex="-1">
            <slot />
        </main>

        <AppPiedDePage />
    </div>
</template>
