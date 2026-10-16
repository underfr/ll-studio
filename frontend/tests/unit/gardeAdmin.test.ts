import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { RouteLocationNormalized } from 'vue-router'
import garde from '~/middleware/admin.global'

/**
 * Garde du back-office, règles RG-ADM-01 à RG-ADM-03 et RG-ADM-05.
 *
 * La session est doublée : ce qui est vérifié ici, c'est la décision prise
 * par la garde selon l'état de la session, pas la session elle-même.
 */
const { session, navigation } = vi.hoisted(() => ({
    session: {
        restaurer: vi.fn(),
        deconnecter: vi.fn(),
        estAdmin: { value: false },
    },
    navigation: vi.fn(),
}))

mockNuxtImport('useSession', () => () => session)
mockNuxtImport('navigateTo', () => navigation)

function route(chemin: string): RouteLocationNormalized {
    const url = new URL(chemin, 'http://exemple.test')

    return {
        path: url.pathname,
        fullPath: chemin,
        query: Object.fromEntries(url.searchParams),
    } as unknown as RouteLocationNormalized
}

/** Exécute la garde vers la cible donnée, depuis l'accueil. */
function proteger(cible: string) {
    return garde(route(cible), route('/'))
}

function etatSession(connecte: boolean, admin = connecte) {
    session.restaurer.mockResolvedValue(connecte)
    session.estAdmin.value = admin
}

beforeEach(() => {
    session.restaurer.mockReset()
    session.deconnecter.mockReset()
    navigation.mockReset()
})

describe('Garde du back-office', () => {
    it('CT-UF-17 · ne s\'occupe pas des pages publiques', async () => {
        await proteger('/galerie')
        await proteger('/administrateur')

        expect(session.restaurer).not.toHaveBeenCalled()
        expect(navigation).not.toHaveBeenCalled()
    })

    it('CT-UF-17 · sans session, renvoie vers la connexion avec la page demandée', async () => {
        etatSession(false)

        await proteger('/admin/photos?page=2')

        expect(navigation).toHaveBeenCalledWith({ path: '/admin/connexion', query: { redirection: '/admin/photos?page=2' } })
    })

    it('CT-UF-17 · laisse passer un administrateur connecté', async () => {
        etatSession(true)

        expect(await proteger('/admin/photos')).toBeUndefined()
        expect(navigation).not.toHaveBeenCalled()
    })

    it('CT-UF-17 · ferme la session d\'un compte sans ROLE_ADMIN et le signale', async () => {
        etatSession(true, false)

        await proteger('/admin')

        expect(session.deconnecter).toHaveBeenCalledOnce()
        expect(navigation).toHaveBeenCalledWith({ path: '/admin/connexion', query: { raison: 'refuse' } })
    })

    it('CT-UF-17 · laisse la page de connexion ouverte sans session', async () => {
        etatSession(false)

        expect(await proteger('/admin/connexion')).toBeUndefined()
        expect(navigation).not.toHaveBeenCalled()
    })

    it.each([
        ['/admin/connexion?redirection=/admin/albums', '/admin/albums'],
        ['/admin/connexion?redirection=//site-tiers.example', '/admin'],
        ['/admin/connexion', '/admin'],
    ])('CT-UF-17 · déjà connecté, %s renvoie vers %s', async (cible, attendu) => {
        etatSession(true)

        await proteger(cible)

        expect(navigation).toHaveBeenCalledWith(attendu, { replace: true })
    })
})
