import type { Collection } from '../support/e2e'

/**
 * Visibilité, règle RG-VIS-01, vue depuis le site.
 *
 * Les photos masquées sont lues avec le compte administrateur, seul à pouvoir
 * les voir, puis cherchées sur chaque page publique.
 */
describe('Visibilité', () => {
    it('CT-E2E-11 · aucune photo masquée n\'apparaît sur une page publique', () => {
        cy.jetonAdmin().then((jeton) => {
            cy.api<Collection<{ title: string, contentUrl: string }>>('/api/photos?visible=false', jeton).then(({ member: masquees }) => {
                expect(masquees, 'le jeu de référence contient des photos masquées').to.have.length.greaterThan(0)

                cy.api<Collection<{ slug: string }>>('/api/albums').then(({ member: series }) => {
                    const pages = ['/', '/galerie', '/galerie?vue=grille', '/albums', ...series.map(serie => `/albums/${serie.slug}`)]

                    for (const page of pages) {
                        cy.visiter(page)

                        for (const photo of masquees) {
                            cy.contains(photo.title).should('not.exist')
                            cy.get(`[aria-label*="${photo.title}"]`).should('not.exist')
                            cy.get(`img[src$="${photo.contentUrl}"]`).should('not.exist')
                        }
                    }
                })
            })
        })
    })
})
