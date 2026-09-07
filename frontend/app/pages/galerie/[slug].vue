<script setup lang="ts">
/**
 * Redirection permanente des anciennes URL de séries.
 *
 * Les albums vivaient sous /galerie/{slug} avant d'avoir leur propre rubrique.
 * Cette page conserve les liens déjà partagés ou indexés.
 *
 * Une règle de route générique sur /galerie/** aurait été plus concise, mais
 * elle capture aussi /galerie lui-même et redirigeait donc la galerie de
 * photographies. Une page dynamique ne s'applique qu'aux sous-chemins.
 */
const route = useRoute()

await navigateTo(`/albums/${route.params.slug}`, { redirectCode: 301 })
</script>

<template>
    <div />
</template>
