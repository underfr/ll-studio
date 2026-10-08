import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it } from 'vitest'
import Albums from '~/pages/albums/index.vue'
import { collection } from '../support/donnees'

/**
 * Page des séries, API simulée. Règle RG-SER-01.
 */
mockNuxtImport('useAlbums', () => () => ({ data: ref(collection([])) }))
mockNuxtImport('useCategories', () => () => ({ data: ref(collection([])) }))

describe('Page albums', () => {
    it('CT-CF-33 · sans aucune série, affiche l\'état vide et un compteur à zéro', async () => {
        const page = await mountSuspended(Albums, { route: '/albums?categorie=animaux' })

        expect(page.get('.albums__compte').text()).toBe('0 série')
        expect(page.get('.albums__vide').text()).toBe('Aucune série publiée dans cette catégorie pour le moment.')
        expect(page.find('.albums__grille').exists()).toBe(false)
    })
})
