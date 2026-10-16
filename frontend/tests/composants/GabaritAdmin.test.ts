import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import GabaritAdmin from '~/layouts/admin.vue'

/**
 * Gabarit du back-office, règles RG-ADM-09 et RG-ADM-10.
 */
const { session, navigation } = vi.hoisted(() => ({
    session: {
        deconnecter: vi.fn(),
        // La garde globale laisse passer : le test porte sur le gabarit.
        restaurer: vi.fn(),
        estAdmin: { value: true },
    },
    navigation: vi.fn(),
}))

// Le compte doit être une vraie référence pour que le gabarit la déballe.
mockNuxtImport('useSession', () => () => ({ ...session, compte: ref({ fullName: 'Studio Administration' }) }))
mockNuxtImport('navigateTo', () => navigation)

const montes: Array<{ unmount: () => void }> = []

beforeEach(() => {
    session.deconnecter.mockReset().mockResolvedValue(undefined)
    session.restaurer.mockReset().mockResolvedValue(true)
    navigation.mockReset()
})

afterEach(() => {
    montes.splice(0).forEach(enveloppe => enveloppe.unmount())
})

async function gabarit(route = '/admin/photos/12') {
    const enveloppe = await mountSuspended(GabaritAdmin, {
        route,
        slots: { default: () => h('p', { class: 'contenu-de-page' }, 'Contenu') },
    })
    montes.push(enveloppe)

    return enveloppe
}

describe('Gabarit du back-office', () => {
    it('CT-CF-55 · navigation propre au back-office, rubrique active signalée sur une sous-page', async () => {
        const enveloppe = await gabarit()
        const liens = enveloppe.findAll('.nav__lien')

        expect(liens.map(lien => lien.text())).toEqual(['Tableau de bord', 'Photos', 'Albums', 'Catégories', 'Messages'])
        expect(enveloppe.findAll('.nav__lien[aria-current="page"]').map(lien => lien.text())).toEqual(['Photos'])
        expect(enveloppe.find('.entete').exists()).toBe(false)
        expect(enveloppe.get('.lien-evitement').attributes('href')).toBe('#contenu')
        expect(enveloppe.get('main#contenu .contenu-de-page').text()).toBe('Contenu')
    })

    it('CT-CF-55 · affiche le compte connecté et ramène à la connexion après déconnexion', async () => {
        const enveloppe = await gabarit()

        expect(enveloppe.get('.barre__compte').text()).toBe('Studio Administration')

        await enveloppe.get('.barre__deconnexion').trigger('click')
        await flushPromises()

        expect(session.deconnecter).toHaveBeenCalledOnce()
        expect(navigation).toHaveBeenCalledWith('/admin/connexion')
    })
})
