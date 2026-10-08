import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AppFiltresCategories from '~/components/AppFiltresCategories.vue'
import GalerieBascule from '~/components/GalerieBascule.vue'
import { categorie } from '../support/donnees'

/**
 * Filtres et mode d'affichage, règles RG-GAL-02, RG-GAL-03 et RG-SER-01.
 * L'état vit dans l'URL : on vérifie donc les adresses produites par les liens.
 */
const { route } = vi.hoisted(() => ({ route: { path: '/galerie', query: {} as Record<string, string> } }))

mockNuxtImport('useRoute', () => () => route)

/** Paramètres de l'adresse d'un lien, et son chemin. */
function cible(lien: { attributes: (nom: string) => string | undefined }) {
    const url = new URL(lien.attributes('href')!, 'http://exemple.test')

    return { chemin: url.pathname, parametres: Object.fromEntries(url.searchParams) }
}

const univers = [categorie('Spectacle'), categorie('Voiture')]

beforeEach(() => {
    route.query = {}
})

describe('AppFiltresCategories', () => {
    it('CT-CF-13 · « Tout » retire le filtre, une catégorie le pose, le mode d\'affichage est conservé', async () => {
        route.query = { vue: 'grille', categorie: 'voiture' }
        const filtres = await mountSuspended(AppFiltresCategories, { props: { categories: univers, actif: 'voiture', base: '/galerie' } })
        const [tout, spectacle, voiture] = filtres.findAll('a')

        expect(cible(tout!)).toEqual({ chemin: '/galerie', parametres: { vue: 'grille' } })
        expect(cible(spectacle!)).toEqual({ chemin: '/galerie', parametres: { vue: 'grille', categorie: 'spectacle' } })
        expect(voiture!.attributes('aria-current')).toBe('true')
        expect(tout!.attributes('aria-current')).toBeUndefined()
    })

    it('CT-CF-13 · sans filtre, « Tout » est l\'entrée active', async () => {
        const filtres = await mountSuspended(AppFiltresCategories, { props: { categories: univers, actif: '', base: '/galerie' } })

        expect(filtres.findAll('a')[0]!.attributes('aria-current')).toBe('true')
    })

    it('CT-CF-14 · reboucle sur la page indiquée, ici les albums', async () => {
        route.path = '/albums'
        const filtres = await mountSuspended(AppFiltresCategories, { props: { categories: univers, actif: '', base: '/albums' } })

        for (const lien of filtres.findAll('a')) {
            expect(cible(lien).chemin).toBe('/albums')
        }
    })
})

describe('GalerieBascule', () => {
    it('CT-CF-15 · changer de mode conserve le filtre de catégorie', async () => {
        route.query = { categorie: 'voiture' }
        const bascule = await mountSuspended(GalerieBascule, { props: { actif: 'mosaique' } })
        const [mosaique, grille] = bascule.findAll('a')

        expect(cible(grille!)).toEqual({ chemin: '/galerie', parametres: { categorie: 'voiture', vue: 'grille' } })
        expect(cible(mosaique!)).toEqual({ chemin: '/galerie', parametres: { categorie: 'voiture' } })
        expect(mosaique!.attributes('aria-current')).toBe('true')
        expect(grille!.attributes('aria-current')).toBeUndefined()
    })
})
