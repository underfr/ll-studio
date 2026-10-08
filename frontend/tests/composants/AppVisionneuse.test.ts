import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import AppVisionneuse from '~/components/AppVisionneuse.vue'
import type { Photo } from '~/types/api'
import { photo, photos } from '../support/donnees'

/**
 * Visionneuse plein écran, règles RG-LBX.
 *
 * Le dialogue est téléporté dans <body> : on l'interroge donc sur le document
 * et non sur l'enveloppe du composant.
 */
const montes: Array<{ unmount: () => void }> = []
const ajouts: HTMLElement[] = []

async function monter(liste: Photo[], index: number | null = null) {
    const enveloppe = await mountSuspended(AppVisionneuse, {
        props: {
            'photos': liste,
            index,
            'onUpdate:index': (valeur: number | null) => enveloppe.setProps({ index: valeur }),
        },
        attachTo: document.body,
    })
    montes.push(enveloppe)

    return enveloppe
}

async function ouvrir(enveloppe: Awaited<ReturnType<typeof monter>>, index: number) {
    await enveloppe.setProps({ index })
    await flushPromises()
}

const dialogue = () => document.body.querySelector<HTMLElement>('[role="dialog"]')
const bouton = (intitule: string) => document.body.querySelector<HTMLButtonElement>(`button[aria-label="${intitule}"]`)
const compteur = () => document.body.querySelector('.visionneuse__compteur')?.textContent?.trim()

function touche(cle: string, avecMaj = false) {
    document.dispatchEvent(new KeyboardEvent('keydown', { key: cle, shiftKey: avecMaj, bubbles: true }))
}

// On démonte proprement : vider document.body arracherait le DOM de
// composants que Vue croit encore montés.
afterEach(() => {
    montes.splice(0).forEach(enveloppe => enveloppe.unmount())
    ajouts.splice(0).forEach(element => element.remove())
    document.body.style.overflow = ''
    vi.unstubAllGlobals()
})

describe('AppVisionneuse', () => {
    it('CT-CF-01 · affiche la photo choisie, sa légende, le compteur et un dialogue accessible', async () => {
        await monter(photos(5), 1)

        const fenetre = dialogue()!
        expect(fenetre.getAttribute('aria-modal')).toBe('true')
        expect(fenetre.getAttribute('aria-label')).toBe('Photographie : Photographie 2')

        const image = fenetre.querySelector('img')!
        expect(image.getAttribute('src')).toContain('/uploads/photos/photo-2.jpg')
        expect(image.getAttribute('alt')).toBe('Texte alternatif 2')
        expect(fenetre.textContent).toContain('Spectacle')
        expect(fenetre.textContent).toContain('Description 2')
        expect(compteur()).toBe('2 / 5')
    })

    it('CT-CF-02 · la navigation boucle, aux boutons comme au clavier', async () => {
        const enveloppe = await monter(photos(5), 4)

        bouton('Photographie suivante')!.click()
        await flushPromises()
        expect(enveloppe.props('index')).toBe(0)

        bouton('Photographie précédente')!.click()
        await flushPromises()
        expect(enveloppe.props('index')).toBe(4)

        touche('ArrowRight')
        await flushPromises()
        expect(enveloppe.props('index')).toBe(0)

        touche('ArrowLeft')
        await flushPromises()
        expect(enveloppe.props('index')).toBe(4)
    })

    it('CT-CF-03 · une seule photo, aucun bouton de navigation', async () => {
        await monter([photo(1)], 0)

        expect(bouton('Photographie suivante')).toBeNull()
        expect(bouton('Photographie précédente')).toBeNull()
    })

    it('CT-CF-04 · Échap et un clic sur le fond ferment, pas un clic sur la photo, la légende ou une flèche', async () => {
        const enveloppe = await monter(photos(3), 0)

        for (const selecteur of ['.visionneuse__photo', '.visionneuse__legende', '.visionneuse__nav--suivant']) {
            document.body.querySelector<HTMLElement>(selecteur)!.click()
            await flushPromises()
            expect(enveloppe.props('index'), selecteur).not.toBeNull()
        }

        document.body.querySelector<HTMLElement>('.visionneuse__fond')!.click()
        await flushPromises()
        expect(enveloppe.props('index')).toBeNull()

        await ouvrir(enveloppe, 0)
        touche('Escape')
        await flushPromises()
        expect(enveloppe.props('index')).toBeNull()
    })

    it('CT-CF-05 · le focus part sur la fermeture, reste captif, puis revient à son point de départ', async () => {
        const declencheur = document.createElement('button')
        document.body.appendChild(declencheur)
        ajouts.push(declencheur)
        declencheur.focus()

        const enveloppe = await monter(photos(3))
        await ouvrir(enveloppe, 0)

        const fermer = bouton('Fermer la visionneuse')!
        const suivant = bouton('Photographie suivante')!
        expect(document.activeElement).toBe(fermer)
        expect(document.body.style.overflow).toBe('hidden')

        // Tab sur le dernier bouton revient au premier, Maj+Tab sur le premier
        // part sur le dernier.
        suivant.focus()
        touche('Tab')
        expect(document.activeElement).toBe(fermer)
        touche('Tab', true)
        expect(document.activeElement).toBe(suivant)

        touche('Escape')
        await flushPromises()
        expect(document.activeElement).toBe(declencheur)
        expect(document.body.style.overflow).toBe('')
    })

    it('CT-CF-06 · précharge les deux voisines de la photo affichée', async () => {
        const sources: string[] = []
        vi.stubGlobal('Image', class {
            set src(valeur: string) {
                sources.push(valeur)
            }
        })

        const enveloppe = await monter(photos(3))
        await ouvrir(enveloppe, 0)

        expect(sources.some(source => source.endsWith('/photo-3.jpg'))).toBe(true)
        expect(sources.some(source => source.endsWith('/photo-2.jpg'))).toBe(true)
    })

    it('CT-CF-07 · sans catégorie ni description, ni l\'une ni l\'autre n\'est affichée', async () => {
        await monter([photo(1, { category: undefined, description: undefined })], 0)

        expect(document.body.querySelector('.visionneuse__categorie')).toBeNull()
        expect(document.body.querySelector('.visionneuse__description')).toBeNull()
    })
})
