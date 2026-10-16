<script setup lang="ts">
/**
 * Gabarit du back-office : barre latérale de navigation et zone de contenu.
 *
 * Le Figma ne contient pas d'écran d'administration. Le gabarit reprend donc
 * les jetons du design system, dont la surface teintée que la maquette
 * réserve aux barres latérales et aux panneaux.
 *
 * Sous 900 px, la barre latérale devient une barre supérieure dont la
 * navigation défile horizontalement : toutes les rubriques restent à un
 * geste, sans panneau à ouvrir ni piège de focus à maintenir.
 */
const { liens, estActif } = useNavigationAdmin()
const { compte, deconnecter } = useSession()
const deconnexionEnCours = ref(false)

async function seDeconnecter(): Promise<void> {
    deconnexionEnCours.value = true
    await deconnecter()
    await navigateTo('/admin/connexion')
}

const route = useRoute()
const navigation = ref<HTMLElement | null>(null)

/**
 * Sur mobile, la rubrique active peut se trouver hors de la partie visible
 * de la barre défilante, par exemple « Messages » à l'arrivée sur la page.
 * On la ramène dans le champ. Sur desktop, la liste tient en entier et
 * l'appel ne déplace rien.
 */
function montrerRubriqueActive(): void {
    navigation.value
        ?.querySelector<HTMLElement>('.nav__lien--actif')
        ?.scrollIntoView({ block: 'nearest', inline: 'nearest' })
}

onMounted(montrerRubriqueActive)
watch(() => route.path, () => nextTick(montrerRubriqueActive))

useHead({
    titleTemplate: titre => (titre ? `${titre} · Administration LL Studio` : 'Administration LL Studio'),
    // Le robots.txt écarte déjà /admin, mais il n'est qu'une consigne :
    // une page découverte par un lien externe pourrait quand même être
    // indexée sans cette balise.
    meta: [{ name: 'robots', content: 'noindex, nofollow' }],
})
</script>

<template>
    <div class="admin">
        <a class="lien-evitement" href="#contenu">Aller au contenu</a>

        <!--
            L'ordre du DOM (marque, navigation, pied) est celui du parcours au
            clavier. Seule la grille déplace le pied, en haut à droite sur
            mobile et au bas de la barre sur desktop.
        -->
        <aside class="barre">
            <div class="barre__marque">
                <AppLogo cible="/admin" libelle="LL Studio, tableau de bord de l'administration" />
                <p class="barre__rubrique">Administration</p>
            </div>

            <nav ref="navigation" class="nav" aria-label="Navigation de l'administration">
                <NuxtLink
                    v-for="lien in liens"
                    :key="lien.chemin"
                    :to="lien.chemin"
                    class="nav__lien"
                    :class="{ 'nav__lien--actif': estActif(lien.chemin) }"
                    :aria-current="estActif(lien.chemin) ? 'page' : undefined"
                >
                    {{ lien.libelle }}
                </NuxtLink>
            </nav>

            <div class="barre__pied">
                <p v-if="compte" class="barre__compte">
                    {{ compte.fullName }}
                </p>

                <button
                    type="button"
                    class="barre__deconnexion"
                    :disabled="deconnexionEnCours"
                    @click="seDeconnecter"
                >
                    Se déconnecter
                </button>

                <NuxtLink to="/" class="barre__site">
                    Voir le site
                </NuxtLink>
            </div>
        </aside>

        <main id="contenu" class="contenu" tabindex="-1">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.admin {
    display: flex;
    min-height: 100vh;
    flex-direction: column;
}

/* --- Barre : supérieure sur mobile, latérale à partir de 900 px --- */

.barre {
    display: grid;
    grid-template-areas:
        "marque pied"
        "nav nav";
    grid-template-columns: 1fr auto;
    gap: 12px 16px;
    padding: 14px 18px 0;
    border-bottom: 1px solid var(--bordure-default);
    background-color: var(--fond-surface-teintee);
}

.barre__marque {
    display: flex;
    flex-direction: column;
    gap: 6px;
    grid-area: marque;
}

.barre__rubrique {
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: 9px;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.barre__pied {
    display: flex;
    align-items: center;
    gap: 16px;
    grid-area: pied;
}

/* Le nom du compte est réservé à la barre latérale : sur mobile, la place
   manque et le tableau de bord salue déjà la personne connectée. */
.barre__compte {
    display: none;
    color: var(--texte-principal);
    font-size: 14px;
}

.barre__deconnexion {
    padding: 0;
    border: 0;
    background: none;
    color: var(--texte-secondaire);
    cursor: pointer;
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: color var(--transition-courte);
}

.barre__deconnexion:hover {
    color: var(--terracotta);
}

.barre__deconnexion:disabled {
    cursor: progress;
}

.barre__site {
    color: var(--or-texte);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: color var(--transition-courte);
}

.barre__site:hover {
    color: var(--texte-principal);
}

/* --- Navigation --- */

.nav {
    display: flex;
    gap: 4px;
    grid-area: nav;
    /* Sur mobile, les rubriques défilent dans la barre plutôt que de faire
       déborder la page entière. Les marges négatives laissent le défilement
       aller jusqu'aux bords de l'écran. */
    overflow-x: auto;
    margin-inline: -18px;
    padding: 0 18px 10px;
    /* La rubrique ramenée dans le champ garde la même marge que la première. */
    scroll-padding-inline: 18px;
    scrollbar-width: none;
}

.nav__lien {
    flex-shrink: 0;
    padding: 10px 12px;
    color: var(--texte-secondaire);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    white-space: nowrap;
    transition: color var(--transition-courte), background-color var(--transition-courte);
}

.nav__lien:hover {
    color: var(--texte-principal);
}

.nav__lien--actif {
    background-color: var(--voile-leger);
    color: var(--or-texte);
}

/* --- Contenu --- */

.contenu {
    flex: 1;
    /* Sans ce plancher, un tableau large pousserait la colonne au-delà de
       l'écran au lieu de défiler dans son propre conteneur. */
    min-width: 0;
}

/* Le conteneur reçoit le focus depuis le lien d'évitement, sans anneau. */
.contenu:focus {
    outline: none;
}

@media (min-width: 900px) {
    .admin {
        flex-direction: row;
    }

    .barre {
        position: sticky;
        top: 0;
        width: 248px;
        height: 100vh;
        flex-shrink: 0;
        grid-template-areas:
            "marque"
            "nav"
            "pied";
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr auto;
        gap: 40px;
        padding: 28px 20px;
        border-right: 1px solid var(--bordure-default);
        border-bottom: 0;
    }

    .barre__pied {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding-top: 20px;
        border-top: 1px solid var(--bordure-tenue);
    }

    .barre__compte {
        display: block;
    }

    .nav {
        flex-direction: column;
        align-self: start;
        overflow-x: visible;
        margin-inline: 0;
        padding: 0;
    }

    .nav__lien {
        padding: 11px 14px;
    }
}
</style>
