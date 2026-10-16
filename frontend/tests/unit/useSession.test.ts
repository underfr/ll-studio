import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'

/**
 * Session du back-office, règles RG-ADM-03 et RG-ADM-06 à RG-ADM-09.
 *
 * $api est doublé sur l'application Nuxt elle-même : useState reste le vrai,
 * si bien que l'état observé est celui que verraient les pages. Le routeur
 * est doublé pour observer le renvoi vers la connexion.
 */
const { api, routeur } = vi.hoisted(() => ({
    api: vi.fn(),
    routeur: {
        push: vi.fn(),
        currentRoute: { value: { fullPath: '/admin/photos?page=2' } },
    },
}))

mockNuxtImport('useNuxtApp', original => () => {
    const app = original()

    return new Proxy(app, { get: (cible, cle) => (cle === '$api' ? api : Reflect.get(cible, cle)) })
})

// Le vrai routeur reste en place pour Nuxt : seuls push et la route courante
// sont remplacés.
mockNuxtImport('useRouter', original => () => {
    const vrai = original()

    return new Proxy(vrai, {
        get: (cible, cle) => (cle in routeur ? routeur[cle as keyof typeof routeur] : Reflect.get(cible, cle)),
    })
})

const ADMIN = { '@id': '/api/users/1', 'firstName': 'Studio', 'fullName': 'Studio Admin', 'roles': ['ROLE_ADMIN', 'ROLE_USER'] }

/** Erreur telle que la lève $fetch, statut compris. */
function erreurHttp(statusCode: number, data: unknown = {}) {
    return Object.assign(new Error(`HTTP ${statusCode}`), { statusCode, data })
}

/** Appels reçus par $api, réduits au chemin et aux options utiles. */
function appels() {
    return api.mock.calls.map(([chemin, options]) => ({
        chemin,
        methode: options?.method ?? 'GET',
        credentials: options?.credentials,
        autorisation: options?.headers instanceof Headers ? options.headers.get('Authorization') : undefined,
    }))
}

beforeEach(() => {
    api.mockReset()
    routeur.push.mockReset()
    clearNuxtState('session')
    localStorage.clear()
    sessionStorage.clear()
})

describe('cheminDeRetour', () => {
    it.each([
        ['/admin', '/admin'],
        ['/admin/photos', '/admin/photos'],
        ['/admin/photos/12?vue=liste', '/admin/photos/12?vue=liste'],
        ['/admin?onglet=1', '/admin?onglet=1'],
    ])('CT-UF-09 · suit un chemin du back-office : %s', (valeur, attendu) => {
        expect(cheminDeRetour(valeur)).toBe(attendu)
    })

    it.each([
        ['absent', undefined],
        ['un nombre', 42],
        ['une adresse sans schéma', '//site-tiers.example'],
        ['une adresse absolue', 'https://site-tiers.example/admin'],
        ['un chemin public', '/galerie'],
        ['un chemin qui ne fait que commencer par admin', '/administrateur'],
        ['la page de connexion', '/admin/connexion'],
        ['la page de connexion avec un retour', '/admin/connexion?redirection=/admin/photos'],
    ])('CT-UF-09 · ramène au tableau de bord pour %s', (_cas, valeur) => {
        expect(cheminDeRetour(valeur)).toBe('/admin')
    })
})

