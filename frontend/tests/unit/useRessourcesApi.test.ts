import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'

/**
 * Composables métier. L'URL réellement demandée est observée sur une doublure
 * de useApiFetch : c'est elle qui révèle la chaîne de requête construite par
 * la fonction interne, sans avoir à l'exporter.
 */
const { appels, reponse } = vi.hoisted(() => ({
    appels: [] as Array<string | (() => string)>,
    reponse: { valeur: null as unknown },
}))

mockNuxtImport('useApiFetch', () => (chemin: string | (() => string)) => {
    appels.push(chemin)

    return { data: ref(reponse.valeur) }
})

/** Dernière URL demandée, getter évalué. */
function derniereUrl(): string {
    const chemin = appels.at(-1)!

    return typeof chemin === 'function' ? chemin() : chemin
}

describe('usePhotos', () => {
    beforeEach(() => {
        appels.length = 0
    })

    it('CT-UF-07 · sans paramètre, pas de chaîne de requête', () => {
        usePhotos()

        expect(derniereUrl()).toBe('/api/photos')
    })

    it('CT-UF-07 · omet les valeurs vides et indéfinies, garde false et 0', () => {
        usePhotos(() => ({ vide: '', absent: undefined, visible: false, page: 0, itemsPerPage: 24 }))

        const url = new URL(derniereUrl(), 'http://exemple.test')
        expect(url.pathname).toBe('/api/photos')
        expect(Object.fromEntries(url.searchParams)).toEqual({ visible: 'false', page: '0', itemsPerPage: '24' })
    })

    it('CT-UF-07 · encode les valeurs, accents et points compris', () => {
        usePhotos(() => ({ 'category.slug': 'spectacle', 'title': 'été à Nogaro' }))

        const url = new URL(derniereUrl(), 'http://exemple.test')
        expect(url.searchParams.get('category.slug')).toBe('spectacle')
        expect(url.searchParams.get('title')).toBe('été à Nogaro')
        expect(derniereUrl()).not.toContain(' ')
    })
})

describe('useAlbum', () => {
    beforeEach(() => {
        appels.length = 0
    })

    it('CT-UF-08 · cherche la série par son slug et retient la première trouvée', async () => {
        const serie = { '@id': '/api/albums/5', 'slug': 'puy-du-fou-2024' }
        reponse.valeur = { member: [serie], totalItems: 1 }

        const { album } = await useAlbum(() => 'puy-du-fou-2024')

        expect(derniereUrl()).toBe('/api/albums?slug=puy-du-fou-2024')
        expect(album.value).toEqual(serie)
    })

    it('CT-UF-08 · renvoie null quand aucune série ne correspond', async () => {
        reponse.valeur = { member: [], totalItems: 0 }

        const { album } = await useAlbum(() => 'inexistante')

        expect(album.value).toBeNull()
    })
})
