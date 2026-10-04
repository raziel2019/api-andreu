#!/bin/sh
set -eu

cd /var/www

LARAVEL_VERSION="${LARAVEL_VERSION:-^13.0}"

set_env_value() {
    file="$1"
    key="$2"
    value="$3"

    [ -f "$file" ] || return 0

    if grep -q "^${key}=" "$file"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$file"
    else
        printf '\n%s=%s\n' "$key" "$value" >> "$file"
    fi
}

configure_laravel() {
    for env_file in .env .env.example; do
        set_env_value "$env_file" APP_URL http://localhost:8080
        set_env_value "$env_file" DB_CONNECTION mysql
        set_env_value "$env_file" DB_HOST db
        set_env_value "$env_file" DB_PORT 3306
        set_env_value "$env_file" DB_DATABASE laravel
        set_env_value "$env_file" DB_USERNAME laravel
        set_env_value "$env_file" DB_PASSWORD laravel
        set_env_value "$env_file" MAIL_MAILER smtp
        set_env_value "$env_file" MAIL_HOST mailhog
        set_env_value "$env_file" MAIL_PORT 1025
    done
}

if [ ! -f artisan ]; then
    echo "Laravel no existe. Creando Laravel ${LARAVEL_VERSION}..."
    rm -rf /tmp/laravel
    composer create-project "laravel/laravel:${LARAVEL_VERSION}" /tmp/laravel --no-interaction
    cp -a /tmp/laravel/. /var/www/
    rm -rf /tmp/laravel
    configure_laravel
    echo "Laravel ${LARAVEL_VERSION} creado correctamente."
elif [ -f composer.json ] && ! grep -q '"laravel/framework": "\^13' composer.json; then
    echo "Aviso: ya existe un proyecto Laravel que no parece ser 13.x. No se sobrescribe automaticamente."
fi

if [ -f artisan ] && [ ! -f vendor/autoload.php ]; then
    echo "Instalando dependencias de Composer..."
    composer install --no-interaction
fi

if [ -f artisan ]; then
    configure_laravel
    php artisan migrate --force || true
fi

exec "$@"
