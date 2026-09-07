/**
 * Source unique de la navigation publique.
 *
 * La navbar desktop et le panneau latéral mobile consomment la même liste :
 * ajouter une entrée ici la fait apparaître aux deux endroits, sans risque
 * de divergence.
 */
export interface LienNavigation {
    /** Libellé affiché, tel qu'il figure sur la maquette. */
    libelle: string
    /** Chemin interne, passé à <NuxtLink>. */
    chemin: string
}

const LIENS_PUBLICS: readonly LienNavigation[] = [
    { libelle: 'Accueil', chemin: '/' },
    { libelle: 'Galerie', chemin: '/galerie' },
    { libelle: 'Contact', chemin: '/contact' },
]

export function useNavigationPublique() {
    const route = useRoute()

    /**
     * L'accueil ne s'active que sur une correspondance exacte ; les autres
     * rubriques restent actives sur leurs sous-pages — /galerie doit rester
     * surligné quand on consulte /galerie/aurores-boreales.
     */
    function estActif(chemin: string): boolean {
        if (chemin === '/') {
            return route.path === '/'
        }

        return route.path === chemin || route.path.startsWith(`${chemin}/`)
    }

    return {
        liens: LIENS_PUBLICS,
        estActif,
    }
}
