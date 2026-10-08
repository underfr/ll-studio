import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import AppEnTete from '~/components/AppEnTete.vue'
import AppMenuMobile from '~/components/AppMenuMobile.vue'
import AppPiedDePage from '~/components/AppPiedDePage.vue'

/**
 * Navigation publique, règles RG-NAV.
 */
const montes: Array<{ unmount: () => void }> = []
const ajouts: HTMLElement[] = []

afterEach(() => {
    montes.splice(0).forEach(enveloppe => enveloppe.unmount())
    ajouts.splice(0).forEach(element => element.remove())
    document.body.style.overflow = ''
})

async function menu() {
    const declencheur = document.createElement('button')
    document.body.appendChild(declencheur)
    ajouts.push(declencheur)
    declencheur.focus()

    const enveloppe = await mountSuspended(AppMenuMobile, {
        props: {
            'ouvert': false,
            'onUpdate:ouvert': (valeur: boolean) => enveloppe.setProps({ ouvert: valeur }),
        },
        attachTo: document.body,
    })
    montes.push(enveloppe)

    await enveloppe.setProps({ ouvert: true })
    await flushPromises()

    return { enveloppe, declencheur }
}

describe('AppMenuMobile', () => {
    it('CT-CF-20 · à l\'ouverture, le focus va sur la fermeture et le panneau porte l\'identifiant attendu', async () => {
        await menu()

        expect(document.activeElement?.getAttribute('aria-label')).toBe('Fermer le menu')
        expect(document.getElementById('menu-mobile')?.getAttribute('role')).toBe('dialog')
        expect(document.body.style.overflow).toBe('hidden')
    })

    it.each([
        ['la touche Échap', () => document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))],
        ['un clic sur le voile', () => document.querySelector<HTMLElement>('.menu__voile')!.click()],
        ['un clic sur un lien', () => document.querySelector<HTMLElement>('.menu__lien')!.click()],
    ])('CT-CF-20 · se ferme par %s et rend le focus', async (_geste, fermer) => {
        const { enveloppe, declencheur } = await menu()

        fermer()
        await flushPromises()

        expect(enveloppe.props('ouvert')).toBe(false)
        expect(document.activeElement).toBe(declencheur)
        expect(document.body.style.overflow).toBe('')
    })
})

describe('AppEnTete', () => {
    it('CT-CF-21 · propose les quatre rubriques, le lien Admin, un bouton EN inerte et un burger relié au panneau', async () => {
        const entete = await mountSuspended(AppEnTete)
        montes.push(entete)

        const rubriques = entete.findAll('.nav__lien').map(lien => lien.text())
        expect(rubriques).toEqual(['Accueil', 'Galerie', 'Albums', 'Contact'])
        expect(entete.get('a.nav__admin').attributes('href')).toBe('/admin')

        const boutonsLangue = entete.findAll('button').filter(bouton => bouton.text() === 'EN')
        expect(boutonsLangue.length).toBeGreaterThan(0)
        for (const bouton of boutonsLangue) {
            expect(bouton.attributes('disabled')).toBeDefined()
        }

        expect(entete.get('button.burger').attributes('aria-controls')).toBe('menu-mobile')
    })
})

describe('AppPiedDePage', () => {
    it('CT-CF-22 · reprend les rubriques et laisse Instagram inerte tant qu\'aucun lien n\'existe', async () => {
        const pied = await mountSuspended(AppPiedDePage)
        montes.push(pied)

        expect(pied.findAll('.pied__lien').map(lien => lien.text())).toEqual(['Accueil', 'Galerie', 'Albums', 'Contact'])
        expect(pied.find('a.pied__social').exists()).toBe(false)
        expect(pied.find('span.pied__social--inactif').exists()).toBe(true)
    })
})
