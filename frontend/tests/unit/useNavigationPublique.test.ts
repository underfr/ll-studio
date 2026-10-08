import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'

/**
 * Navigation publique, règles RG-NAV-01 et RG-NAV-02.
 */
const { route } = vi.hoisted(() => ({ route: { path: '/' } }))

mockNuxtImport('useRoute', () => () => route)

describe('useNavigationPublique', () => {
    beforeEach(() => {
        route.path = '/'
    })

    it('CT-UF-06 · propose quatre entrées, dans l\'ordre de la maquette', () => {
        expect(useNavigationPublique().liens).toEqual([
            { libelle: 'Accueil', chemin: '/' },
            { libelle: 'Galerie', chemin: '/galerie' },
            { libelle: 'Albums', chemin: '/albums' },
            { libelle: 'Contact', chemin: '/contact' },
        ])
    })

    it.each([
        ['/', ['/']],
        ['/galerie', ['/galerie']],
        ['/albums', ['/albums']],
        ['/albums/puy-du-fou-2024', ['/albums']],
        ['/albumsx', []],
        ['/contact', ['/contact']],
    ])('CT-UF-05 · sur %s, l\'entrée active est %j', (chemin, actives) => {
        route.path = chemin
        const { liens, estActif } = useNavigationPublique()

        expect(liens.filter(lien => estActif(lien.chemin)).map(lien => lien.chemin)).toEqual(actives)
    })
})
