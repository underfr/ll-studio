#!/bin/sh
set -e

if [ "$1" = "php-fpm" ]; then

    # 1. Configuration Symfony. Le dépôt ne contient que .env.example : le
    #    fichier .env effectif est dérivé ici et reste hors du dépôt.
    if [ ! -f .env ]; then
        echo "[entrypoint] .env absent — copie depuis .env.example…"
        cp .env.example .env
    fi

    # 2. Secrets propres à cette installation, écrits dans .env.local, qui
    #    n'est pas versionné. Chaque clone génère donc les siens : aucun
    #    secret ne transite par git.
    if ! grep -qs '^APP_SECRET=..*' .env.local; then
        echo "[entrypoint] génération d'APP_SECRET…"
        printf 'APP_SECRET=%s\n' "$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')" >> .env.local
    fi

    if ! grep -qs '^JWT_PASSPHRASE=..*' .env.local; then
        echo "[entrypoint] génération de JWT_PASSPHRASE…"
        printf 'JWT_PASSPHRASE=%s\n' "$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')" >> .env.local
        # La passphrase change : les clés existantes ne seraient plus
        # déchiffrables, on les régénère à l'étape 4.
        rm -f config/jwt/private.pem config/jwt/public.pem
    fi

    # 3. En dev les sources sont montées depuis l'hôte : vendor/ vit dans un
    #    volume nommé et doit être (ré)installé au premier démarrage.
    if [ ! -f vendor/autoload_runtime.php ]; then
        echo "[entrypoint] vendor/ absent — installation des dépendances Composer…"
        composer install --prefer-dist --no-interaction --no-progress
    fi

    # 4. Paire de clés JWT, elle aussi hors du dépôt (config/jwt/*.pem).
    if [ ! -f config/jwt/private.pem ]; then
        echo "[entrypoint] génération de la paire de clés JWT…"
        php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
    fi
fi

mkdir -p var/cache var/log
chown -R www-data:www-data var 2>/dev/null || true

exec docker-php-entrypoint "$@"
