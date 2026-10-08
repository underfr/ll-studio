import type { Collection } from '../support/e2e'

/** Page d'accueil, règles RG-ACC. */
describe('Accueil', () => {
    it('CT-E2E-01 · ouvre sur la photo la plus récente, montre les séries et mène aux bonnes pages', () => {
        cy.api<Collection<{ alt: string, contentUrl: string }>>('/api/photos?itemsPerPage=1').then(({ member: [recente] }) => {
            cy.visiter('/')
            cy.findAllByRole('img', { name: recente!.alt }).first()
                .should('have.attr', 'src').and('match', new RegExp(`${recente!.contentUrl}$`))
        })

        cy.api('/api/albums').then(({ totalItems }) => {
            cy.findByRole('heading', { name: 'Albums en vedette' }).closest('section')
                .find('a[href^="/albums/"]').should('have.length', Math.min(totalItems, 4))
        })

        const destinations: Array<[string, string]> = [
            ['Tout voir →', '/albums'],
            ['Voir la galerie', '/galerie'],
            ['Me réserver', '/contact'],
            ['Prendre contact', '/contact'],
        ]

        for (const [intitule, chemin] of destinations) {
            cy.visiter('/')
            cy.findByRole('link', { name: intitule }).click()
            cy.location('pathname').should('eq', chemin)
        }
    })
})
