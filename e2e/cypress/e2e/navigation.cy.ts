/** Navigation publique, règles RG-NAV. */
describe('Navigation', () => {
    it('CT-E2E-02 · chaque rubrique de l\'en-tête mène à sa page et y devient active', () => {
        const rubriques: Array<[string, string]> = [
            ['Galerie', '/galerie'],
            ['Albums', '/albums'],
            ['Contact', '/contact'],
            ['Accueil', '/'],
        ]

        cy.visiter('/')

        for (const [rubrique, chemin] of rubriques) {
            cy.findByRole('navigation', { name: 'Navigation principale' }).within(() => {
                cy.findByRole('link', { name: rubrique }).click()
            })
            cy.location('pathname').should('eq', chemin)
            cy.findByRole('navigation', { name: 'Navigation principale' }).within(() => {
                cy.findByRole('link', { name: rubrique }).should('have.attr', 'aria-current', 'page')
            })
        }
    })

    it('CT-E2E-03 · sur mobile, le menu mène à une rubrique et se ferme au clavier en rendant le focus', () => {
        cy.viewport(375, 812)
        cy.visiter('/')

        cy.findByRole('button', { name: 'Ouvrir le menu' }).click()
        cy.findByRole('dialog', { name: 'Menu de navigation' }).within(() => {
            cy.findByRole('link', { name: 'Albums' }).click()
        })
        cy.location('pathname').should('eq', '/albums')
        cy.findByRole('dialog', { name: 'Menu de navigation' }).should('not.exist')

        cy.findByRole('button', { name: 'Ouvrir le menu' }).click()
        cy.findByRole('dialog', { name: 'Menu de navigation' }).should('exist')
        // La page ne défile pas pendant l'ouverture : depuis #68, c'est la
        // feuille de daisyUI qui le fait, d'où une vérification ici.
        cy.get('html').should('have.css', 'overflow', 'hidden')
        cy.realPress('Escape')
        cy.findByRole('dialog', { name: 'Menu de navigation' }).should('not.exist')
        cy.focused().should('have.attr', 'aria-label', 'Ouvrir le menu')
        cy.get('html').should('not.have.css', 'overflow', 'hidden')
    })

    it('CT-E2E-04 · le premier arrêt du clavier est le lien d\'évitement, qui mène au contenu', () => {
        cy.visiter('/')
        cy.document().then(document => (document.activeElement as HTMLElement | null)?.blur())

        cy.realPress('Tab')
        cy.focused().should('have.text', 'Aller au contenu').and('be.visible')

        cy.realPress('Enter')
        cy.focused().should('have.attr', 'id', 'contenu')
    })
})
