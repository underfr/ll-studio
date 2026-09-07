<script setup lang="ts">
/**
 * Dernière section de la page, juste avant le pied de page.
 */
</script>

<template>
    <section class="appel">
        <p class="appel__phrase">Créons quelque chose d'inoubliable.</p>

        <NuxtLink to="/contact" class="bouton bouton--or">Prendre contact</NuxtLink>
    </section>
</template>

<style scoped>
.appel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 22px;
    padding: 50px var(--gouttiere);
    border-top: 1px solid var(--bordure-default);
    background-color: var(--fond-surface-teintee);
    text-align: center;
}

@media (min-width: 900px) {
    .appel {
        gap: 26px;
        padding: 90px var(--gouttiere);
    }
}

.appel__phrase {
    font-family: var(--police-titre);
    font-size: clamp(40px, 5.5vw, 80px);
    font-style: italic;
    font-weight: 500;
    letter-spacing: -1px;
    line-height: 1;
}

@media (max-width: 599px) {
    .appel :deep(.bouton) {
        width: 100%;
        justify-content: center;
    }
}
</style>
