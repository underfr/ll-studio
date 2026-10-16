import type { Compte } from '~/types/api'

/**
 * Session du back-office (issue #25).
 *
 * Le jeton d'accès (1 h) ne vit qu'en mémoire : il n'est écrit ni dans un
 * cookie lisible ni dans localStorage, où un script injecté pourrait le lire.
 * Il est donc perdu à chaque rechargement, et restauré par
 * /api/token/refresh à partir du cookie HttpOnly que l'API a posé à la
 * connexion et que JavaScript ne voit jamais (issue #62).
 *
 * Le back-office étant rendu côté navigateur uniquement (routeRules de
 * nuxt.config.ts), ces fonctions ne s'exécutent jamais sur le serveur Nuxt :
 * aucun jeton ne peut se retrouver dans le HTML ou le payload d'une page.
 */

type OptionsRequete = NonNullable<Parameters<typeof $fetch>[1]>

interface EtatSession {
    jeton: string | null
    compte: Compte | null
    /**
     * Vrai dès que l'API a été interrogée une fois. Sans session, la garde
     * renvoie vers la page de connexion, qui repasse par la garde : sans cet
     * indicateur, chaque visite déclencherait deux rafraîchissements voués à
     * l'échec au lieu d'un.
     */
    verifiee: boolean
}

/** Page de connexion, la seule du back-office ouverte sans session. */
const PAGE_CONNEXION = '/admin/connexion'

/**
 * Rafraîchissement en cours, partagé par tous les appelants.
 *
 * Le jeton de rafraîchissement est à usage unique : si deux requêtes
 * expirées lançaient chacune leur rafraîchissement, la seconde présenterait
 * un jeton déjà consommé, serait refusée, et fermerait la session. Les
 * appelants simultanés attendent donc la même promesse.
 */
let rafraichissementEnCours: Promise<boolean> | null = null

/**
 * Chemin où renvoyer après la connexion, à partir du paramètre de retour.
 *
 * Seuls les chemins du back-office sont acceptés. Sans ce filtre, un lien
 * piégé du type « /admin/connexion?redirection=//site-tiers.example »
 * enverrait l'administrateur, à peine connecté, vers un autre site.
 */
export function cheminDeRetour(valeur: unknown): string {
    if (typeof valeur !== 'string') {
        return '/admin'
    }

    const estDuBackOffice = valeur === '/admin'
        || valeur.startsWith('/admin/')
        || valeur.startsWith('/admin?')

    if (!estDuBackOffice || valeur.startsWith(PAGE_CONNEXION)) {
        return '/admin'
    }

    return valeur
}

/** Statut HTTP d'une erreur levée par $fetch, s'il y en a un. */
export function statutErreur(erreur: unknown): number | undefined {
    return (erreur as { statusCode?: number } | null)?.statusCode
}

export function useSession() {
    const etat = useState<EtatSession>('session', () => ({ jeton: null, compte: null, verifiee: false }))
    const api = useNuxtApp().$api as typeof $fetch

    // Capturé maintenant : après un await, useRoute et navigateTo n'ont plus
    // accès au contexte de l'application.
    const router = useRouter()

    const compte = computed(() => etat.value.compte)
    const estAdmin = computed(() => etat.value.compte?.roles?.includes('ROLE_ADMIN') ?? false)

    /**
     * Oublie la session côté navigateur, sans prévenir l'API. L'état reste
     * « vérifié » : on sait désormais qu'aucune session n'est ouverte.
     */
    function oublier(): void {
        etat.value = { jeton: null, compte: null, verifiee: true }
    }

    /**
     * Obtient un nouveau jeton d'accès à partir du cookie.
     *
     * @returns faux si le cookie est absent, expiré ou révoqué
     */
    function rafraichir(): Promise<boolean> {
        rafraichissementEnCours ??= api<{ token: string }>('/api/token/refresh', {
            method: 'POST',
            // Indispensable pour que le navigateur joigne le cookie à une
            // requête vers une autre origine que celle de la page.
            credentials: 'include',
        })
            .then(({ token }) => {
                etat.value.jeton = token

                return true
            })
            .catch(() => {
                oublier()

                return false
            })
            .finally(() => {
                rafraichissementEnCours = null
            })

        return rafraichissementEnCours
    }

    /**
     * Requête authentifiée vers l'API.
     *
     * Un 401 signifie le plus souvent que le jeton d'accès a expiré : la
     * requête est rejouée une fois après rafraîchissement. Si le
     * rafraîchissement échoue à son tour, la session est terminée et
     * l'administrateur est renvoyé vers la connexion, avec de quoi revenir
     * à la page où il était.
     */
    async function requete<T>(chemin: string, options: OptionsRequete = {}): Promise<T> {
        const appeler = () => {
            const entetes = new Headers(options.headers as HeadersInit | undefined)
            entetes.set('Authorization', `Bearer ${etat.value.jeton}`)

            return api<T>(chemin, { ...options, headers: entetes })
        }

        if (!etat.value.jeton && !(await rafraichir())) {
            return sessionExpiree()
        }

        try {
            return await appeler()
        }
        catch (erreur) {
            if (statutErreur(erreur) !== 401) {
                throw erreur
            }

            if (!(await rafraichir())) {
                return sessionExpiree()
            }

            return await appeler()
        }
    }

    async function sessionExpiree(): Promise<never> {
        oublier()

        await router.push({
            path: PAGE_CONNEXION,
            query: {
                raison: 'expiree',
                redirection: cheminDeRetour(router.currentRoute.value.fullPath),
            },
        })

        // L'appelant ne doit pas poursuivre comme si la requête avait abouti.
        throw new Error('Session expirée.')
    }

    async function chargerCompte(): Promise<void> {
        etat.value.compte = await requete<Compte>('/api/me')
    }

    /**
     * Ouvre une session. Les erreurs de l'API (401, 429...) sont propagées
     * telles quelles : c'est à l'écran de connexion de les formuler.
     */
    async function connecter(email: string, motDePasse: string): Promise<void> {
        const { token } = await api<{ token: string }>('/api/login', {
            method: 'POST',
            body: { email, password: motDePasse },
            // Sans cette option, le navigateur ignorerait le cookie du jeton
            // de rafraîchissement posé par la réponse.
            credentials: 'include',
        })

        etat.value.jeton = token
        await chargerCompte()
    }

    /**
     * Retrouve la session après un rechargement de page.
     *
     * @returns vrai si une session est ouverte, sans jamais lever d'erreur :
     *          une API injoignable équivaut ici à une absence de session
     */
    async function restaurer(): Promise<boolean> {
        if (etat.value.compte) {
            return true
        }

        if (etat.value.verifiee) {
            return false
        }

        etat.value.verifiee = true

        if (!(await rafraichir())) {
            return false
        }

        try {
            await chargerCompte()

            return true
        }
        catch {
            oublier()

            return false
        }
    }

    /**
     * Ferme la session : l'API révoque le jeton de rafraîchissement et
     * efface le cookie. La session est oubliée côté navigateur même si l'API
     * ne répond pas, pour ne jamais laisser croire qu'elle reste ouverte.
     */
    async function deconnecter(): Promise<void> {
        try {
            await api('/api/token/invalidate', { method: 'POST', credentials: 'include' })
        }
        catch {
            // Rien à signaler : sans cookie valide, la session est déjà close.
        }
        finally {
            oublier()
        }
    }

    return {
        compte,
        estAdmin,
        connecter,
        restaurer,
        deconnecter,
        requete,
    }
}
