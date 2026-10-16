import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { describe, expect, it, vi } from 'vitest'

/**
 * Navigation du back-office, règle RG-ADM-10.
 */
const { route } = vi.hoisted(() => ({ route: { path: '/admin' } }))

mockNuxtImport('useRoute', () => () => route)

describe('useNavigationAdmin', () => {
    it('CT-UF-16 · cinq rubriques, dans l\'ordre prévu', () => {
        expect(useNavigationAdmin().liens.map(lien => [lien.libelle, lien.chemin])).toEqual([
            ['Tableau de bord', '/admin'],
            ['Photos', '/admin/photos'],
            ['Albums', '/admin/albums'],
            ['Catégories', '/admin/categories'],
            ['Messages', '/admin/messages'],
        ])
    })

    it.each([
        ['/admin', ['Tableau de bord']],
        ['/admin/', ['Tableau de bord']],
        ['/admin/photos', ['Photos']],
        ['/admin/photos/12', ['Photos']],
        ['/admin/photosx', []],
        ['/galerie', []],
    ])('CT-UF-16 · sur %s, rubrique active : %j', (chemin, actives) => {
        route.path = chemin
        const { liens, estActif } = useNavigationAdmin()

        expect(liens.filter(lien => estActif(lien.chemin)).map(lien => lien.libelle)).toEqual(actives)
    })
})
