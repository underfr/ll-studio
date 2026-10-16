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
            La photographie vient de l'API et peut être très lumineuse. Le
            voile est donc déjà dense là où commence le texte, au tiers de la
            hauteur : le surtitre doré, le plus fragile, y garde 4,5:1 sur les
            douze photos de démonstration, mobile comme bureau (issue #33).
            Les maquettes laissaient la photo à nu à cet endroit.
        -->
        <div
            class="ouverture__voile absolute inset-0 bg-linear-to-b from-neutral/30 via-neutral/80 via-35% to-neutral/90 bureau:from-neutral/50 bureau:via-neutral/75 bureau:to-neutral/85"
        />

        <div class="ouverture__copy hero-content relative w-full max-w-none flex-col items-start justify-end px-5.5 pb-10 bureau:px-10 bureau:pb-14">
            <p class="font-mono text-[clamp(11px,1vw,12px)] uppercase tracking-[clamp(3px,0.4vw,5px)] text-accent">
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
                <!-- Sur la photo, l'anneau de focus passe en crème : l'or
                     profond des boutons n'y ressortirait pas. -->
                <NuxtLink to="/galerie" class="btn btn-primary focus-visible:outline-neutral-content">Voir la galerie</NuxtLink>
                <NuxtLink
                    to="/contact"
                    class="btn btn-outline border-neutral-content/50 text-neutral-content hover:border-neutral-content hover:bg-neutral-content/10 focus-visible:border-neutral-content focus-visible:bg-neutral-content/10 focus-visible:outline-neutral-content active:bg-neutral-content/20"
                >
                    Me réserver
                </NuxtLink>
            </div>
        </div>
    </section>
</template>
