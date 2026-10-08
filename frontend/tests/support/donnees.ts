import type { Album, Categorie, Photo } from '~/types/api'

/**
 * Données de test au format JSON-LD renvoyé par l'API.
 */
export function categorie(nom = 'Spectacle', slug = nom.toLowerCase()): Categorie {
    return { '@id': `/api/categories/${slug}`, '@type': 'Category', 'id': 1, 'name': nom, slug }
}

export function photo(n: number, surcharge: Partial<Photo> = {}): Photo {
    return {
        '@id': `/api/photos/${n}`,
        '@type': 'Photo',
        'id': n,
        'title': `Photographie ${n}`,
        'alt': `Texte alternatif ${n}`,
        'filePath': `photo-${n}.jpg`,
        'contentUrl': `/uploads/photos/photo-${n}.jpg`,
        'description': `Description ${n}`,
        'category': categorie(),
        ...surcharge,
    }
}

export function photos(nombre: number): Photo[] {
    return Array.from({ length: nombre }, (_, rang) => photo(rang + 1))
}

export function serie(surcharge: Partial<Album> = {}): Album {
    return {
        '@id': '/api/albums/5',
        '@type': 'Album',
        'id': 5,
        'title': 'Puy du Fou 2024',
        'slug': 'puy-du-fou-2024',
        'description': 'Deux journées de spectacles.',
        'visible': true,
        'createdAt': '2024-08-18T21:00:00+00:00',
        'category': categorie(),
        'coverPhoto': photo(7),
        'photoCount': 6,
        ...surcharge,
    }
}

/** Collection JSON-LD autour d'une liste. */
export function collection<T>(membres: T[], total = membres.length) {
    return { '@id': '/api/test', '@type': 'Collection', 'member': membres, 'totalItems': total }
}
