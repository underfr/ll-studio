/** Référencement, règles RG-IDX. */
describe('Référencement', () => {
    it('CT-E2E-15 · robots.txt exclut l\'admin, chaque page porte le suffixe du site et la langue française', () => {
        cy.request('/robots.txt').its('body').should('contain', 'Disallow: /admin')

        for (const page of ['/', '/galerie', '/albums']) {
            cy.visiter(page)
            cy.title().should('match', / · LL Studio$/)
            cy.get('html').should('have.attr', 'lang', 'fr')
        }
    })
})
