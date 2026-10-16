<script setup lang="ts">
/**
 * En-tête public, sur la « navbar » de daisyUI.
 *
 * Les variantes desktop et mobile coexistent dans le DOM et sont permutées
 * en CSS à 900 px plutôt que par détection de largeur en JavaScript : le
 * rendu serveur est ainsi identique au rendu client, sans scintillement.
 */
const { liens, estActif } = useNavigationPublique()

const menuOuvert = ref(false)
const route = useRoute()

// Naviguer ferme le panneau, y compris via les boutons précédent/suivant.
watch(() => route.fullPath, () => {
    menuOuvert.value = false
})
</script>

<template>
    <header class="entete sticky top-0 z-20 border-b border-base-300 bg-base-100/85 backdrop-blur-md">
        <!-- Variante desktop -->
        <div class="navbar mx-auto hidden max-w-360 px-10 py-3 bureau:flex">
            <div class="flex-1">
                <AppLogo taille="desktop" />
            </div>

            <nav class="nav flex items-center gap-2" aria-label="Navigation principale">
                <ul class="menu menu-horizontal gap-1 p-0">
                    <li v-for="lien in liens" :key="lien.chemin">
                        <NuxtLink
                            :to="lien.chemin"
                            class="nav__lien font-mono text-[11px] uppercase tracking-[2px]"
                            :class="{ 'menu-active': estActif(lien.chemin) }"
                            :aria-current="estActif(lien.chemin) ? 'page' : undefined"
                        >
                            {{ lien.libelle }}
                        </NuxtLink>
                    </li>
                </ul>

                <span class="mx-1 h-6 w-px bg-base-content/20" aria-hidden="true" />

                <!--
                    Le sélecteur de langue figure sur toutes les maquettes mais
                    aucune issue ne couvre l'internationalisation : il est rendu
                    inerte et annoncé comme indisponible (issue #50).
                -->
                <button type="button" class="btn btn-outline btn-secondary btn-sm font-mono" disabled title="Version anglaise à venir">
                    EN
                </button>

                <NuxtLink to="/admin" class="nav__admin btn btn-primary btn-sm font-mono uppercase tracking-wider">
                    Admin
                </NuxtLink>
            </nav>
        </div>

        <!-- Variante mobile -->
        <div class="navbar min-h-0 px-4 py-2 bureau:hidden">
            <div class="flex-1">
                <AppLogo taille="mobile" />
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-outline btn-secondary btn-xs font-mono" disabled title="Version anglaise à venir">
                    EN
                </button>

                <button
                    type="button"
                    class="burger btn btn-square btn-ghost"
                    :aria-expanded="menuOuvert"
                    aria-controls="menu-mobile"
                    aria-label="Ouvrir le menu"
                    @click="menuOuvert = true"
                >
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>
        </div>

        <AppMenuMobile v-model:ouvert="menuOuvert" />
    </header>
</template>
