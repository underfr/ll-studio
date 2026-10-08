import '@testing-library/cypress/add-commands'
import 'cypress-real-events'

/** Collection JSON-LD renvoyée par l'API. */
export interface Collection<T = Record<string, unknown>> {
    member: T[]
    totalItems: number
}

declare global {
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace Cypress {
        interface Chainable {
            /** Visite une page et attend que Nuxt l'ait hydratée. */
            visiter(chemin: string, options?: Partial<VisitOptions>): Chainable<void>
            /** Interroge l'API publique, éventuellement avec un jeton. */
            api<T = Collection>(chemin: string, jeton?: string): Chainable<T>
            /** Jeton d'accès du compte administrateur de démonstration. */
            jetonAdmin(): Chainable<string>
        }
    }
}

Cypress.Commands.add('visiter', (chemin: string, options: Partial<Cypress.VisitOptions> = {}) => {
    cy.visit(chemin, options)

    // Nuxt rend la page côté serveur, puis Vue l'hydrate. Un clic avant la fin
    // de l'hydratation peut se perdre : on attend que Vue soit monté.
    cy.get('#__nuxt').should(($racine) => {
        expect(($racine[0] as unknown as { __vue_app__?: unknown }).__vue_app__, 'application hydratée').to.exist
    })
})

Cypress.Commands.add('api', (chemin: string, jeton?: string) => {
    return cy.request({
        url: `${Cypress.expose('apiUrl')}${chemin}`,
        headers: {
            Accept: 'application/ld+json',
            ...(jeton ? { Authorization: `Bearer ${jeton}` } : {}),
        },
    }).its('body')
})

Cypress.Commands.add('jetonAdmin', () => {
    return cy.env(['adminEmail', 'adminPassword']).then(({ adminEmail, adminPassword }) => {
        return cy.request('POST', `${Cypress.expose('apiUrl')}/api/login`, {
            email: adminEmail,
            password: adminPassword,
        }).its('body.token')
    })
})
