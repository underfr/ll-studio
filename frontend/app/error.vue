<script setup lang="ts">
import type { NuxtError } from '#app'

/**
 * Écran d'erreur du site public.
 *
 * Nuxt rend ce composant à la place d'app.vue : sans <NuxtLayout> explicite,
 * la page perdrait l'en-tête, le pied de page et le lien d'évitement, et
 * sortirait de la charte au moment précis où le visiteur est déjà perdu.
 */
const props = defineProps<{ error: NuxtError }>()

const estIntrouvable = computed(() => props.error.statusCode === 404)

const titre = computed(() => estIntrouvable.value
    ? "Cette page n'existe pas."
    : "Quelque chose s'est mal passé.")

/*
 * Le texte affiché vient de `data.detail`, que nos propres createError
 * renseignent. Le statusMessage de Nuxt n'est volontairement pas utilisé :
 * sur une route inconnue il vaut « Page not found: /le-chemin-demandé »,
 * en anglais et en renvoyant au visiteur ce qu'il a tapé. Tout le reste
 * retombe sur une phrase fixe, pour ne jamais exposer d'interne.
 */
const message = computed(() => {
    const detail = (props.error.data as { detail?: string } | undefined)?.detail

    if (detail) {
        return detail
    }

    return estIntrouvable.value
        ? "Cette adresse ne correspond à aucune page du site."
        : "Le serveur n'a pas pu répondre. Vous pouvez réessayer dans un instant."
})

useHead(() => ({
    title: estIntrouvable.value ? 'Page introuvable' : 'Erreur',
    // Une page d'erreur n'a rien à faire dans un index de moteur de recherche.
    meta: [{ name: 'robots', content: 'noindex' }],
}))

/*
 * clearError sort de l'état d'erreur avant de naviguer. Des ancres simples et
 * non des <NuxtLink> : le gestionnaire de clic interne du composant navigue de
 * son côté et laisse l'écran d'erreur monté, l'adresse changeait sans que la
 * page change. L'attribut href reste réel, donc le lien s'ouvre dans un nouvel
 * onglet et s'annonce correctement aux technologies d'assistance.
 */
async function quitter(chemin: string): Promise<void> {
    await clearError({ redirect: chemin })
}
</script>

<template>
    <NuxtLayout>
        <section class="erreur mx-auto max-w-360 px-5.5 py-24 bureau:px-10">
            <p class="section__kicker surtitre">Erreur {{ error.statusCode }}</p>

            <h1 class="erreur__titre mt-4 max-w-[16ch] font-display text-[clamp(40px,7vw,76px)] tracking-[-1px]">{{ titre }}</h1>

            <p class="erreur__message mt-6 max-w-[48ch] text-base-content/70">{{ message }}</p>

            <div class="mt-10 flex flex-wrap items-center gap-x-7 gap-y-4">
                <a href="/" class="bouton btn btn-primary" @click.prevent="quitter('/')">
                    Retour à l'accueil
                </a>

                <a
                    v-if="estIntrouvable"
                    href="/galerie"
                    class="section__report link link-hover link-secondary font-mono text-xs uppercase tracking-wider"
                    @click.prevent="quitter('/galerie')"
                >
                    Voir la galerie
                </a>
            </div>
        </section>
    </NuxtLayout>
</template>
