/**
 * Instance $fetch préconfigurée pour l'API du portfolio.
 *
 * Centraliser la configuration ici plutôt que de la répéter à chaque appel
 * évite qu'une requête oublie l'URL de base ou l'en-tête Accept, et donne un
 * point unique où brancher le jeton d'authentification (issue #25).
 */
export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig()

    /*
     * Deux origines pour la même API :
     *
     * - pendant le rendu serveur, Nuxt tourne dans le réseau Docker et joint
     *   directement le conteneur nginx, sans repasser par le port publié sur
     *   la machine hôte ;
     * - dans le navigateur, seule l'URL publique est joignable.
     */
    const baseUrl = import.meta.server && config.apiBaseServer
        ? config.apiBaseServer
        : config.public.apiBase

    const api = $fetch.create({
        baseURL: baseUrl,
        headers: { Accept: 'application/ld+json' },
    })

    return { provide: { api } }
})
