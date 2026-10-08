#!/bin/sh
#
# Prépare l'environnement des tests PHPUnit. À lancer depuis la machine hôte,
# une fois, pile Docker démarrée, puis après chaque recréation du volume de la
# base de données :
#
#     sh backend/tests/preparer-environnement.sh
#
# Deux opérations que les tests ne peuvent pas faire eux-mêmes :
#
# 1. Créer la base de test. L'image MySQL officielle ne donne à l'utilisateur
#    de l'application des droits que sur sa propre base : c'est le compte root
#    qui crée la base suffixée _test et lui en ouvre l'accès.
#
# 2. Fournir la phrase secrète JWT. En environnement de test, Symfony ne charge
#    pas .env.local, où l'entrypoint l'a écrite : on la recopie dans
#    .env.test.local, qui est chargé en test et n'est pas versionné.
set -eu

cd "$(dirname "$0")/../.."
compose="docker compose -f docker/docker-compose.yml"

$compose exec -T database sh -c '
    mysql -uroot -p"$MYSQL_ROOT_PASSWORD" 2>/dev/null <<SQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}_test\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE}_test\`.* TO "${MYSQL_USER}"@"%";
FLUSH PRIVILEGES;
SQL
    echo "Base ${MYSQL_DATABASE}_test prête."
'

$compose exec -T php sh -c '
    grep "^JWT_PASSPHRASE=" .env.local > .env.test.local
    echo "Phrase secrète JWT recopiée dans .env.test.local."
'
