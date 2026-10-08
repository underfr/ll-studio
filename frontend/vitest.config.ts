import { defineVitestConfig } from '@nuxt/test-utils/config'

/*
 * Tests unitaires et de composants du front (plan de tests, section 4.2).
 *
 * L'environnement « nuxt » fournit les imports automatiques, le routeur et la
 * configuration d'exécution, comme dans l'application. Aucun appel réseau :
 * les composables d'API sont remplacés par des doublures dans chaque test.
 */
export default defineVitestConfig({
    test: {
        environment: 'nuxt',
        environmentOptions: {
            nuxt: { domEnvironment: 'happy-dom' },
        },
        include: ['tests/**/*.test.ts'],
    },
})
