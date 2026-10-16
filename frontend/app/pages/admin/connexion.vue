<script setup lang="ts">
/**
 * Écran de connexion du back-office (issue #25).
 *
 * Le Figma ne contient pas cet écran : il assemble les composants du design
 * system, champ « Input » et bouton primaire, sur une carte centrée. La page
 * se passe de gabarit, pour ne montrer ni la navigation publique ni celle de
 * l'administration à qui n'est pas connecté.
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
    <main class="connexion">
        <div class="carte">
            <div class="carte__tete">
                <AppLogo />
                <p class="carte__rubrique">Administration</p>
                <h1 class="carte__titre">Connexion</h1>
            </div>

            <p v-if="information && !erreurFormulaire" class="carte__information" role="status">
                {{ information }}
            </p>

            <!--
                Toujours présente dans le DOM pour que les lecteurs d'écran
                annoncent le message dès qu'il y est inscrit.
            -->
            <p class="carte__erreur" role="alert">
                {{ erreurFormulaire }}
            </p>

            <form class="formulaire" novalidate @submit.prevent="envoyer">
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

                <button type="submit" class="bouton bouton--or formulaire__envoi" :disabled="envoiEnCours">
                    {{ envoiEnCours ? 'Connexion...' : 'Se connecter' }}
                </button>
            </form>

            <NuxtLink to="/" class="carte__retour">
                Retour au site
            </NuxtLink>
        </div>
    </main>
</template>

<style scoped>
.connexion {
    display: grid;
    min-height: 100vh;
    place-items: center;
    padding: 40px 16px;
}

.carte {
    display: flex;
    width: 100%;
    max-width: 400px;
    flex-direction: column;
    gap: 24px;
    padding: 40px 32px;
    border: 1px solid var(--bordure-default);
    border-radius: 6px;
    background-color: var(--fond-surface-teintee);
}

@media (max-width: 420px) {
    .carte {
        padding: 32px 22px;
    }
}

.carte__tete {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.carte__rubrique {
    margin-top: 14px;
    color: var(--or-texte);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.carte__titre {
    font-size: 40px;
}

.carte__information {
    color: var(--texte-secondaire);
    font-size: 14px;
    line-height: 1.5;
}

.carte__erreur {
    color: var(--terracotta);
    font-size: 14px;
    line-height: 1.5;
}

/*
 * Vide, la zone sort du flux pour ne pas creuser d'espace dans la carte.
 * Elle n'est pas masquée par display: none, qui la retirerait de l'arbre
 * d'accessibilité : certains lecteurs d'écran n'annonceraient plus le
 * message à son apparition.
 */
.carte__erreur:empty {
    position: absolute;
}

.formulaire {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.formulaire__envoi {
    justify-content: center;
    margin-top: 6px;
    border: 0;
    cursor: pointer;
}

.formulaire__envoi:disabled {
    cursor: progress;
    opacity: 0.7;
}

.carte__retour {
    align-self: flex-start;
    color: var(--or-texte);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: color var(--transition-courte);
}

.carte__retour:hover {
    color: var(--texte-principal);
}
</style>
