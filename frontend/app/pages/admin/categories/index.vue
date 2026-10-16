<script setup lang="ts">
definePageMeta({ layout: 'admin' })

useHead({ title: 'Catégories' })
</script>

<template>
    <section>
        <AdminEnTetePage rubrique="Catégories" titre="Catégories" />

        <div class="alert" role="note">
            <svg class="size-5 shrink-0 text-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1" />
            </svg>
            <span>Écran en préparation : gestion des catégories à l'issue #28.</span>
        </div>
    </section>
</template>
