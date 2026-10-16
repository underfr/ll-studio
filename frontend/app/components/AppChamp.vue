<script setup lang="ts">
/**
 * Champ de formulaire : libellé, saisie et message d'erreur.
 *
 * Construit sur les composants « fieldset » et « input » de daisyUI. Les
 * classes « champ » restent comme points d'accroche des tests.
 *
 * Le contour par défaut de daisyUI, l'encre à 20 %, ne fait que 1,5:1 sur
 * le crème : on devine mal où cliquer. Il passe à 50 %, soit 3,5:1 (au moins
 * 3:1 pour un composant d'interface, issue #33). En erreur, la classe
 * input-error prend le relais avec la couleur d'erreur.
 *
 * Les attributs non déclarés (name, inputmode, autofocus...) sont transmis
 * au champ lui-même et non au conteneur : c'est lui que visent les
 * gestionnaires de mots de passe et les technologies d'assistance.
 */
defineOptions({ inheritAttrs: false })

const valeur = defineModel<string>({ required: true })

withDefaults(defineProps<{
    libelle: string
    type?: 'text' | 'email' | 'password' | 'search' | 'tel' | 'url'
    /** Message d'erreur. Sa présence suffit à passer le champ en erreur. */
    erreur?: string
}>(), {
    type: 'text',
    erreur: '',
})

const saisie = ref<HTMLInputElement | null>(null)

// useId garantit des identifiants stables entre rendu serveur et client.
const id = useId()
const idErreur = `${id}-erreur`

/** Permet au formulaire de placer le focus sur le premier champ en erreur. */
defineExpose({ focus: () => saisie.value?.focus() })
</script>

<template>
    <div class="champ fieldset" :class="{ 'champ--erreur': erreur }">
        <label :for="id" class="champ__libelle fieldset-legend">{{ libelle }}</label>

        <input
            :id="id"
            ref="saisie"
            v-model="valeur"
            v-bind="$attrs"
            :type="type"
            class="champ__saisie input w-full"
            :class="erreur ? 'input-error' : 'border-base-content/50'"
            :aria-invalid="erreur ? 'true' : undefined"
            :aria-describedby="erreur ? idErreur : undefined"
        >

        <p v-if="erreur" :id="idErreur" class="champ__erreur text-sm text-error">
            {{ erreur }}
        </p>
    </div>
</template>
