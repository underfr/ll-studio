<script setup lang="ts">
/**
 * Panneau de navigation latéral mobile (maquette « Menu latéral · mobile »).
 *
 * Le panneau est rendu dans un <Teleport> vers <body> : il échappe ainsi au
 * contexte d'empilement de l'en-tête et peut couvrir la page sans dépendre
 * d'un z-index arbitraire.
 */
const ouvert = defineModel<boolean>('ouvert', { required: true })

const { liens, estActif } = useNavigationPublique()

const panneau = ref<HTMLElement | null>(null)
const boutonFermer = ref<HTMLButtonElement | null>(null)

function fermer(): void {
    ouvert.value = false
}

/**
 * Échap ferme le panneau, et Tab reste captif à l'intérieur : sans ça, le
 * focus clavier partirait derrière le voile, sur des liens invisibles.
 */
function surTouche(evenement: KeyboardEvent): void {
    if (!ouvert.value) {
        return
    }

    if (evenement.key === 'Escape') {
        evenement.preventDefault()
        fermer()

        return
    }

    if (evenement.key !== 'Tab' || !panneau.value) {
        return
    }

    const focusables = panneau.value.querySelectorAll<HTMLElement>(
        'a[href], button:not([disabled])',
    )

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

onMounted(() => document.addEventListener('keydown', surTouche))
onBeforeUnmount(() => document.removeEventListener('keydown', surTouche))

watch(ouvert, async (estOuvert) => {
    // Le fond ne doit pas défiler derrière le panneau.
    document.body.style.overflow = estOuvert ? 'hidden' : ''

    if (estOuvert) {
        await nextTick()
        boutonFermer.value?.focus()
    }
})

// Filet de sécurité : si le composant disparaît alors que le panneau est
// ouvert, le défilement de la page doit être rendu.
onBeforeUnmount(() => {
    document.body.style.overflow = ''
})
</script>

<template>
    <Teleport to="body">
        <Transition name="menu">
            <div v-if="ouvert" class="menu">
                <!-- Voile cliquable : fermer en touchant à côté est le geste attendu. -->
                <div class="menu__voile" @click="fermer" />

                <div
                    ref="panneau"
                    class="menu__panneau"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Menu de navigation"
                >
                    <button
                        ref="boutonFermer"
                        type="button"
                        class="menu__fermer"
                        aria-label="Fermer le menu"
                        @click="fermer"
                    >
                        &times;
                    </button>

                    <nav class="menu__nav" aria-label="Navigation principale">
                        <NuxtLink
                            v-for="lien in liens"
                            :key="lien.chemin"
                            :to="lien.chemin"
                            class="menu__lien"
                            :class="{ 'menu__lien--actif': estActif(lien.chemin) }"
                            :aria-current="estActif(lien.chemin) ? 'page' : undefined"
                            @click="fermer"
                        >
                            {{ lien.libelle }}
                        </NuxtLink>
                    </nav>

                    <NuxtLink to="/admin" class="menu__admin" @click="fermer">
                        Admin
                    </NuxtLink>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.menu {
    position: fixed;
    z-index: 50;
    inset: 0;
}

.menu__voile {
    position: absolute;
    inset: 0;
    background-color: var(--scrim);
}

.menu__panneau {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    display: flex;
    width: var(--largeur-panneau-mobile);
    max-width: 85vw;
    flex-direction: column;
    align-items: flex-end;
    padding: 26px 24px;
    border-left: 1px solid var(--bordure-default);
    background-color: var(--fond-surface-teintee);
}

.menu__fermer {
    padding: 0;
    border: 0;
    background: none;
    color: var(--texte-principal);
    cursor: pointer;
    font-family: var(--police-texte);
    font-size: 26px;
    line-height: 1;
}

.menu__nav {
    display: flex;
    width: 100%;
    flex-direction: column;
    gap: 4px;
    padding-top: 20px;
}

.menu__lien {
    padding: 8px 0;
    border-bottom: 1px solid var(--bordure-tenue);
    color: var(--texte-principal);
    font-family: var(--police-titre);
    font-size: 34px;
    font-weight: 500;
    line-height: 1;
}

.menu__lien--actif {
    color: var(--or-texte);
}

.menu__admin {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    /* Pousse le bouton en bas du panneau, comme sur la maquette. */
    margin-top: auto;
    padding: 14px;
    background-color: var(--or-remplissage);
    color: var(--texte-sur-or);
    font-family: var(--police-ui);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.menu-enter-active .menu__panneau,
.menu-leave-active .menu__panneau {
    transition: transform var(--transition-panneau);
}

.menu-enter-active .menu__voile,
.menu-leave-active .menu__voile {
    transition: opacity var(--transition-panneau);
}

.menu-enter-from .menu__panneau,
.menu-leave-to .menu__panneau {
    transform: translateX(100%);
}

.menu-enter-from .menu__voile,
.menu-leave-to .menu__voile {
    opacity: 0;
}
</style>
