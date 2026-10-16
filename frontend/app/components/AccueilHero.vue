<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Bandeau d'ouverture, sur le « hero » de daisyUI. La photographie de fond
 * est la plus récente publiée : l'accueil se renouvelle à chaque ajout, sans
 * intervention.
 */
defineProps<{ photo?: Photo }>()
</script>

<template>
    <section class="ouverture hero relative min-h-[660px] items-end justify-items-start overflow-hidden bg-neutral bureau:min-h-[820px]">
        <img
            v-if="photo"
            class="ouverture__photo absolute inset-0 size-full object-cover"
            :src="urlMedia(photo.contentUrl)"
            :alt="photo.alt"
            fetchpriority="high"
            decoding="async"
        >

        <!--
            Le voile diffère entre les deux maquettes : sur mobile le texte
            occupe une plus grande part de l'image, le dégradé y est donc plus
            dense en bas. La photographie vient de l'API et peut être très
            lumineuse : le contraste réel sera mesuré par l'audit (#33).
        -->
        <div
            class="ouverture__voile absolute inset-0 bg-linear-to-b from-neutral/40 via-neutral/0 via-40% to-neutral/90 bureau:from-neutral/55 bureau:via-neutral/5 bureau:via-35% bureau:to-neutral/85"
        />

        <div class="ouverture__copy hero-content relative w-full max-w-none flex-col items-start justify-end px-5.5 pb-10 bureau:px-10 bureau:pb-14">
            <p class="font-mono text-[clamp(10px,1vw,12px)] uppercase tracking-[clamp(3px,0.4vw,5px)] text-primary">
                Photographe · Multimédia
            </p>

            <h1 class="max-w-[14ch] font-display text-[clamp(52px,8vw,115px)] font-semibold leading-[0.93] tracking-[-2px] text-neutral-content">
                La lumière raconte ce que les mots taisent.
            </h1>

            <p class="max-w-[520px] text-[clamp(15px,1.2vw,17px)] leading-relaxed text-neutral-content/90">
                Nature, spectacle, espace, automobile, événements : je capture l'instant
                où la scène bascule dans le sublime.
            </p>

            <!-- Sur mobile la maquette empile les boutons pleine largeur. -->
            <div class="mt-1.5 flex w-full flex-col gap-3.5 tablette:w-auto tablette:flex-row">
                <NuxtLink to="/galerie" class="btn btn-primary">Voir la galerie</NuxtLink>
                <NuxtLink
                    to="/contact"
                    class="btn btn-outline border-neutral-content/50 text-neutral-content hover:border-neutral-content hover:bg-neutral-content/10 focus-visible:border-neutral-content focus-visible:bg-neutral-content/10 active:bg-neutral-content/20"
                >
                    Me réserver
                </NuxtLink>
            </div>
        </div>
    </section>
</template>
