import type { LienNavigation } from '~/composables/useNavigationPublique'

/**
 * Source unique de la navigation du back-office.
 *
 * Même principe que useNavigationPublique : la barre latérale desktop et la
 * barre supérieure mobile lisent la même liste.
 */
const LIENS_ADMIN: readonly LienNavigation[] = [
    { libelle: 'Tableau de bord', chemin: '/admin' },
    { libelle: 'Photos', chemin: '/admin/photos' },
    { libelle: 'Albums', chemin: '/admin/albums' },
    { libelle: 'Catégories', chemin: '/admin/categories' },
    { libelle: 'Messages', chemin: '/admin/messages' },
]

export function useNavigationAdmin() {
    const route = useRoute()

    /**
     * Le tableau de bord ne s'active que sur /admin exactement : toutes les
     * autres rubriques commencent aussi par /admin. Les rubriques restent
     * actives sur leurs sous-pages, /admin/photos/12 surligne « Photos ».
     */
    function estActif(chemin: string): boolean {
        if (chemin === '/admin') {
            return route.path === '/admin' || route.path === '/admin/'
        }

        return route.path === chemin || route.path.startsWith(`${chemin}/`)
    }

    return {
        liens: LIENS_ADMIN,
        estActif,
    }
}
