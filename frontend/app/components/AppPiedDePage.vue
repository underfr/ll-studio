<script setup lang="ts">
/**
 * Pied de page commun à tout le site public.
 *
 * Il ne figure pas dans la maquette Figma, qui s'arrête sur un appel à
 * l'action pleine largeur. Il en reprend donc les partis pris : fond teinté,
 * filet supérieur, libellés en Space Mono capitales espacées, accent or.
 */
const { liens, estActif } = useNavigationPublique()

/**
 * Compte Instagram du studio. Le profil n'existe pas encore : tant que cette
 * constante vaut null, le bloc est affiché sans être cliquable, plutôt que de
 * pointer vers un lien mort. Renseigner l'URL suffit à l'activer.
 */
const lienInstagram: string | null = null
const identifiantInstagram = '@ll.studio'

const annee = new Date().getFullYear()
</script>

<template>
    <footer class="pied">
        <div class="pied__contenu">
            <div class="pied__haut">
                <div class="pied__marque">
                    <AppLogo taille="desktop" />
                    <p class="pied__accroche">Photographe · Multimédia</p>
                </div>

                <nav class="pied__nav" aria-label="Navigation de pied de page">
                    <NuxtLink
                        v-for="lien in liens"
                        :key="lien.chemin"
                        :to="lien.chemin"
                        class="pied__lien"
                        :aria-current="estActif(lien.chemin) ? 'page' : undefined"
                    >
                        {{ lien.libelle }}
                    </NuxtLink>
                </nav>
            </div>

            <div class="pied__bas">
                <p class="pied__mentions">
                    © {{ annee }} LL Studio. Tous droits réservés.
                </p>

                <a
                    v-if="lienInstagram"
                    class="pied__social"
                    :href="lienInstagram"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <AppIconeInstagram />
                    <span>{{ identifiantInstagram }}</span>
                </a>
                <span v-else class="pied__social pied__social--inactif">
                    <AppIconeInstagram />
                    <span>{{ identifiantInstagram }}</span>
                </span>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.pied {
    border-top: 1px solid var(--bordure-default);
    background-color: var(--fond-surface-teintee);
}

.pied__contenu {
    max-width: var(--largeur-max);
    margin-inline: auto;
    padding: 48px var(--gouttiere) 36px;
}

.pied__haut {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 28px;
}

.pied__marque {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.pied__accroche {
    color: var(--or-texte);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.pied__nav {
    display: flex;
    flex-wrap: wrap;
    gap: 22px;
}

.pied__lien {
    color: var(--texte-secondaire);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: color var(--transition-courte);
}

.pied__lien:hover,
.pied__lien[aria-current='page'] {
    color: var(--texte-principal);
}

.pied__bas {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    /* Filet plus ténu que celui du haut : il sépare sans découper. */
    margin-top: 32px;
    padding-top: 22px;
    border-top: 1px solid var(--bordure-tenue);
}

.pied__mentions {
    color: var(--texte-tertiaire);
    font-family: var(--police-ui);
    font-size: 10px;
    letter-spacing: 1px;
}

.pied__social {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--or-texte);
    font-family: var(--police-ui);
    font-size: 11px;
    letter-spacing: 1px;
    transition: color var(--transition-courte);
}

.pied__social:hover {
    color: var(--texte-principal);
}

/* Tant que le compte n'existe pas, le bloc reste lisible mais inerte. */
.pied__social--inactif {
    color: var(--texte-tertiaire);
    cursor: default;
}

.pied__social--inactif:hover {
    color: var(--texte-tertiaire);
}

@media (max-width: 599px) {
    .pied__haut {
        align-items: flex-start;
    }

    .pied__nav {
        gap: 16px;
    }
}
</style>
