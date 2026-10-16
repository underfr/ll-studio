/**
 * Back-office : accès, session, déconnexion et panneau mobile, règles
 * RG-ADM, RG-AUTH-11, RG-AUTH-12 et RG-IDX-03.
 */

/** Remplit et envoie le formulaire de connexion avec le compte de démonstration. */
function seConnecter(): void {
    cy.env(['adminEmail', 'adminPassword']).then(({ adminEmail, adminPassword }) => {
        cy.findByLabelText('Adresse e-mail').clear().type(adminEmail)
        cy.findByLabelText('Mot de passe').clear().type(adminPassword, { log: false })
        cy.findByRole('button', { name: 'Se connecter' }).click()
    })
}

describe('Back-office', () => {
    it('CT-E2E-16 · sans session, la page demandée attend la connexion, puis s\'ouvre et survit au rechargement', () => {
        cy.visiter('/admin/photos')

        cy.location('pathname').should('eq', '/admin/connexion')
        cy.location('search').should((recherche) => {
            expect(new URLSearchParams(recherche).get('redirection')).to.eq('/admin/photos')
        })

        cy.env(['adminEmail']).then(({ adminEmail }) => {
            cy.findByLabelText('Adresse e-mail').type(adminEmail)
        })
        cy.findByLabelText('Mot de passe').type('pas-le-bon-mot-de-passe')
        cy.findByRole('button', { name: 'Se connecter' }).click()
        cy.findByRole('alert').should('have.text', 'Adresse e-mail ou mot de passe incorrect.')
        cy.findByLabelText('Mot de passe').should('have.value', '')

        seConnecter()

        cy.location('pathname').should('eq', '/admin/photos')
        cy.findByRole('heading', { level: 1 }).should('have.text', 'Photographies')
        cy.get('.barre__compte').should('have.text', 'Studio Administration')

        // Aucun jeton n'est lisible par un script de la page.
        cy.window().then((fenetre) => {
            expect(fenetre.localStorage.length, 'localStorage').to.eq(0)
            expect(fenetre.sessionStorage.length, 'sessionStorage').to.eq(0)
            expect(fenetre.document.cookie, 'document.cookie').not.to.contain('refresh_token')
        })

        cy.reload()
        cy.location('pathname').should('eq', '/admin/photos')
        cy.findByRole('heading', { level: 1 }).should('have.text', 'Photographies')
    })

    it('CT-E2E-17 · la déconnexion ferme la session pour de bon', () => {
        cy.visiter('/admin/connexion')
        seConnecter()
        cy.location('pathname').should('eq', '/admin')
        // Le gabarit laisse des espaces autour du texte du titre.
        cy.findByRole('heading', { level: 1 }).invoke('text').invoke('trim').should('eq', 'Bonjour Studio.')

        cy.findByRole('button', { name: 'Se déconnecter' }).click()
        cy.location('pathname').should('eq', '/admin/connexion')

        // Un rechargement ne rouvre rien : le jeton a été révoqué par l'API.
        cy.visiter('/admin')
        cy.location('pathname').should('eq', '/admin/connexion')
    })

    it('CT-E2E-19 · sur mobile, le panneau de navigation se pilote au clavier et se ferme en naviguant', () => {
        cy.viewport(375, 812)
        cy.visiter('/admin/connexion')
        seConnecter()
        cy.location('pathname').should('eq', '/admin')

        // Fermé, le panneau n'offre aucun lien atteignable.
        cy.get('.drawer-side').should('not.be.visible')

        cy.findByRole('button', { name: 'Ouvrir le menu' }).should('have.attr', 'aria-expanded', 'false').focus()
        cy.realPress('Enter')
        cy.focused().should('have.attr', 'aria-label', 'Fermer le menu')
        cy.findByRole('button', { name: 'Ouvrir le menu' }).should('have.attr', 'aria-expanded', 'true')
        cy.get('.drawer-content').should('have.attr', 'inert')

        cy.realPress('Escape')
        cy.focused().should('have.attr', 'aria-label', 'Ouvrir le menu')
        cy.get('.drawer-content').should('not.have.attr', 'inert')

        cy.realPress('Enter')
        cy.findByRole('navigation', { name: 'Navigation de l\'administration' }).within(() => {
            cy.findByRole('link', { name: 'Messages' }).click()
        })
        cy.location('pathname').should('eq', '/admin/messages')
        cy.findByRole('button', { name: 'Ouvrir le menu' }).should('have.attr', 'aria-expanded', 'false')
        cy.get('.drawer-side').should('not.be.visible')
    })

    it('CT-E2E-18 · les pages du back-office ne sont pas indexables', () => {
        cy.request('/admin/connexion').its('headers.x-robots-tag').should('eq', 'noindex, nofollow')

        cy.visiter('/admin/connexion')
        cy.get('meta[name="robots"]').should('have.attr', 'content', 'noindex, nofollow')
    })
})
