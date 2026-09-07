/**
 * Formes renvoyées par l'API Platform du back.
 *
 * Les collections sortent en JSON-LD 1.1 : les clés sont « member » et
 * « totalItems », sans le préfixe « hydra: » des versions précédentes.
 */

export interface RessourceJsonLd {
    '@id': string
    '@type': string
}

/** Liens de pagination exposés sous la clé « view ». */
export interface VueCollection {
    '@id': string
    first?: string
    last?: string
    previous?: string
    next?: string
}

export interface CollectionJsonLd<T> extends RessourceJsonLd {
    totalItems: number
    member: T[]
    view?: VueCollection
}

export interface Categorie extends RessourceJsonLd {
    id: number
    name: string
    slug: string
    /** Absent quand la catégorie est imbriquée dans une photo ou un album. */
    photoCount?: number
}

export interface Photo extends RessourceJsonLd {
    id: number
    title: string
    alt: string
    /** Nom du fichier sur le disque du serveur, sans son dossier. */
    filePath: string
    /** Chemin public de l'image, relatif à l'origine de l'API. */
    contentUrl: string
    /** Absente de la couverture d'album, qui ne porte qu'un extrait des champs. */
    description?: string
    visible?: boolean
    createdAt?: string
    category?: Categorie
    /** Renvoyés uniquement par GET /api/photos/{id}. */
    albums?: string[]
    owner?: string
}

export interface Album extends RessourceJsonLd {
    id: number
    title: string
    slug: string
    description: string | null
    visible: boolean
    createdAt: string
    category: Categorie
    /** Absente tant qu'aucune photo de couverture n'a été choisie. */
    coverPhoto?: Photo
    photoCount: number
    /** Renvoyées uniquement par GET /api/albums/{id}. */
    photos?: Photo[]
}

export interface Message extends RessourceJsonLd {
    id: number
    name: string
    email: string
    subject: string
    message: string
    read: boolean
    createdAt: string
}

/** Corps d'erreur renvoyé par l'API, aussi bien Hydra que Problem Details. */
export interface ErreurApi {
    status?: number
    title?: string
    detail?: string
    description?: string
    violations?: Array<{ propertyPath: string, message: string }>
}

/**
 * Paramètres de requête acceptés par les collections : filtres de recherche,
 * pagination et tri mis en place à l'issue #7.
 */
export type ParametresCollection = Record<string, string | number | boolean | undefined>
