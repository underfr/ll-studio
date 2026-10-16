<script setup lang="ts">
/**
 * Champ de formulaire (composant « Input » du design system).
 *
 * Libellé en Space Mono capitales au-dessus d'un champ Archivo 15 px. Les
 * états de la maquette sont portés par le navigateur (focus, désactivé,
 * indication) ou par la prop erreur, qui colore le champ en terracotta et
 * affiche le message sous le champ.
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
    <div class="champ" :class="{ 'champ--erreur': erreur }">
        <label :for="id" class="champ__libelle">{{ libelle }}</label>

        <input
            :id="id"
            ref="saisie"
            v-model="valeur"
            v-bind="$attrs"
            :type="type"
            class="champ__saisie"
            :aria-invalid="erreur ? 'true' : undefined"
            :aria-describedby="erreur ? idErreur : undefined"
        >

        <p v-if="erreur" :id="idErreur" class="champ__erreur">
            {{ erreur }}
        </p>
    </div>
</template>

<style scoped>
.champ {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.champ__libelle {
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.champ__saisie {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid var(--bordure-champ);
    border-radius: 2px;
    background-color: var(--fond-page);
    color: var(--texte-principal);
    font-family: var(--police-texte);
    font-size: 15px;
    line-height: normal;
    transition: border-color var(--transition-courte);
}

.champ__saisie::placeholder {
    color: var(--texte-tertiaire);
}

/* La maquette marque le focus par la bordure or, à la place de l'anneau
   commun. Une ombre d'un pixel double cette bordure : un trait d'un pixel
   qui change de teinte se repère mal, surtout au clavier. */
.champ__saisie:focus {
    border-color: var(--or-texte);
    box-shadow: 0 0 0 1px var(--or-texte);
    outline: none;
}

.champ__saisie:disabled {
    background-color: var(--fond-surface-teintee);
    color: var(--texte-tertiaire);
    cursor: not-allowed;
}

.champ--erreur .champ__saisie {
    border-color: var(--terracotta);
    color: var(--terracotta);
}

/* Un champ en erreur garde sa couleur quand il reprend le focus. */
.champ--erreur .champ__saisie:focus {
    box-shadow: 0 0 0 1px var(--terracotta);
}

.champ__erreur {
    color: var(--terracotta);
    font-size: 13px;
    line-height: 1.4;
}
</style>
