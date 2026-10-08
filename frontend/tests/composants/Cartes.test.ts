import { mountSuspended } from '@nuxt/test-utils/runtime'
import { describe, expect, it } from 'vitest'
import AppCarteAlbum from '~/components/AppCarteAlbum.vue'
import AppCartePhoto from '~/components/AppCartePhoto.vue'
import { photo, serie } from '../support/donnees'

/** Texte rendu, espaces et retours à la ligne ramenés à une seule espace. */
const texte = (html: { text: () => string }) => html.text().replace(/\s+/g, ' ').trim()

describe('AppCartePhoto', () => {
    it('CT-CF-10 · est un bouton qui annonce la photo, l\'affiche avec son alternative et demande à l\'ouvrir', async () => {
        const tuile = await mountSuspended(AppCartePhoto, { props: { photo: photo(3) } })

        const bouton = tuile.get('button')
        expect(bouton.attributes('aria-label')).toBe('Agrandir : Photographie 3')
        expect(tuile.get('img').attributes('alt')).toBe('Texte alternatif 3')
        expect(tuile.get('.tuile__categorie').text()).toBe('Spectacle')
        expect(tuile.get('.tuile__titre').text()).toBe('Photographie 3')

        await bouton.trigger('click')
        expect(tuile.emitted('ouvrir')).toHaveLength(1)
    })

    it('CT-CF-10 · se passe de légende quand on le lui demande', async () => {
        const tuile = await mountSuspended(AppCartePhoto, { props: { photo: photo(3), avecLegende: false } })

        expect(tuile.find('.tuile__legende').exists()).toBe(false)
    })

    it('CT-CF-11 · charge immédiatement une image prioritaire, les autres en différé', async () => {
        const prioritaire = await mountSuspended(AppCartePhoto, { props: { photo: photo(1), prioritaire: true } })
        const differee = await mountSuspended(AppCartePhoto, { props: { photo: photo(2) } })

        expect(prioritaire.get('img').attributes('loading')).toBe('eager')
        expect(differee.get('img').attributes('loading')).toBe('lazy')
    })
})

describe('AppCarteAlbum', () => {
    it('CT-CF-12 · mène à la page de la série et accorde le nombre de photos', async () => {
        const plusieurs = await mountSuspended(AppCarteAlbum, { props: { album: serie({ photoCount: 3 }) } })
        const une = await mountSuspended(AppCarteAlbum, { props: { album: serie({ photoCount: 1 }) } })

        expect(plusieurs.get('a').attributes('href')).toBe('/albums/puy-du-fou-2024')
        expect(texte(plusieurs)).toContain('Spectacle · 3 photos')
        expect(texte(une)).toContain('Spectacle · 1 photo')
        expect(texte(une)).not.toContain('1 photos')
    })

    it('CT-CF-12 · n\'affiche d\'image que si la série a une couverture', async () => {
        const avec = await mountSuspended(AppCarteAlbum, { props: { album: serie() } })
        const sans = await mountSuspended(AppCarteAlbum, { props: { album: serie({ coverPhoto: undefined }) } })

        expect(avec.find('img').exists()).toBe(true)
        expect(sans.find('img').exists()).toBe(false)
    })
})
