import type {
    Album,
    Categorie,
    CollectionJsonLd,
    ParametresCollection,
    Photo,
} from '~/types/api'

/**
 * Composables métier, un par ressource exposée par l'API.
 *
 * Ils ne font que nommer les chemins et typer les réponses : toute la
 * mécanique de requête vit dans useApiFetch. Les paramètres sont acceptés
 * sous forme de getter pour rester réactifs, de sorte qu'un changement de
 * filtre déclenche une nouvelle requête sans code supplémentaire.
 */

/** Transforme les paramètres en chaîne de requête, en ignorant les valeurs vides. */
function chaineDeRequete(parametres: ParametresCollection): string {
    const query = new URLSearchParams()

    for (const [cle, valeur] of Object.entries(parametres)) {
        if (valeur !== undefined && valeur !== '') {
            query.set(cle, String(valeur))
        }
    }

    const rendu = query.toString()

    return rendu ? `?${rendu}` : ''
}

/**
 * Photographies de la galerie.
 *
 * Les filtres de l'issue #7 sont utilisables tels quels, par exemple
 * `usePhotos(() => ({ 'albums.slug': 'puy-du-fou-2024' }))` pour les photos
 * d'une série, ou `{ 'category.slug': 'spectacle' }` pour une catégorie.
 */
export function usePhotos(parametres: () => ParametresCollection = () => ({})) {
    return useApiFetch<CollectionJsonLd<Photo>>(
        () => `/api/photos${chaineDeRequete(parametres())}`,
    )
}

export function usePhoto(id: () => number | string) {
    return useApiFetch<Photo>(() => `/api/photos/${id()}`)
}

/** Séries photographiques. La collection ne porte que la couverture et le compteur. */
export function useAlbums(parametres: () => ParametresCollection = () => ({})) {
    return useApiFetch<CollectionJsonLd<Album>>(
        () => `/api/albums${chaineDeRequete(parametres())}`,
    )
}

/**
 * Série identifiée par son slug.
 *
 * L'API n'expose pas d'opération d'item par slug : on passe donc par le
 * filtre de collection mis en place à l'issue #7, et on retient le premier
 * résultat. Les photos de la série se récupèrent séparément avec
 * `usePhotos(() => ({ 'albums.slug': slug }))`, ce qui évite une requête
 * enchaînée et laisse la pagination jouer son rôle.
 */
export function useAlbum(slug: () => string) {
    const requete = useApiFetch<CollectionJsonLd<Album>>(
        () => `/api/albums${chaineDeRequete({ slug: slug() })}`,
    )

    return {
        ...requete,
        album: computed<Album | null>(() => requete.data.value?.member[0] ?? null),
    }
}

/** Catégories. La collection n'est pas paginée : elle tient en une requête. */
export function useCategories() {
    return useApiFetch<CollectionJsonLd<Categorie>>('/api/categories')
}
