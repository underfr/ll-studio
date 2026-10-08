import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import PageErreur from '~/error.vue'

/**
 * Page d'erreur globale, règles RG-ERR-02 à RG-ERR-04.
 */
const { etat } = vi.hoisted(() => ({
    etat: {
        clearError: vi.fn(),
        entetes: [] as unknown[],
    },
}))

mockNuxtImport('clearError', () => etat.clearError)
mockNuxtImport('useHead', () => (entete: unknown) => {
    etat.entetes.push(typeof entete === 'function' ? entete() : entete)
})

beforeEach(() => {
    etat.clearError.mockReset()
    etat.entetes.length = 0
})

async function monter(error: Record<string, unknown>) {
    return mountSuspended(PageErreur, { props: { error } })
}

/** La page a-t-elle demandé à ne pas être indexée ? */
const nonIndexable = () => etat.entetes.some(entete =>
    (entete as { meta?: Array<{ name?: string, content?: string }> })?.meta
        ?.some(meta => meta.name === 'robots' && meta.content === 'noindex'))

describe('Page d\'erreur', () => {
    it('CT-CF-40 · sur une 404, affiche le message fourni par l\'application', async () => {
        const page = await monter({ statusCode: 404, statusMessage: 'Cette série n’existe pas.', data: { detail: 'Cette série n’existe pas.' } })

        expect(page.get('.section__kicker').text()).toBe('Erreur 404')
        expect(page.get('.erreur__titre').text()).toBe('Cette page n\'existe pas.')
        expect(page.get('.erreur__message').text()).toBe('Cette série n’existe pas.')
    })

    it('CT-CF-41 · sans message de l\'application, n\'affiche jamais celui de Nuxt ni l\'adresse demandée', async () => {
        const page = await monter({ statusCode: 404, statusMessage: 'Page not found: /adresse-inventee' })

        expect(page.get('.erreur__message').text()).toBe('Cette adresse ne correspond à aucune page du site.')
        expect(page.text()).not.toContain('/adresse-inventee')
        expect(page.text()).not.toContain('Page not found')
    })

    it('CT-CF-42 · sur une 500, affiche un message fixe et pas de lien vers la galerie', async () => {
        const page = await monter({ statusCode: 500, statusMessage: 'SQLSTATE[23000] détail interne', message: 'trace interne' })

        expect(page.get('.erreur__titre').text()).toBe('Quelque chose s\'est mal passé.')
        expect(page.get('.erreur__message').text()).toBe('Le serveur n\'a pas pu répondre. Vous pouvez réessayer dans un instant.')
        expect(page.text()).not.toContain('SQLSTATE')
        expect(page.text()).not.toContain('Voir la galerie')
    })

    it('CT-CF-43 · ses liens quittent réellement l\'écran d\'erreur, et la page n\'est pas indexable', async () => {
        const page = await monter({ statusCode: 404 })

        expect(nonIndexable()).toBe(true)

        await page.get('a.bouton').trigger('click')
        expect(etat.clearError).toHaveBeenLastCalledWith({ redirect: '/' })

        await page.get('a.section__report').trigger('click')
        expect(etat.clearError).toHaveBeenLastCalledWith({ redirect: '/galerie' })
    })
})
