// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: '2025-07-15',
    devtools: { enabled: true },

    css: ['~/assets/css/tokens.css'],

    app: {
        head: {
            htmlAttrs: { lang: 'fr' },
            titleTemplate: '%s · LL Studio',
            meta: [
                { charset: 'utf-8' },
                { name: 'viewport', content: 'width=device-width, initial-scale=1' },
            ],
            link: [
                // Polices de la maquette : Cormorant Garamond pour les titres,
                // Space Mono pour l'interface, Archivo pour le texte courant.
                // L'auto-hébergement sera traité avec l'audit de performances
                // (issue #35).
                { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
                { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
                {
                    rel: 'stylesheet',
                    href: 'https://fonts.googleapis.com/css2'
                        + '?family=Archivo:wght@400;500'
                        + '&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500'
                        + '&family=Space+Mono:wght@400;700'
                        + '&display=swap',
                },
            ],
        },
    },
})
