#!/bin/sh
set -e

# En dev les sources sont montées depuis l'hôte : vendor/ vit dans un volume
# nommé et doit donc être (ré)installé au premier démarrage du conteneur.
if [ "$1" = "php-fpm" ] && [ ! -f vendor/autoload_runtime.php ]; then
    echo "[entrypoint] vendor/ absent — installation des dépendances Composer…"
    composer install --prefer-dist --no-interaction --no-progress
fi

# La paire de clés JWT n'est pas versionnée (config/jwt/*.pem est ignoré) :
# on la génère au premier démarrage pour qu'un clone frais soit utilisable.
if [ "$1" = "php-fpm" ] && [ ! -f config/jwt/private.pem ]; then
    echo "[entrypoint] génération de la paire de clés JWT…"
    php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
fi

mkdir -p var/cache var/log
chown -R www-data:www-data var 2>/dev/null || true

exec docker-php-entrypoint "$@"
