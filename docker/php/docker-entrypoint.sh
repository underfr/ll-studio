#!/bin/sh
set -e

# En dev les sources sont montées depuis l'hôte : vendor/ vit dans un volume
# nommé et doit donc être (ré)installé au premier démarrage du conteneur.
if [ "$1" = "php-fpm" ] && [ ! -f vendor/autoload_runtime.php ]; then
    echo "[entrypoint] vendor/ absent — installation des dépendances Composer…"
    composer install --prefer-dist --no-interaction --no-progress
fi

mkdir -p var/cache var/log
chown -R www-data:www-data var 2>/dev/null || true

exec docker-php-entrypoint "$@"
