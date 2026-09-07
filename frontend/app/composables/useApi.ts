import type { UseFetchOptions } from 'nuxt/app'
import type { ErreurApi } from '~/types/api'

/**
 * Enveloppe de useFetch branchée sur l'instance $fetch du plugin api.
 *
 * Elle conserve tout le comportement de useFetch (rendu serveur, mise en
 * cache par clé, réactivité des paramètres) en lui ajoutant l'URL de base et
 * les en-têtes de l'API.
 */
export function useApiFetch<T>(
    chemin: string | (() => string),
    options: UseFetchOptions<T> = {},
) {
    return useFetch(chemin, {
        ...options,
        $fetch: useNuxtApp().$api as typeof $fetch,
    } as UseFetchOptions<T>)
}

/**
 * Construit l'URL absolue d'un média servi par l'API.
 *
 * Le back renvoie des chemins relatifs à sa propre origine (« /uploads/... »).
 * Tels quels, ils pointeraient vers le serveur Nuxt, qui ne sert pas les
 * images. On utilise toujours l'URL publique, même pendant le rendu serveur :
 * l'attribut src finit dans le HTML et c'est le navigateur du visiteur qui le
 * résoudra, pas Nuxt.
 */
export function urlMedia(chemin: string | undefined | null): string {
    if (!chemin) {
        return ''
    }

    const base = useRuntimeConfig().public.apiBase

    return `${base}${chemin.startsWith('/') ? '' : '/'}${chemin}`
}

/**
 * Extrait un message lisible d'une erreur d'API.
 *
 * API Platform répond tantôt en Hydra, tantôt en Problem Details : le libellé
 * utile se trouve selon les cas dans « detail », « description » ou dans la
 * liste des violations de validation.
 */
export function messageErreur(erreur: unknown): string {
    const donnees = (erreur as { data?: ErreurApi } | null)?.data

    if (donnees?.violations?.length) {
        return donnees.violations
            .map(violation => violation.message)
            .join(' ')
    }

    return donnees?.detail
        ?? donnees?.description
        ?? donnees?.title
        ?? 'Le service est momentanément indisponible.'
}
