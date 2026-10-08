import type { Collection } from '../support/e2e'

/** Séries côté site, règles RG-SER. */
const cartes = () => cy.get('main a[href^="/albums/"]')
const filtres = () => cy.findByRole('navigation', { name: 'Filtrer par catégorie' })

describe('Albums', () => {
    it('CT-E2E-09 · liste les séries, se filtre par catégorie et annonce l\'absence de résultat', () => {
        cy.api('/api/albums').its('totalItems').then((total) => {
            cy.visiter('/albums')
            cartes().should('have.length', total)
        })

        filtres().within(() => cy.findByRole('link', { name: 'Voiture' }).click())
        cy.location('search').should('eq', '?categorie=voiture')
        cartes().should('have.length', 1)
        cy.findByText('1 série').should('exist')

        filtres().within(() => cy.findByRole('link', { name: 'Animaux' }).click())
        cartes().should('have.length', 0)
        cy.findByText('Aucune série publiée dans cette catégorie pour le moment.').should('exist')
        cy.findByText('0 série').should('exist')
    })

    it('CT-E2E-10 · la page d\'une série montre ses seules photos publiées et ramène aux albums', () => {
        cy.api<Collection<{ photoCount: number }>>('/api/albums?slug=puy-du-fou-2024').then(({ member: [serie] }) => {
            cy.visiter('/albums')
            cartes().filter(':contains("Puy du Fou 2024")').click()

            cy.location('pathname').should('eq', '/albums/puy-du-fou-2024')
            cy.findByRole('heading', { level: 1, name: 'Puy du Fou 2024' }).should('exist')
            cy.title().should('eq', 'Puy du Fou 2024 · LL Studio')
            cy.contains(`${serie!.photoCount} photos`).should('exist')
            cy.findAllByRole('button', { name: /^Agrandir : / }).should('have.length', serie!.photoCount)
        })

        cy.findByRole('link', { name: '← Retour aux albums' }).click()
        cy.location('pathname').should('eq', '/albums')
    })
})
