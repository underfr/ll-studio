import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it, vi } from 'vitest'
import Serie from '~/pages/albums/[slug].vue'
import { collection, photo, serie } from '../support/donnees'

/**
 * Page d'une série, API simulée. Règles RG-SER-04 et RG-SER-06.
 */
const { etat } = vi.hoisted(() => ({ etat: { album: null as unknown, photos: null as unknown } }))

mockNuxtImport('useAlbum', () => async () => ({ album: computed(() => etat.album) }))
mockNuxtImport('usePhotos', () => () => ({ data: ref(etat.photos) }))

describe('Page d\'une série', () => {
    it('CT-CF-35 · sans couverture, prend sa première photo publiée en bandeau', async () => {
        etat.album = serie({ coverPhoto: undefined })
        etat.photos = collection([photo(3), photo(4)])

        const page = await mountSuspended(Serie, { route: '/albums/puy-du-fou-2024' })

        expect(page.get('.couverture__photo').attributes('src')).toContain('/photo-3.jpg')
    })

    it('CT-CF-35 · sans photo publiée, le dit au visiteur', async () => {
        etat.album = serie({ coverPhoto: undefined, photoCount: 0 })
        etat.photos = collection([])

        const page = await mountSuspended(Serie, { route: '/albums/puy-du-fou-2024' })

        expect(page.get('.serie__vide').text()).toBe('Cette série ne contient encore aucune photographie publiée.')
        expect(page.find('.couverture__photo').exists()).toBe(false)
    })
})
