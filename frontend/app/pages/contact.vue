<script setup lang="ts">
useHead({ title: 'Contact' })
</script>

<template>
    <section class="mx-auto max-w-360 px-5.5 py-24 bureau:px-10">
        <p class="surtitre">Contact</p>
        <h1 class="mt-4 max-w-[18ch] font-display text-[clamp(40px,7vw,76px)]">Parlons de votre projet.</h1>

        <div class="alert mt-8 max-w-2xl" role="note">
            <svg class="size-5 shrink-0 text-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1" />
            </svg>
            <span>Le formulaire de contact arrive avec l'issue #41.</span>
        </div>
    </section>
</template>
