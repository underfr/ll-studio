<script setup lang="ts">
/**
 * Gabarit du back-office, construit sur le « drawer » de daisyUI (issue #67).
 *
 * À partir de 1024 px, le panneau de navigation reste ouvert sur le côté.
 * En dessous, il s'ouvre par le bouton de la barre supérieure.
 *
 * daisyUI fait le reste en CSS : panneau masqué (et donc hors du parcours
 * au clavier) tant qu'il est fermé, voile, défilement de la page bloqué
 * pendant l'ouverture. Ce qu'il ne fait pas est ajouté ici : un vrai bouton
 * à la place de son <label>, que le clavier n'atteint pas, le focus placé à
 * l'ouverture et gardé dans le panneau, Échap pour fermer et le focus rendu
 * au bouton, le reste de la page rendu inerte tant que le panneau couvre
 * l'écran.
 */
const { liens, estActif } = useNavigationAdmin()
const { compte, deconnecter } = useSession()
const route = useRoute()

const menuOuvert = ref(false)
const deconnexionEnCours = ref(false)
const boutonMenu = ref<HTMLButtonElement | null>(null)
const boutonFermer = ref<HTMLButtonElement | null>(null)
const panneau = ref<HTMLElement | null>(null)

useHead({
    titleTemplate: titre => (titre ? `${titre} · Administration LL Studio` : 'Administration LL Studio'),
    // Le robots.txt écarte déjà /admin, mais il n'est qu'une consigne :
    // une page découverte par un lien externe pourrait quand même être
    // indexée sans cette balise.
    meta: [{ name: 'robots', content: 'noindex, nofollow' }],
})

async function fermerMenu(rendreLeFocus = true): Promise<void> {
    if (!menuOuvert.value) {
        return
    }

    menuOuvert.value = false

    if (rendreLeFocus) {
        // Le bouton est dans le contenu, inerte jusqu'au prochain rendu.
        await nextTick()
        boutonMenu.value?.focus()
    }
}

watch(menuOuvert, async (ouvert) => {
    if (ouvert) {
        await nextTick()
        focaliserQuandVisible(boutonFermer.value)
    }
})

// Naviguer ferme le panneau, y compris par les boutons précédent et suivant
// du navigateur. Un clic sur la rubrique déjà affichée ne change pas de
// route : les liens du panneau le ferment donc aussi eux-mêmes.
watch(() => route.fullPath, () => fermerMenu(false))

function surTouche(evenement: KeyboardEvent): void {
    if (!menuOuvert.value) {
        return
    }

    if (evenement.key === 'Escape') {
        evenement.preventDefault()
        fermerMenu()

        return
    }

    piegerTabulation(evenement, panneau.value)
}

/*
 * Si la fenêtre s'élargit panneau ouvert, il passe en colonne fixe : on le
 * referme, sans quoi le contenu resterait inerte derrière un panneau qui ne
 * le couvre plus.
 */
let largeEcran: MediaQueryList | null = null
const surChangementDeLargeur = (requete: MediaQueryListEvent) => {
    if (requete.matches) {
        fermerMenu(false)
    }
}

onMounted(() => {
    document.addEventListener('keydown', surTouche)
    largeEcran = window.matchMedia('(min-width: 1024px)')
    largeEcran.addEventListener('change', surChangementDeLargeur)
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', surTouche)
    largeEcran?.removeEventListener('change', surChangementDeLargeur)
})

async function seDeconnecter(): Promise<void> {
    deconnexionEnCours.value = true
    await deconnecter()
    await navigateTo('/admin/connexion')
}
</script>

<template>
    <div class="admin drawer min-h-screen lg:drawer-open">
        <!--
            Case à cocher qui pilote le drawer de daisyUI. Elle est hors du
            parcours au clavier et des technologies d'assistance : le bouton
            de la barre supérieure en tient lieu.
        -->
        <input
            id="menu-admin"
            v-model="menuOuvert"
            type="checkbox"
            class="drawer-toggle"
            tabindex="-1"
            aria-hidden="true"
        >

        <div class="drawer-content flex min-w-0 flex-col" :inert="menuOuvert || undefined">
            <a class="lien-evitement" href="#contenu">Aller au contenu</a>

            <header class="navbar border-b border-base-300 bg-base-200 px-4 lg:hidden">
                <div class="flex-1">
                    <AppLogo cible="/admin" taille="mobile" libelle="LL Studio, tableau de bord de l'administration" />
                </div>

                <button
                    ref="boutonMenu"
                    type="button"
                    class="btn btn-square btn-ghost"
                    aria-controls="panneau-admin"
                    :aria-expanded="menuOuvert"
                    aria-label="Ouvrir le menu"
                    @click="menuOuvert = true"
                >
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </header>

            <main id="contenu" class="contenu flex-1 px-4 py-8 focus:outline-none lg:px-10 lg:py-12" tabindex="-1">
                <slot />
            </main>
        </div>

        <div class="drawer-side z-40">
            <label for="menu-admin" class="drawer-overlay" aria-hidden="true" />

            <aside
                id="panneau-admin"
                ref="panneau"
                class="barre flex min-h-full w-72 flex-col gap-8 border-r border-base-300 bg-base-200 p-5"
                aria-label="Menu de l'administration"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-2">
                        <AppLogo cible="/admin" libelle="LL Studio, tableau de bord de l'administration" @click="fermerMenu(false)" />
                        <p class="font-mono text-xs uppercase tracking-[0.3em] text-base-content/50">
                            Administration
                        </p>
                    </div>

                    <button
                        ref="boutonFermer"
                        type="button"
                        class="btn btn-square btn-ghost btn-sm lg:hidden"
                        aria-label="Fermer le menu"
                        @click="fermerMenu()"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>

                <nav class="nav" aria-label="Navigation de l'administration">
                    <ul class="menu w-full gap-1 p-0">
                        <li v-for="lien in liens" :key="lien.chemin">
                            <NuxtLink
                                :to="lien.chemin"
                                class="nav__lien"
                                :class="{ 'menu-active': estActif(lien.chemin) }"
                                :aria-current="estActif(lien.chemin) ? 'page' : undefined"
                                @click="fermerMenu(false)"
                            >
                                {{ lien.libelle }}
                            </NuxtLink>
                        </li>
                    </ul>
                </nav>

                <div class="barre__pied mt-auto flex flex-col items-start gap-2 border-t border-base-300 pt-5">
                    <p v-if="compte" class="barre__compte text-sm font-medium">
                        {{ compte.fullName }}
                    </p>

                    <button
                        type="button"
                        class="barre__deconnexion btn btn-ghost btn-sm -ml-3"
                        :disabled="deconnexionEnCours"
                        @click="seDeconnecter"
                    >
                        Se déconnecter
                    </button>

                    <NuxtLink to="/" class="barre__site link link-hover link-secondary text-sm">
                        Voir le site
                    </NuxtLink>
                </div>
            </aside>
        </div>
    </div>
</template>
