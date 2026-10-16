import { mountSuspended } from '@nuxt/test-utils/runtime'
import { afterEach, describe, expect, it } from 'vitest'
import AppChamp from '~/components/AppChamp.vue'

/**
 * Champ de formulaire, règle RG-ADM-11.
 */
const montes: Array<{ unmount: () => void }> = []

afterEach(() => {
    montes.splice(0).forEach(enveloppe => enveloppe.unmount())
})

async function champ(props: Record<string, unknown> = {}) {
    const enveloppe = await mountSuspended(AppChamp, {
        props: { modelValue: '', libelle: 'Adresse e-mail', type: 'email', ...props },
        attrs: { name: 'email', autocomplete: 'username' },
    })
    montes.push(enveloppe)

    return enveloppe
}

describe('AppChamp', () => {
    it('CT-CF-50 · le libellé est relié au champ, qui reçoit les attributs transmis', async () => {
        const enveloppe = await champ()
        const saisie = enveloppe.get('input')

        expect(enveloppe.get('label').text()).toBe('Adresse e-mail')
        expect(enveloppe.get('label').attributes('for')).toBe(saisie.attributes('id'))
        expect(saisie.attributes('type')).toBe('email')
        expect(saisie.attributes('name')).toBe('email')
        expect(saisie.attributes('autocomplete')).toBe('username')
        expect(enveloppe.get('.champ').attributes('name')).toBeUndefined()
        expect(saisie.attributes('aria-invalid')).toBeUndefined()
    })

    it('CT-CF-50 · la saisie met à jour la valeur liée', async () => {
        const enveloppe = await champ()

        await enveloppe.get('input').setValue('admin@ll-studio.test')

        expect(enveloppe.emitted('update:modelValue')?.at(-1)).toEqual(['admin@ll-studio.test'])
    })

    it('CT-CF-50 · en erreur, le champ est marqué invalide et relié à son message', async () => {
        const enveloppe = await champ({ erreur: 'Indiquez votre adresse e-mail.' })
        const saisie = enveloppe.get('input')
        const message = enveloppe.get('.champ__erreur')

        expect(enveloppe.get('.champ').classes()).toContain('champ--erreur')
        expect(saisie.attributes('aria-invalid')).toBe('true')
        expect(saisie.attributes('aria-describedby')).toBe(message.attributes('id'))
        expect(message.text()).toBe('Indiquez votre adresse e-mail.')
    })
})
