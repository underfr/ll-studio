<script setup lang="ts">
/**
 * En-tête public (maquettes « Navbar / Desktop » et « Navbar / Mobile »).
 *
 * Les deux variantes coexistent dans le DOM et sont permutées en CSS plutôt
 * que par détection de largeur en JavaScript : le rendu serveur est ainsi
 * identique au rendu client, sans scintillement au premier affichage.
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
    <header class="entete">
        <!-- Variante desktop -->
        <div class="entete__barre entete__barre--desktop">
            <AppLogo taille="desktop" />

            <nav class="nav" aria-label="Navigation principale">
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

                <span class="nav__separateur" aria-hidden="true" />

                <!--
                    Le sélecteur de langue figure sur toutes les maquettes mais
                    aucune issue ne couvre l'internationalisation : il est rendu
                    inerte et annoncé comme indisponible tant que l'i18n n'est
                    pas au programme.
                -->
                <button type="button" class="nav__langue" disabled title="Version anglaise à venir">
                    EN
                </button>

                <NuxtLink to="/admin" class="nav__admin">
                    Admin
                </NuxtLink>
            </nav>
        </div>

        <!-- Variante mobile -->
        <div class="entete__barre entete__barre--mobile">
            <AppLogo taille="mobile" />

            <div class="actions">
                <button type="button" class="actions__langue" disabled title="Version anglaise à venir">
                    EN
                </button>

                <button
                    type="button"
                    class="burger"
                    :aria-expanded="menuOuvert"
                    aria-controls="menu-mobile"
                    aria-label="Ouvrir le menu"
                    @click="menuOuvert = true"
                >
                    <span class="burger__barre" />
                    <span class="burger__barre" />
                    <span class="burger__barre" />
                </button>
            </div>
        </div>

        <AppMenuMobile id="menu-mobile" v-model:ouvert="menuOuvert" />
    </header>
</template>

<style scoped>
.entete {
    position: sticky;
    z-index: 20;
    top: 0;
    border-bottom: 1px solid var(--bordure-default);
    /* La maquette annonce un fond crème à 86 % : l'en-tête laisse deviner
       la photo qui défile dessous. */
    backdrop-filter: blur(12px);
    background-color: rgb(250 247 241 / 86%);
}

.entete__barre {
    display: flex;
    max-width: var(--largeur-max);
    align-items: center;
    justify-content: space-between;
    margin-inline: auto;
}

.entete__barre--desktop {
    display: none;
    padding: 18px 40px;
}

.entete__barre--mobile {
    padding: 14px 18px;
}

@media (min-width: 900px) {
    .entete__barre--desktop {
        display: flex;
    }

    .entete__barre--mobile {
        display: none;
    }
}

/* --- Navigation desktop --- */

.nav {
    display: flex;
    align-items: center;
    gap: 6px;
}

.nav__lien {
    padding: 9px 14px;
    color: var(--texte-secondaire);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: color var(--transition-courte), background-color var(--transition-courte);
}

.nav__lien:hover {
    color: var(--texte-principal);
}

.nav__lien--actif {
    background-color: var(--voile-leger);
    color: var(--or-texte);
}

.nav__separateur {
    width: 1px;
    height: 22px;
    background-color: var(--bordure-discrete);
}

.nav__langue {
    padding: 8px 12px;
    border: 1px solid var(--bordure-discrete);
    background: none;
    color: var(--or-texte);
    cursor: not-allowed;
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.nav__admin {
    padding: 9px 14px;
    background-color: var(--or-remplissage);
    color: var(--texte-sur-or);
    font-family: var(--police-ui);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* --- Actions mobile --- */

.actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.actions__langue {
    padding: 6px 9px;
    border: 1px solid var(--bordure-discrete);
    background: none;
    color: var(--or-texte);
    cursor: not-allowed;
    font-family: var(--police-ui);
    font-size: 10px;
    text-transform: uppercase;
}

.burger {
    display: flex;
    flex-direction: column;
    gap: 4px;
    /* Le dessin ne fait que 22 × 14 px. Le padding porte la cible tactile à
       44 × 44, seuil recommandé par le critère WCAG 2.5.5. Il est là pour
       la main, pas pour l'œil. */
    padding: 15px 11px;
    /* Compense le padding horizontal pour que les barres restent alignées
       sur la marge droite de la maquette. */
    margin-right: -11px;
    border: 0;
    background: none;
    cursor: pointer;
}

.burger__barre {
    width: 22px;
    height: 2px;
    background-color: var(--texte-principal);
}
</style>
