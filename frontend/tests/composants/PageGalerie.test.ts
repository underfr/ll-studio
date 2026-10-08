import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Galerie from '~/pages/galerie/index.vue'
import type { ParametresCollection } from '~/types/api'
import { collection, photos } from '../support/donnees'

/**
 * Page galerie, API simulée. Règles RG-GAL.
 */
const { etat } = vi.hoisted(() => ({
    etat: {
        parametres: [] as Array<() => ParametresCollection>,
        reponse: null as unknown,
    },
}))

mockNuxtImport('usePhotos', () => (parametres: () => ParametresCollection) => {
    etat.parametres.push(parametres)

    return { data: computed(() => etat.reponse) }
})
mockNuxtImport('useCategories', () => () => ({ data: ref(collection([])) }))

/** Paramètres de la requête de photos, tels qu'ils sont à cet instant. */
const demande = () => etat.parametres.at(-1)!()

const montes: Array<{ unmount: () => void }> = []

beforeEach(() => {
    etat.parametres.length = 0
})

afterEach(() => {
    montes.splice(0).forEach(page => page.unmount())
})

async function monter(route = '/galerie') {
    const page = await mountSuspended(Galerie, { route })
    montes.push(page)

    return page
}

const boutonChargerPlus = (page: Awaited<ReturnType<typeof monter>>) =>
    page.findAll('button').find(bouton => bouton.text() === 'Charger plus')

describe('Page galerie', () => {
    it('CT-CF-30 · annonce le total, propose d\'en charger plus, puis demande une tranche de plus', async () => {
        etat.reponse = collection(photos(24), 30)
        const page = await monter()

        expect(page.get('.galerie__compte').text()).toBe('30 photographies')
        expect(demande().itemsPerPage).toBe(24)

        await boutonChargerPlus(page)!.trigger('click')

        expect(demande().itemsPerPage).toBe(48)
    })

    it('CT-CF-30 · ne propose plus rien quand tout est affiché', async () => {
        etat.reponse = collection(photos(24), 24)
        const page = await monter()

        expect(boutonChargerPlus(page)).toBeUndefined()
    })

    it('CT-CF-31 · changer de catégorie repart de la première tranche', async () => {
        etat.reponse = collection(photos(24), 30)
        const page = await monter()
        await boutonChargerPlus(page)!.trigger('click')
        expect(demande().itemsPerPage).toBe(48)

        await navigateTo('/galerie?categorie=voiture')
        await flushPromises()

        expect(demande()).toMatchObject({ 'itemsPerPage': 24, 'category.slug': 'voiture' })
    })

    it('CT-CF-32 · accorde le compteur au singulier et ouvre la visionneuse sur la tuile choisie', async () => {
        etat.reponse = collection(photos(1), 1)
        const page = await monter()

        expect(page.get('.galerie__compte').text()).toBe('1 photographie')

        await page.get('button.tuile').trigger('click')
        await flushPromises()

        expect(document.body.querySelector('[role="dialog"]')?.getAttribute('aria-label')).toBe('Photographie : Photographie 1')
    })
})
