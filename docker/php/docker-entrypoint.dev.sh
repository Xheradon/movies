#!/bin/sh
set -e

export XDEBUG_MODE=off # Disable xdebug for entrypoint

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	PHP_INI_RECOMMENDED="$PHP_INI_DIR/php.ini-production"
	if [ "$APP_ENV" != 'prod' ]; then
		PHP_INI_RECOMMENDED="$PHP_INI_DIR/php.ini-development"
	fi
	ln -sf "$PHP_INI_RECOMMENDED" "$PHP_INI_DIR/php.ini"

	mkdir -p var/cache var/log

	if [ "$APP_ENV" != 'prod' ]; then
		composer install --ignore-platform-req=php --prefer-dist --no-progress --no-interaction
	fi

	echo "Waiting for db to be ready..."
	ATTEMPTS_LEFT_TO_REACH_DATABASE=60
	until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
		sleep 1
		ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE-1))
		echo "Still waiting for db to be ready... Or maybe the db is not reachable. $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left"
	done

	if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
		echo "The db is not up or not reachable"
		exit 1
	else
	   echo "The db is now ready and reachable"
	fi

	if ls -A migrations/*.php > /dev/null 2>&1; then
		bin/console doctrine:migrations:migrate --no-interaction
	fi
fi

if [ -n "$XDEBUG_ENABLED" ]; then
	export XDEBUG_MODE=profile,debug
fi
exec docker-php-entrypoint "$@"
