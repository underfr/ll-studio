<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Section de présentation. Les chiffres sont des « stats » de daisyUI. Le
 * compteur « Univers » vient des catégories de l'API plutôt que d'être figé :
 * il reste juste quand le photographe en ajoute une.
 */
const props = defineProps<{
    photo?: Photo
    nombreUnivers: number
}>()

const chiffres = computed(() => [
    { libelle: 'Séances', valeur: '120+' },
    { libelle: 'Univers', valeur: String(props.nombreUnivers) },
    { libelle: 'Ans', valeur: '8' },
])
</script>

<template>
    <section class="apropos grid border-t border-base-300 bureau:grid-cols-2">
        <div class="order-2 flex min-h-[420px] flex-col justify-center gap-9 px-5.5 py-14 bureau:order-1 bureau:px-10 bureau:py-20">
            <div class="flex flex-col gap-4.5">
                <p class="surtitre">À propos</p>

                <h2 class="max-w-[16ch] font-display text-[clamp(34px,3.6vw,52px)] leading-[1.05] tracking-[-1px]">
                    Chaque cadrage est une décision, jamais un hasard.
                </h2>

                <p class="max-w-[440px] text-[clamp(15px,1.1vw,16px)] leading-relaxed text-base-content/70">
                    Basé entre la ville et la nuit, je travaille l'obscurité comme une
                    matière, des aurores boréales aux fosses de concert, de la Voie lactée
                    aux carrosseries chromées. Mon obsession : la texture de la lumière.
                </p>
            </div>

            <dl class="apropos__chiffres stats stats-horizontal w-fit bg-base-100 shadow-sm">
                <div v-for="chiffre in chiffres" :key="chiffre.libelle" class="stat px-5 py-3">
                    <dt class="stat-title font-mono text-[10px] uppercase tracking-[2px]">{{ chiffre.libelle }}</dt>
                    <dd class="stat-value font-display font-medium text-secondary">{{ chiffre.valeur }}</dd>
                </div>
            </dl>
        </div>

        <div class="apropos__visuel order-1 min-h-[260px] bureau:order-2 bureau:min-h-[420px] bureau:border-l bureau:border-base-300">
            <img
                v-if="photo"
                class="size-full object-cover"
                :src="urlMedia(photo.contentUrl)"
                :alt="photo.alt"
                loading="lazy"
                decoding="async"
            >
        </div>
    </section>
</template>
