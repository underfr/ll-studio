<script setup lang="ts">
/**
 * Écran de connexion du back-office (issue #25).
 *
 * Une carte daisyUI centrée (issue #67). La page se passe de gabarit, pour
 * ne montrer ni la navigation publique ni celle de l'administration à qui
 * n'est pas connecté.
 */
import type AppChamp from '~/components/AppChamp.vue'

definePageMeta({ layout: false })

useHead({
    title: 'Connexion',
    titleTemplate: '%s · Administration LL Studio',
    meta: [{ name: 'robots', content: 'noindex, nofollow' }],
})

const route = useRoute()
const session = useSession()

const email = ref('')
const motDePasse = ref('')
const erreurs = reactive({ email: '', motDePasse: '' })
const erreurFormulaire = ref('')
const envoiEnCours = ref(false)

const champEmail = ref<InstanceType<typeof AppChamp> | null>(null)
const champMotDePasse = ref<InstanceType<typeof AppChamp> | null>(null)

/** Raisons pour lesquelles on a pu être renvoyé ici. */
const INFORMATIONS: Record<string, string> = {
    expiree: 'Votre session a expiré. Reconnectez-vous pour reprendre où vous en étiez.',
    refuse: 'Ce compte n\'a pas accès à l\'administration.',
}

const information = computed(() => INFORMATIONS[String(route.query.raison)] ?? '')

/**
 * Message d'échec de connexion.
 *
 * Le 401 ne dit pas lequel des deux champs est faux : l'indiquer aiderait
 * surtout à deviner quelles adresses ont un compte. Le 429 reprend le
 * message de l'API, qui précise le délai d'attente.
 */
function messageEchec(erreur: unknown): string {
    switch (statutErreur(erreur)) {
        case 401:
            return 'Adresse e-mail ou mot de passe incorrect.'
        case 429:
            return (erreur as { data?: { message?: string } }).data?.message
                ?? 'Trop de tentatives de connexion. Réessayez dans quelques minutes.'
        default:
            return 'Le service est momentanément indisponible. Réessayez plus tard.'
    }
}

async function envoyer(): Promise<void> {
    erreurFormulaire.value = ''
    erreurs.email = email.value.trim() ? '' : 'Indiquez votre adresse e-mail.'
    erreurs.motDePasse = motDePasse.value ? '' : 'Indiquez votre mot de passe.'

    if (erreurs.email || erreurs.motDePasse) {
        // Le focus va au premier champ à corriger, que le lecteur d'écran
        // annonce avec son message d'erreur.
        (erreurs.email ? champEmail : champMotDePasse).value?.focus()

        return
    }

    envoiEnCours.value = true

    try {
        await session.connecter(email.value.trim(), motDePasse.value)

        if (!session.estAdmin.value) {
            await session.deconnecter()
            erreurFormulaire.value = INFORMATIONS.refuse!

            return
        }

        await navigateTo(cheminDeRetour(route.query.redirection), { replace: true })
    }
    catch (erreur) {
        erreurFormulaire.value = messageEchec(erreur)
        // L'adresse est conservée, le mot de passe est à ressaisir.
        motDePasse.value = ''
        champMotDePasse.value?.focus()
    }
    finally {
        envoiEnCours.value = false
    }
}
</script>

<template>
    <main class="connexion grid min-h-screen place-items-center bg-base-200 px-4 py-10">
        <div class="carte card w-full max-w-sm bg-base-100 shadow-sm">
            <div class="card-body gap-6">
                <div class="flex flex-col gap-3">
                    <AppLogo />
                    <p class="mt-3 font-mono text-xs uppercase tracking-[0.3em] text-secondary">
                        Administration
                    </p>
                    <h1 class="font-display text-4xl">Connexion</h1>
                </div>

                <div v-if="information && !erreurFormulaire" class="alert alert-info alert-soft text-sm" role="status">
                    {{ information }}
                </div>

                <!--
                    Zone d'alerte toujours présente dans le DOM, pour que les
                    lecteurs d'écran annoncent le message dès qu'il y apparaît.
                    Vide, elle sort du flux au lieu d'être masquée : display:
                    none la retirerait de l'arbre d'accessibilité.
                -->
                <div class="empty:absolute" role="alert">
                    <div v-if="erreurFormulaire" class="alert alert-error alert-soft text-sm">
                        {{ erreurFormulaire }}
                    </div>
                </div>

                <form class="flex flex-col gap-4" novalidate @submit.prevent="envoyer">
                    <AppChamp
                        ref="champEmail"
                        v-model="email"
                        libelle="Adresse e-mail"
                        type="email"
                        name="email"
                        autocomplete="username"
                        inputmode="email"
                        :erreur="erreurs.email"
                    />

                    <AppChamp
                        ref="champMotDePasse"
                        v-model="motDePasse"
                        libelle="Mot de passe"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        :erreur="erreurs.motDePasse"
                    />

                    <button type="submit" class="btn btn-primary btn-block mt-2" :disabled="envoiEnCours">
                        <span v-if="envoiEnCours" class="loading loading-spinner loading-sm" aria-hidden="true" />
                        {{ envoiEnCours ? 'Connexion...' : 'Se connecter' }}
                    </button>
                </form>

                <NuxtLink to="/" class="link link-hover link-secondary self-start text-sm">
                    Retour au site
                </NuxtLink>
            </div>
        </div>
    </main>
</template>
