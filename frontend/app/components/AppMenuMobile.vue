<script setup lang="ts">
/**
 * Menu mobile du site public, sur le « drawer » de daisyUI.
 *
 * Le panneau est téléporté dans <body> : l'en-tête qui le déclenche applique
 * un flou d'arrière-plan, qui ferait du panneau fixe un prisonnier de sa
 * boîte. Hors de #__nuxt, il peut aussi rendre tout le site inerte sans
 * l'être lui-même.
 *
 * daisyUI fournit le panneau, le voile, la transition et le blocage du
 * défilement. Le reste est ajouté ici (RG-NAV-03) : focus sur la fermeture
 * dès que le panneau est visible, gardé dans le panneau, Échap pour fermer,
 * focus rendu au bouton qui l'avait ouvert, page inerte pendant l'ouverture.
 */
const ouvert = defineModel<boolean>('ouvert', { required: true })

const { liens, estActif } = useNavigationPublique()

const panneau = ref<HTMLElement | null>(null)
const boutonFermer = ref<HTMLButtonElement | null>(null)

/** Élément qui avait le focus avant l'ouverture, pour le lui rendre ensuite. */
let origineDuFocus: HTMLElement | null = null

function fermer(): void {
    ouvert.value = false
}

function surTouche(evenement: KeyboardEvent): void {
    if (!ouvert.value) {
        return
    }

    if (evenement.key === 'Escape') {
        evenement.preventDefault()
        fermer()

        return
    }

    piegerTabulation(evenement, panneau.value)
}

/** Le site entier vit dans #__nuxt ; le panneau, téléporté, n'en fait pas partie. */
function rendreLaPageInerte(inerte: boolean): void {
    document.getElementById('__nuxt')?.toggleAttribute('inert', inerte)
}

// Au-delà de 900 px, l'en-tête desktop reprend la main : le menu se ferme.
let largeEcran: MediaQueryList | null = null
const surChangementDeLargeur = (requete: MediaQueryListEvent) => {
    if (requete.matches) {
        fermer()
    }
}

onMounted(() => {
    document.addEventListener('keydown', surTouche)
    largeEcran = window.matchMedia('(min-width: 900px)')
    largeEcran.addEventListener('change', surChangementDeLargeur)
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', surTouche)
    largeEcran?.removeEventListener('change', surChangementDeLargeur)
    // Filet de sécurité : la page ne doit jamais rester inerte.
    rendreLaPageInerte(false)
})

watch(ouvert, async (estOuvert) => {
    rendreLaPageInerte(estOuvert)

    if (estOuvert) {
        origineDuFocus = document.activeElement as HTMLElement | null
        await nextTick()
        focaliserQuandVisible(boutonFermer.value)
    }
    else {
        // La page redevient active au prochain rendu seulement.
        await nextTick()
        origineDuFocus?.focus()
        origineDuFocus = null
    }
})
</script>

<template>
    <!-- Rien à rendre côté serveur : le menu, fermé au chargement, ne sert
         qu'avec JavaScript. -->
    <ClientOnly>
        <Teleport to="body">
            <div class="menu-mobile drawer drawer-end">
                <!--
                    Case à cocher qui pilote le drawer de daisyUI, hors du
                    parcours au clavier : le bouton burger en tient lieu.
                -->
                <input
                    id="bascule-menu-mobile"
                    v-model="ouvert"
                    type="checkbox"
                    class="drawer-toggle"
                    tabindex="-1"
                    aria-hidden="true"
                >

                <div class="drawer-side z-50">
                    <!-- Voile cliquable : fermer en touchant à côté est le geste attendu. -->
                    <label
                        for="bascule-menu-mobile"
                        class="menu-mobile__voile drawer-overlay"
                        aria-hidden="true"
                        @click.prevent="fermer"
                    />

                    <div
                        id="menu-mobile"
                        ref="panneau"
                        class="menu-mobile__panneau flex min-h-full w-72 max-w-[85vw] flex-col items-end gap-6 bg-base-200 p-6"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Menu de navigation"
                    >
                        <button
                            ref="boutonFermer"
                            type="button"
                            class="menu-mobile__fermer btn btn-square btn-ghost"
                            aria-label="Fermer le menu"
                            @click="fermer"
                        >
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>

                        <nav class="w-full" aria-label="Navigation principale">
                            <ul class="menu menu-lg w-full gap-1 p-0">
                                <li v-for="lien in liens" :key="lien.chemin">
                                    <NuxtLink
                                        :to="lien.chemin"
                                        class="menu-mobile__lien font-display text-3xl"
                                        :class="{ 'menu-active': estActif(lien.chemin) }"
                                        :aria-current="estActif(lien.chemin) ? 'page' : undefined"
                                        @click="fermer"
                                    >
                                        {{ lien.libelle }}
                                    </NuxtLink>
                                </li>
                            </ul>
                        </nav>

                        <NuxtLink
                            to="/admin"
                            class="menu-mobile__admin btn btn-primary btn-block mt-auto font-mono uppercase tracking-wider"
                            @click="fermer"
                        >
                            Admin
                        </NuxtLink>
                    </div>
                </div>
            </div>
        </Teleport>
    </ClientOnly>
</template>
