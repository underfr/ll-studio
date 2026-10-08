/** Galerie et visionneuse, règles RG-GAL et RG-LBX. */
const tuiles = () => cy.findAllByRole('button', { name: /^Agrandir : / })
const filtres = () => cy.findByRole('navigation', { name: 'Filtrer par catégorie' })

describe('Galerie', () => {
    it('CT-E2E-05 · affiche toutes les photos publiées, avec leur nombre', () => {
        cy.api('/api/photos?itemsPerPage=1').then(({ totalItems }) => {
            cy.visiter('/galerie')
            tuiles().should('have.length', Math.min(totalItems, 24))
            cy.findByText(`${totalItems} photographies`).should('exist')
            if (totalItems <= 24) {
                cy.findByRole('button', { name: 'Charger plus' }).should('not.exist')
            }
        })
    })

    it('CT-E2E-06 · le filtre par catégorie vit dans l\'adresse, le bouton précédent le restaure', () => {
        cy.api('/api/photos?itemsPerPage=1').its('totalItems').then((total) => {
            cy.api('/api/photos?itemsPerPage=1&category.slug=spectacle').its('totalItems').then((spectacle) => {
                cy.visiter('/galerie')

                filtres().within(() => cy.findByRole('link', { name: 'Spectacle' }).click())
                cy.location('search').should('eq', '?categorie=spectacle')
                tuiles().should('have.length', spectacle)

                filtres().within(() => cy.findByRole('link', { name: 'Tout' }).click())
                tuiles().should('have.length', total)

                cy.go('back')
                cy.location('search').should('eq', '?categorie=spectacle')
                tuiles().should('have.length', spectacle)
            })
        })
    })

    it('CT-E2E-07 · le mode grille et le filtre se conservent mutuellement, y compris au rechargement', () => {
        cy.visiter('/galerie')

        cy.findByRole('link', { name: 'Grille' }).click()
        filtres().within(() => cy.findByRole('link', { name: 'Voiture' }).click())

        const verifier = () => {
            cy.location('search').then((recherche) => {
                const parametres = new URLSearchParams(recherche)
                expect(parametres.get('vue')).to.eq('grille')
                expect(parametres.get('categorie')).to.eq('voiture')
            })
            cy.findByRole('link', { name: 'Grille' }).should('have.attr', 'aria-current', 'true')
            filtres().within(() => cy.findByRole('link', { name: 'Voiture' }).should('have.attr', 'aria-current', 'true'))
        }

        verifier()
        cy.reload()
        cy.get('#__nuxt').should($racine => expect(($racine[0] as unknown as { __vue_app__?: unknown }).__vue_app__).to.exist)
        verifier()
    })

    it('CT-E2E-08 · la visionneuse navigue en boucle au clavier, se ferme et rend le focus à la tuile', () => {
        cy.api('/api/photos?itemsPerPage=1').its('totalItems').then((total) => {
            cy.visiter('/galerie')

            tuiles().eq(2).click()
            cy.findByRole('dialog').should('exist')
            cy.findByText(`3 / ${total}`).should('exist')

            cy.realPress('ArrowRight')
            cy.findByText(`4 / ${total}`).should('exist')
            cy.realPress('ArrowLeft')
            cy.findByText(`3 / ${total}`).should('exist')

            cy.realPress('Escape')
            cy.findByRole('dialog').should('not.exist')
            tuiles().eq(2).then(($tuile) => {
                cy.focused().should('have.attr', 'aria-label', $tuile.attr('aria-label'))
            })

            tuiles().eq(2).click()
            cy.findByRole('dialog').click('topLeft')
            cy.findByRole('dialog').should('not.exist')
        })
    })
})
