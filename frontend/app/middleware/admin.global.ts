/**
 * Garde du back-office (issue #25).
 *
 * Globale plutôt que déclarée page par page : toute route sous /admin est
 * protégée par défaut, et une page ajoutée plus tard ne peut pas l'être
 * par oubli. Seule la page de connexion reste ouverte.
 *
 * Ce n'est qu'un confort d'interface. La sécurité repose sur l'API, qui
 * vérifie le jeton et le rôle à chaque opération : contourner cette garde
 * donnerait au mieux des écrans vides.
 */
export default defineNuxtRouteMiddleware(async (to) => {
    const estBackOffice = to.path === '/admin' || to.path.startsWith('/admin/')

    // Le back-office est rendu côté navigateur : le serveur n'a rien à garder.
    if (import.meta.server || !estBackOffice) {
        return
    }

    const session = useSession()
    const connecte = await session.restaurer()

    if (to.path === '/admin/connexion') {
        // Déjà connecté : inutile de revoir le formulaire.
        if (connecte && session.estAdmin.value) {
            return navigateTo(cheminDeRetour(to.query.redirection), { replace: true })
        }

        return
    }

    if (!connecte) {
        return navigateTo({ path: '/admin/connexion', query: { redirection: to.fullPath } })
    }

    // Un compte valide mais sans ROLE_ADMIN n'a rien à faire ici : sa
    // session est fermée pour ne pas laisser un jeton inutile en circulation.
    if (!session.estAdmin.value) {
        await session.deconnecter()

        return navigateTo({ path: '/admin/connexion', query: { raison: 'refuse' } })
    }
})
