<script setup lang="ts">
/**
 * Marque Instagram, tracée en filaire pour rester dans le registre graphique
 * du site : traits fins, pas d'aplat. Elle hérite de la couleur du texte
 * environnant via currentColor.
 *
 * L'icône est purement décorative : le libellé qui l'accompagne porte déjà
 * l'information, elle est donc masquée aux lecteurs d'écran.
 */
withDefaults(defineProps<{ taille?: number }>(), { taille: 18 })
</script>

<template>
    <svg
        :width="taille"
        :height="taille"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        <rect x="3" y="3" width="18" height="18" rx="5" />
        <circle cx="12" cy="12" r="4" />
        <circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none" />
    </svg>
</template>
