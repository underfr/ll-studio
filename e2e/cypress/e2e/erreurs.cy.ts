/** Erreurs et redirection, règles RG-ERR et RG-SER. */
const enHtml = { Accept: 'text/html' }

describe('Erreurs', () => {
    it('CT-E2E-12 · une série inconnue répond 404 dans la charte, et le bouton ramène à l\'accueil', () => {
        cy.request({ url: '/albums/inexistant', headers: enHtml, failOnStatusCode: false }).its('status').should('eq', 404)

        cy.visiter('/albums/inexistant', { failOnStatusCode: false })
        cy.findByRole('heading', { level: 1, name: 'Cette page n\'existe pas.' }).should('exist')
        cy.findByText('Cette série n\'existe pas.').should('exist')
        cy.findByRole('navigation', { name: 'Navigation principale' }).should('exist')
        cy.findByRole('link', { name: 'Aller au contenu' }).should('exist')

        cy.findByRole('link', { name: 'Retour à l\'accueil' }).click()
        cy.location('pathname').should('eq', '/')
        cy.findByRole('heading', { level: 1, name: 'Cette page n\'existe pas.' }).should('not.exist')
    })

    it('CT-E2E-13 · une adresse inconnue répond 404 sans réafficher l\'adresse demandée', () => {
        cy.request({ url: '/adresse-inconnue', headers: enHtml, failOnStatusCode: false }).its('status').should('eq', 404)

        cy.visiter('/adresse-inconnue', { failOnStatusCode: false })
        cy.findByText('Cette adresse ne correspond à aucune page du site.').should('exist')
        cy.contains('adresse-inconnue').should('not.exist')
    })

    it('CT-E2E-14 · l\'ancienne adresse d\'une série redirige en 301 vers la nouvelle', () => {
        cy.request({ url: '/galerie/puy-du-fou-2024', headers: enHtml, followRedirect: false }).then((reponse) => {
            expect(reponse.status).to.eq(301)
            expect(reponse.redirectedToUrl).to.match(/\/albums\/puy-du-fou-2024$/)
        })
    })
})
