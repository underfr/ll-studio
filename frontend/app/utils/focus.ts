/**
 * Gestion du focus des panneaux et fenêtres modales : menu mobile public,
 * panneau du back-office, visionneuse.
 */

/**
 * Place le focus sur un élément dès qu'il devient visible.
 *
 * daisyUI ne rend visibles ses panneaux qu'après un court délai de
 * transition : un focus donné plus tôt viserait un élément encore masqué et
 * resterait sans effet.
 */
export function focaliserQuandVisible(element: HTMLElement | null | undefined, essais = 30): void {
    if (!element || essais === 0) {
        return
    }

    // Sans checkVisibility (navigateurs anciens), on tente le focus aussitôt.
    if (typeof element.checkVisibility !== 'function' || element.checkVisibility({ visibilityProperty: true })) {
        element.focus()

        return
    }

    requestAnimationFrame(() => focaliserQuandVisible(element, essais - 1))
}

/**
 * Garde Tab et Maj+Tab à l'intérieur d'un conteneur : après le dernier
 * élément focalisable vient le premier, et inversement. Rendre le reste de la
 * page inerte ne suffit pas : le focus partirait alors vers l'interface du
 * navigateur au lieu de reboucler.
 */
export function piegerTabulation(evenement: KeyboardEvent, conteneur: HTMLElement | null): void {
    if (evenement.key !== 'Tab' || !conteneur) {
        return
    }

    const focusables = conteneur.querySelectorAll<HTMLElement>('a[href], button:not([disabled])')

    if (focusables.length === 0) {
        return
    }

    const premier = focusables[0]!
    const dernier = focusables[focusables.length - 1]!

    if (evenement.shiftKey && document.activeElement === premier) {
        evenement.preventDefault()
        dernier.focus()
    }
    else if (!evenement.shiftKey && document.activeElement === dernier) {
        evenement.preventDefault()
        premier.focus()
    }
}
