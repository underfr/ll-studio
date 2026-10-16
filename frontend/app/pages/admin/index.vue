<script setup lang="ts">
definePageMeta({ layout: 'admin' })

useHead({ title: 'Tableau de bord' })

const { compte } = useSession()
</script>

<template>
    <section class="jalon">
        <p class="jalon__rubrique">Tableau de bord</p>
        <h1 class="jalon__titre">
            Bonjour {{ compte?.firstName }}.
        </h1>
        <p class="jalon__note">
            Choisissez une rubrique dans le menu pour gérer le contenu du portfolio.
        </p>
    </section>
</template>
