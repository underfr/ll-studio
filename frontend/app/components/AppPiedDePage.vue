<script setup lang="ts">
/**
 * Pied de page commun à tout le site public, sur le « footer » de daisyUI.
 *
 * Il ne figure pas dans la maquette Figma, qui s'arrête sur un appel à
 * l'action pleine largeur. Il en reprend le registre : fond teinté, filet
 * supérieur, libellés en Space Mono capitales espacées, accent or.
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
    <footer class="pied border-t border-base-300 bg-base-200">
        <div class="mx-auto max-w-360 px-5.5 pt-12 pb-9 bureau:px-10">
            <div class="footer items-end gap-7 tablette:footer-horizontal">
                <aside class="flex flex-col gap-2.5">
                    <AppLogo taille="desktop" />
                    <p class="surtitre text-[10px]">Photographe · Multimédia</p>
                </aside>

                <nav class="flex flex-wrap gap-4 tablette:justify-self-end tablette:gap-6" aria-label="Navigation de pied de page">
                    <NuxtLink
                        v-for="lien in liens"
                        :key="lien.chemin"
                        :to="lien.chemin"
                        class="pied__lien link link-hover font-mono text-[11px] uppercase tracking-[2px] text-base-content/70 aria-[current=page]:text-base-content"
                        :aria-current="estActif(lien.chemin) ? 'page' : undefined"
                    >
                        {{ lien.libelle }}
                    </NuxtLink>
                </nav>
            </div>

            <!-- Filet plus ténu que celui du haut : il sépare sans découper. -->
            <div class="footer mt-8 items-center gap-4 border-t border-base-content/10 pt-5 tablette:footer-horizontal">
                <p class="font-mono text-[10px] tracking-[1px] text-base-content/60">
                    © {{ annee }} LL Studio. Tous droits réservés.
                </p>

                <a
                    v-if="lienInstagram"
                    class="pied__social link link-hover link-secondary flex items-center gap-2 font-mono text-[11px] tablette:justify-self-end"
                    :href="lienInstagram"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <AppIconeInstagram />
                    <span>{{ identifiantInstagram }}</span>
                </a>
                <!-- Tant que le compte n'existe pas, le bloc reste lisible mais inerte. -->
                <span
                    v-else
                    class="pied__social pied__social--inactif flex items-center gap-2 font-mono text-[11px] text-base-content/60 tablette:justify-self-end"
                >
                    <AppIconeInstagram />
                    <span>{{ identifiantInstagram }}</span>
                </span>
            </div>
        </div>
    </footer>
</template>
