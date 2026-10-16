import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import PageConnexion from '~/pages/admin/connexion.vue'

/**
 * Écran de connexion, règles RG-ADM-02 à RG-ADM-04 et RG-ADM-07.
 *
 * La session est doublée : l'écran est jugé sur ce qu'il affiche et sur ce
 * qu'il demande à la session, selon la réponse qu'elle lui fait.
 */
const { session, navigation } = vi.hoisted(() => ({
    session: {
        connecter: vi.fn(),
        deconnecter: vi.fn(),
        // Appelée par la garde globale quand le test fixe la route.
        restaurer: vi.fn(),
        estAdmin: { value: true },
    },
    navigation: vi.fn(),
}))

mockNuxtImport('useSession', () => () => session)
mockNuxtImport('navigateTo', () => navigation)

const montes: Array<{ unmount: () => void }> = []

beforeEach(() => {
    session.connecter.mockReset()
    session.deconnecter.mockReset()
    session.restaurer.mockReset().mockResolvedValue(false)
    session.estAdmin.value = true
    navigation.mockReset()
})

afterEach(() => {
    montes.splice(0).forEach(enveloppe => enveloppe.unmount())
})

async function page(route = '/admin/connexion') {
    const enveloppe = await mountSuspended(PageConnexion, { route, attachTo: document.body })
    montes.push(enveloppe)

    return enveloppe
}

async function soumettre(enveloppe: Awaited<ReturnType<typeof page>>, email: string, motDePasse: string) {
    await enveloppe.get('input[name="email"]').setValue(email)
    await enveloppe.get('input[name="password"]').setValue(motDePasse)
    await enveloppe.get('form').trigger('submit')
    await flushPromises()
}

const alerte = (enveloppe: Awaited<ReturnType<typeof page>>) => enveloppe.get('[role="alert"]').text()

function erreurHttp(statusCode: number, data: unknown = {}) {
    return Object.assign(new Error(`HTTP ${statusCode}`), { statusCode, data })
}

describe('Écran de connexion', () => {
    it('CT-CF-51 · un envoi incomplet signale chaque champ et place le focus sur le premier', async () => {
        const enveloppe = await page()

        await soumettre(enveloppe, '   ', '')

        expect(enveloppe.findAll('.champ__erreur').map(message => message.text())).toEqual([
            'Indiquez votre adresse e-mail.',
            'Indiquez votre mot de passe.',
        ])
        expect(document.activeElement?.getAttribute('name')).toBe('email')
        expect(session.connecter).not.toHaveBeenCalled()
    })

    it('CT-CF-51 · les champs se prêtent aux gestionnaires de mots de passe', async () => {
        const enveloppe = await page()

        expect(enveloppe.get('input[name="email"]').attributes('autocomplete')).toBe('username')
        expect(enveloppe.get('input[name="password"]').attributes('autocomplete')).toBe('current-password')
        expect(enveloppe.get('input[name="password"]').attributes('type')).toBe('password')
    })

    it('CT-CF-52 · identifiants refusés : message neutre, adresse conservée, mot de passe à ressaisir', async () => {
        session.connecter.mockRejectedValue(erreurHttp(401, { code: 401, message: 'Identifiants invalides.' }))
        const enveloppe = await page()

        await soumettre(enveloppe, 'admin@ll-studio.test', 'mauvais')

        expect(session.connecter).toHaveBeenCalledWith('admin@ll-studio.test', 'mauvais')
        expect(alerte(enveloppe)).toBe('Adresse e-mail ou mot de passe incorrect.')
        expect((enveloppe.get('input[name="email"]').element as HTMLInputElement).value).toBe('admin@ll-studio.test')
        expect((enveloppe.get('input[name="password"]').element as HTMLInputElement).value).toBe('')
        expect(document.activeElement?.getAttribute('name')).toBe('password')
        expect(navigation).not.toHaveBeenCalled()
    })

    it.each([
        [429, { message: 'Trop de tentatives de connexion échouées, veuillez réessayer dans 15 minutes.' }, 'Trop de tentatives de connexion échouées, veuillez réessayer dans 15 minutes.'],
        [429, {}, 'Trop de tentatives de connexion. Réessayez dans quelques minutes.'],
        [500, {}, 'Le service est momentanément indisponible. Réessayez plus tard.'],
    ])('CT-CF-52 · une réponse %i affiche le message prévu', async (statut, donnees, attendu) => {
        session.connecter.mockRejectedValue(erreurHttp(statut, donnees))
        const enveloppe = await page()

        await soumettre(enveloppe, 'admin@ll-studio.test', 'secret')

        expect(alerte(enveloppe)).toBe(attendu)
    })

    it.each([
        ['/admin/connexion?redirection=/admin/albums', '/admin/albums'],
        ['/admin/connexion?redirection=//site-tiers.example', '/admin'],
    ])('CT-CF-53 · un administrateur connecté depuis %s rejoint %s', async (route, attendu) => {
        session.connecter.mockResolvedValue(undefined)
        const enveloppe = await page(route)

        await soumettre(enveloppe, 'admin@ll-studio.test', 'secret')

        expect(navigation).toHaveBeenCalledWith(attendu, { replace: true })
    })

    it('CT-CF-53 · un compte sans ROLE_ADMIN est refusé et sa session aussitôt fermée', async () => {
        session.connecter.mockResolvedValue(undefined)
        session.estAdmin.value = false
        const enveloppe = await page()

        await soumettre(enveloppe, 'lecteur@ll-studio.test', 'secret')

        expect(session.deconnecter).toHaveBeenCalledOnce()
        expect(alerte(enveloppe)).toBe('Ce compte n\'a pas accès à l\'administration.')
        expect(navigation).not.toHaveBeenCalled()
    })

    it('CT-CF-54 · renvoyé pour session expirée, l\'écran en donne la raison', async () => {
        const enveloppe = await page('/admin/connexion?raison=expiree&redirection=/admin/photos')

        expect(enveloppe.get('[role="status"]').text())
            .toBe('Votre session a expiré. Reconnectez-vous pour reprendre où vous en étiez.')
        expect(alerte(enveloppe)).toBe('')
    })
})
