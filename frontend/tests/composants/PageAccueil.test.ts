import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import Accueil from '~/pages/index.vue'
import type { ParametresCollection } from '~/types/api'
import { categorie, collection, photos, serie } from '../support/donnees'

/**
 * Page d'accueil, API simulée. Règles RG-ACC.
 */
const { etat } = vi.hoisted(() => ({
    etat: {
        photos: null as unknown,
        series: null as unknown,
        categories: null as unknown,
        demandes: {} as Record<string, ParametresCollection>,
    },
}))

mockNuxtImport('usePhotos', () => (parametres: () => ParametresCollection) => {
    etat.demandes.photos = parametres()

    return { data: ref(etat.photos) }
})
mockNuxtImport('useAlbums', () => (parametres: () => ParametresCollection) => {
    etat.demandes.series = parametres()

    return { data: ref(etat.series) }
})
mockNuxtImport('useCategories', () => () => ({ data: ref(etat.categories) }))

beforeEach(() => {
    etat.demandes = {}
})

describe('Page d\'accueil', () => {
    it('CT-CF-34 · ouvre sur la photo la plus récente, montre les séries et présente la deuxième photo', async () => {
        etat.photos = collection(photos(2))
        etat.series = collection([1, 2, 3, 4].map(n => serie({ '@id': `/api/albums/${n}`, 'slug': `serie-${n}` })))
        etat.categories = collection([categorie('Spectacle'), categorie('Voiture'), categorie('Animaux')])

        const page = await mountSuspended(Accueil, { route: '/' })

        expect(etat.demandes.photos).toMatchObject({ itemsPerPage: 2 })
        expect(etat.demandes.series).toMatchObject({ itemsPerPage: 4 })
        expect(page.get('.hero__photo').attributes('src')).toContain('/photo-1.jpg')
        expect(page.findAll('a.carte')).toHaveLength(4)
        expect(page.get('.apropos__visuel img').attributes('src')).toContain('/photo-2.jpg')
        expect(page.findAll('.apropos__chiffres dd').map(chiffre => chiffre.text())).toContain('3')
        expect(page.find('.bandeau').exists()).toBe(true)
    })

    it('CT-CF-34 · sans contenu, garde un bandeau sans image et retire séries et univers', async () => {
        etat.photos = collection([])
        etat.series = collection([])
        etat.categories = collection([])

        const page = await mountSuspended(Accueil, { route: '/' })

        expect(page.find('.hero').exists()).toBe(true)
        expect(page.find('.hero__photo').exists()).toBe(false)
        expect(page.find('a.carte').exists()).toBe(false)
        expect(page.text()).not.toContain('Albums en vedette')
        expect(page.find('.bandeau').exists()).toBe(false)
    })
})
