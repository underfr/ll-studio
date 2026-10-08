import { describe, expect, it } from 'vitest'

/**
 * Fonctions utilitaires de l'API, règles RG-ERR-05 et RG-TUI-03.
 */
describe('messageErreur', () => {
    it('CT-UF-01 · met bout à bout les messages de validation', () => {
        const erreur = {
            data: {
                violations: [
                    { propertyPath: 'title', message: 'Le titre est obligatoire.' },
                    { propertyPath: 'alt', message: 'Le texte alternatif est obligatoire.' },
                ],
            },
        }

        expect(messageErreur(erreur)).toBe('Le titre est obligatoire. Le texte alternatif est obligatoire.')
    })

    it('CT-UF-02 · retient le détail, puis la description, puis le titre', () => {
        expect(messageErreur({ data: { detail: 'Détail' } })).toBe('Détail')
        expect(messageErreur({ data: { description: 'Description' } })).toBe('Description')
        expect(messageErreur({ data: { title: 'Titre' } })).toBe('Titre')
        expect(messageErreur({ data: { title: 'Titre', description: 'Description', detail: 'Détail' } })).toBe('Détail')
    })

    it.each([
        ['null', null],
        ['undefined', undefined],
        ['une chaîne', 'erreur'],
        ['un objet sans data', { status: 500 }],
        ['une liste de violations vide', { data: { violations: [] } }],
    ])('CT-UF-03 · retombe sur le message par défaut avec %s', (_libelle, erreur) => {
        expect(messageErreur(erreur)).toBe('Le service est momentanément indisponible.')
    })
})

describe('urlMedia', () => {
    // Lue dans les tests et non à la collecte du fichier : l'application Nuxt
    // n'existe qu'une fois l'environnement de test démarré.
    const origine = () => useRuntimeConfig().public.apiBase

    it.each([
        ['null', null],
        ['undefined', undefined],
        ['une chaîne vide', ''],
    ])('CT-UF-04 · renvoie une chaîne vide pour %s', (_libelle, chemin) => {
        expect(urlMedia(chemin)).toBe('')
    })

    it('CT-UF-04 · préfixe le chemin par l\'origine publique de l\'API', () => {
        expect(urlMedia('/uploads/photos/a.jpg')).toBe(`${origine()}/uploads/photos/a.jpg`)
    })

    it('CT-UF-04 · complète un chemin sans barre oblique initiale', () => {
        expect(urlMedia('uploads/photos/a.jpg')).toBe(`${origine()}/uploads/photos/a.jpg`)
    })
})
