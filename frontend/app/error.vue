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
        <section class="erreur">
            <p class="section__kicker">Erreur {{ error.statusCode }}</p>

            <h1 class="erreur__titre">{{ titre }}</h1>

            <p class="erreur__message">{{ message }}</p>

            <div class="erreur__actions">
                <a href="/" class="bouton bouton--or" @click.prevent="quitter('/')">
                    Retour à l'accueil
                </a>

                <a
                    v-if="estIntrouvable"
                    href="/galerie"
                    class="section__report"
                    @click.prevent="quitter('/galerie')"
                >
                    Voir la galerie
                </a>
            </div>
        </section>
    </NuxtLayout>
</template>

<style scoped>
.erreur {
    max-width: var(--largeur-max);
    margin-inline: auto;
    padding: 96px var(--gouttiere);
}

.erreur__titre {
    max-width: 16ch;
    margin-top: 16px;
    font-size: clamp(40px, 7vw, 76px);
    letter-spacing: -1px;
}

.erreur__message {
    max-width: 48ch;
    margin-top: 24px;
    color: var(--texte-secondaire);
    font-size: 16px;
}

.erreur__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px 28px;
    margin-top: 40px;
}
</style>
