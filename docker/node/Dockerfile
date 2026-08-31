# syntax=docker/dockerfile:1

###############################################################################
# base — Node 24 + pnpm (via corepack)
###############################################################################
FROM node:24-alpine AS base

RUN corepack enable && corepack prepare pnpm@10 --activate

ENV PNPM_HOME=/pnpm \
    PATH=/pnpm:$PATH

WORKDIR /app

###############################################################################
# dev — serveur de développement Nuxt avec HMR
###############################################################################
FROM base AS dev

ENV NODE_ENV=development \
    HOST=0.0.0.0 \
    PORT=3000

EXPOSE 3000

# node_modules vit dans un volume nommé : on (ré)installe au démarrage
CMD ["sh", "-c", "pnpm install --prefer-offline --store-dir=/pnpm/store && pnpm dev --host 0.0.0.0 --port 3000"]

###############################################################################
# build — génère la sortie Nitro (.output)
###############################################################################
FROM base AS build

COPY frontend/package.json frontend/pnpm-lock.yaml frontend/pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

COPY frontend/ ./
RUN pnpm build

###############################################################################
# prod — serveur Nitro autonome, sans dépendances de build
###############################################################################
FROM node:24-alpine AS prod

ENV NODE_ENV=production \
    HOST=0.0.0.0 \
    PORT=3000

WORKDIR /app

COPY --from=build /app/.output ./.output

USER node
EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]
