import { execSync } from 'node:child_process'
import { defineConfig } from 'cypress'

/*
 * Tests de bout en bout (plan de tests, section 4.3).
 *
 * Ils tournent sur la machine hôte et non dans un conteneur : l'image du front
 * est en Alpine, où Cypress ne s'exécute pas, et un navigateur conteneurisé
 * n'atteindrait pas l'API sur localhost:8000. La pile Docker doit être démarrée.
 */
export default defineConfig({
    e2e: {
        baseUrl: 'http://localhost:3000',
        specPattern: 'cypress/e2e/**/*.cy.ts',
        supportFile: 'cypress/support/e2e.ts',
        viewportWidth: 1280,
        viewportHeight: 800,
        // Le serveur de développement compile une page à sa première visite.
        defaultCommandTimeout: 10_000,
        pageLoadTimeout: 60_000,
        video: false,
        // Configuration publique, lue par Cypress.expose().
        expose: {
            apiUrl: 'http://localhost:8000',
        },
        // Valeurs sensibles, lues par cy.env() : compte de démonstration créé
        // par les fixtures. Surchargeables par CYPRESS_ADMIN_EMAIL et
        // CYPRESS_ADMIN_PASSWORD.
        env: {
            adminEmail: 'admin@ll-studio.test',
            adminPassword: 'Demo-LLStudio-2026!',
        },
        setupNodeEvents(on) {
            /*
             * Jeu de référence rechargé une fois avant la campagne (décision
             * D2 du plan) : cela efface la base de développement, qui ne doit
             * donc contenir que les fixtures. Les parcours ne modifient
             * aucune donnée, un seul rechargement suffit.
             */
            on('before:run', () => {
                execSync(
                    'docker compose -f ../docker/docker-compose.yml exec -T php php bin/console doctrine:fixtures:load --no-interaction',
                    { stdio: 'inherit' },
                )
            })
        },
    },
})
