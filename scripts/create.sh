#!/bin/bash

while getopts v:n:u:p:r:q:m:s:a:x:y:z:j:t:w:h:k: option
do
case "${option}"
	in
	n) project_name=${OPTARG};;
	u) project_url=${OPTARG};;
	r) route=${OPTARG};;
	q) base_path=${OPTARG};;
	m) logs_route=${OPTARG};;
	s) os_distribution=${OPTARG};;
	a) apache_conf=${OPTARG};;
	x) database_name=${OPTARG};;
	y) database_user=${OPTARG};;
	z) password_user=${OPTARG};;
	t) server=${OPTARG};;
	h) host=${OPTARG};;
	v) cookie_domain=${OPTARG};;
	k) debug=${OPTARG};;
esac
done

wpkey1=`tr -dc A-Za-z0-9 </dev/urandom | head -c 62 ; echo ''`
wpkey2=`tr -dc A-Za-z0-9 </dev/urandom | head -c 62 ; echo ''`
wpkey3=`tr -dc A-Za-z0-9 </dev/urandom | head -c 62 ; echo ''`
wpkey4=`tr -dc A-Za-z0-9 </dev/urandom | head -c 62 ; echo ''`
wpkey5=`tr -dc A-Za-z0-9 </dev/urandom | head -c 62 ; echo ''`
wpkey6=`tr -dc A-Za-z0-9 </dev/urandom | head -c 62 ; echo ''`
wpkey7=`tr -dc A-Za-z0-9 </dev/urandom | head -c 62 ; echo ''`
wpkey8=`tr -dc A-Za-z0-9 </dev/urandom | head -c 62 ; echo ''`

# DEBUG
if test "$debug" = 'active'; then
	echo "project_name: $project_name"
	echo "project_url: $project_url"
	echo "route: $route"
	echo "apache_conf: $apache_conf"
	echo "database: $database_name"
	echo "user: $database_user"
	echo "password: $password_user"
	echo "wpkey1: $wpkey1"
	echo "wpkey2: $wpkey2"
	echo "wpkey3: $wpkey3"
	echo "wpkey4: $wpkey4"
	echo "wpkey5: $wpkey5"
	echo "wpkey6: $wpkey6"
	echo "wpkey7: $wpkey7"
	echo "wpkey8: $wpkey8"
	echo "server: $server"
	echo "base_path: $base_path"
	echo "logs_route: $logs_route"
	echo "os_distribution: $os_distribution"
fi

crud_route="$base_path/operations/crud/$project_name"

rm -rf "$crud_route"
rm -rf "$route/wordpress_template.tar.gz"

mkdir -p "$crud_route"
cp "$base_path/templates/wordpress_template.tar.gz" "$crud_route/wordpress_template.tar.gz"
cd "$crud_route" && gunzip -c "$crud_route/wordpress_template.tar.gz" | tar xopf -
mv "$crud_route/massive_file/filem" "$route/$project_name"

echo "
define('WP_HOME','http://$project_url');
define('WP_SITEURL','http://$project_url');
define('DB_NAME', '$database_name');
define('DB_USER', '$database_user');
define('DB_PASSWORD', '$password_user');
define('DB_HOST', '$host');

define('AUTH_KEY',         '$wpkey1');
define('SECURE_AUTH_KEY',  '$wpkey2');
define('LOGGED_IN_KEY',    '$wpkey3');
define('NONCE_KEY',        '$wpkey4');
define('AUTH_SALT',        '$wpkey5');
define('SECURE_AUTH_SALT', '$wpkey6');
define('LOGGED_IN_SALT',   '$wpkey7');
define('NONCE_SALT',       '$wpkey8');

define('COOKIE_DOMAIN', '.$project_url' );

" > "$crud_route/otemp.txt"

sed "21r $crud_route/otemp.txt" < "$base_path/templates/wp-template.php" > "$route/$project_name/wp-config.php"

if test "$server" = 'apache'; then

	mkdir -p "$logs_route/$project_name"
	touch "$logs_route/$project_name/error.log"
	touch "$logs_route/$project_name/access.log"

	# Security .htaccess best practices
	rm -f "$route/$project_name/.htaccess"
	rm -rf "$route/$project_name/filem"
	cp "$base_path/templates/htaccess_main.txt" "$route/$project_name/.htaccess"

	rm -f "$route/$project_name/wp-content/.htaccess"
	rm -f "$route/$project_name/wp-includes/.htaccess"
fi

# uploads hardening (always apply)
mkdir -p "$route/$project_name/wp-content/uploads"
rm -f "$route/$project_name/wp-content/uploads/.htaccess"

if [ -f "$base_path/templates/htaccess_uploads.txt" ]; then
	cp "$base_path/templates/htaccess_uploads.txt" "$route/$project_name/wp-content/uploads/.htaccess"
	if test "$debug" = 'active'; then
		echo "uploads .htaccess applied: $route/$project_name/wp-content/uploads/.htaccess"
	fi
else
	echo "uploads template missing: $base_path/templates/htaccess_uploads.txt"
fi

# Security files permissions
find "$route/$project_name" -type d -exec chmod 755 {} +
find "$route/$project_name" -type f -exec chmod 644 {} +
# chmod 600 "$route/$project_name/wp-config.php"

rm -rf "$crud_route"

cd /var/www/html/Pete && php artisan opcache:refresh