describe('useSession', () => {
    it('CT-UF-10 · la connexion passe le cookie, puis lit le compte avec le jeton, sans rien stocker', async () => {
        api.mockResolvedValueOnce({ token: 'jeton-1' }).mockResolvedValueOnce(ADMIN)
        const session = useSession()

        await session.connecter('admin@ll-studio.test', 'secret')

        expect(appels()).toEqual([
            { chemin: '/api/login', methode: 'POST', credentials: 'include', autorisation: undefined },
            { chemin: '/api/me', methode: 'GET', credentials: undefined, autorisation: 'Bearer jeton-1' },
        ])
        expect(api.mock.calls[0]![1].body).toEqual({ email: 'admin@ll-studio.test', password: 'secret' })
        expect(session.compte.value?.fullName).toBe('Studio Admin')
        expect(session.estAdmin.value).toBe(true)
        expect(localStorage.length).toBe(0)
        expect(sessionStorage.length).toBe(0)
        expect(document.cookie).not.toContain('jeton-1')
    })

    it('CT-UF-11 · une requête refusée en 401 est rejouée une fois avec le jeton renouvelé', async () => {
        api.mockResolvedValueOnce({ token: 'ancien' }).mockResolvedValueOnce(ADMIN)
        const session = useSession()
        await session.connecter('admin@ll-studio.test', 'secret')
        api.mockReset()

        api.mockRejectedValueOnce(erreurHttp(401))
            .mockResolvedValueOnce({ token: 'nouveau' })
            .mockResolvedValueOnce({ totalItems: 3 })

        const resultat = await session.requete('/api/photos')

        expect(resultat).toEqual({ totalItems: 3 })
        expect(appels()).toEqual([
            { chemin: '/api/photos', methode: 'GET', credentials: undefined, autorisation: 'Bearer ancien' },
            { chemin: '/api/token/refresh', methode: 'POST', credentials: 'include', autorisation: undefined },
            { chemin: '/api/photos', methode: 'GET', credentials: undefined, autorisation: 'Bearer nouveau' },
        ])
    })

    it('CT-UF-11 · une autre erreur que 401 est transmise sans rafraîchir', async () => {
        api.mockResolvedValueOnce({ token: 'jeton' }).mockResolvedValueOnce(ADMIN)
        const session = useSession()
        await session.connecter('admin@ll-studio.test', 'secret')
        api.mockReset()
        api.mockRejectedValueOnce(erreurHttp(422))

        await expect(session.requete('/api/photos')).rejects.toMatchObject({ statusCode: 422 })
        expect(appels().map(appel => appel.chemin)).toEqual(['/api/photos'])
    })

    it('CT-UF-12 · si le rafraîchissement échoue, la session est oubliée et l\'on revient à la connexion', async () => {
        api.mockResolvedValueOnce({ token: 'jeton' }).mockResolvedValueOnce(ADMIN)
        const session = useSession()
        await session.connecter('admin@ll-studio.test', 'secret')
        api.mockReset()
        api.mockRejectedValueOnce(erreurHttp(401)).mockRejectedValueOnce(erreurHttp(401))

        await expect(session.requete('/api/photos')).rejects.toThrow('Session expirée.')

        expect(session.compte.value).toBeNull()
        expect(routeur.push).toHaveBeenCalledWith({
            path: '/admin/connexion',
            query: { raison: 'expiree', redirection: '/admin/photos?page=2' },
        })
    })

    it('CT-UF-13 · des requêtes simultanées ne déclenchent qu\'un rafraîchissement', async () => {
        const session = useSession()
        let liberer!: (valeur: { token: string }) => void
        api.mockImplementation((chemin: string) => chemin === '/api/token/refresh'
            ? new Promise((resolve) => { liberer = resolve })
            : Promise.resolve({ chemin }))

        const requetes = Promise.all([
            session.requete('/api/photos'),
            session.requete('/api/albums'),
            session.requete('/api/categories'),
        ])
        await Promise.resolve()
        liberer({ token: 'partage' })
        await requetes

        const chemins = appels().map(appel => appel.chemin)
        expect(chemins.filter(chemin => chemin === '/api/token/refresh')).toHaveLength(1)
        expect(appels().filter(appel => appel.chemin !== '/api/token/refresh').map(appel => appel.autorisation))
            .toEqual(['Bearer partage', 'Bearer partage', 'Bearer partage'])
    })

    it('CT-UF-14 · la restauration rouvre la session par le cookie, puis ne réinterroge plus l\'API', async () => {
        api.mockResolvedValueOnce({ token: 'restaure' }).mockResolvedValueOnce(ADMIN)
        const session = useSession()

        expect(await session.restaurer()).toBe(true)
        expect(await session.restaurer()).toBe(true)

        expect(appels().map(appel => appel.chemin)).toEqual(['/api/token/refresh', '/api/me'])
        expect(session.compte.value?.firstName).toBe('Studio')
    })

    it('CT-UF-14 · sans session, l\'API n\'est interrogée qu\'une fois', async () => {
        api.mockRejectedValue(erreurHttp(401))
        const session = useSession()

        expect(await session.restaurer()).toBe(false)
        expect(await session.restaurer()).toBe(false)

        expect(appels().map(appel => appel.chemin)).toEqual(['/api/token/refresh'])
    })

    it('CT-UF-14 · une API injoignable équivaut à une absence de session, sans erreur levée', async () => {
        api.mockResolvedValueOnce({ token: 'jeton' }).mockRejectedValueOnce(new TypeError('Failed to fetch'))
        const session = useSession()

        expect(await session.restaurer()).toBe(false)
        expect(session.compte.value).toBeNull()
    })

    it('CT-UF-15 · la déconnexion révoque la session côté API, et l\'oublie même si l\'API échoue', async () => {
        api.mockResolvedValueOnce({ token: 'jeton' }).mockResolvedValueOnce(ADMIN)
        const session = useSession()
        await session.connecter('admin@ll-studio.test', 'secret')
        api.mockReset()
        api.mockRejectedValueOnce(new TypeError('Failed to fetch'))

        await session.deconnecter()

        expect(appels()).toEqual([
            { chemin: '/api/token/invalidate', methode: 'POST', credentials: 'include', autorisation: undefined },
        ])
        expect(session.compte.value).toBeNull()
        expect(session.estAdmin.value).toBe(false)
    })
})
