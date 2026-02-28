#!/bin/bash

while getopts e:d:s:p: option 
do 
case "${option}" 
	in 
	e) email=${OPTARG};; 
	d) domain=${OPTARG};; 
	s) apache_reload_secret=${OPTARG};; 
	p) project_path=${OPTARG};; 
esac 
done 

echo "generating SSL"
echo "email: $email"
echo "domain: $domain"
echo "apache_reload_secret: $apache_reload_secret"
echo "project_path: $project_path"

#echo "certbot --apache --non-interactive --agree-tos --email $email -d $domain"
#certbot --apache --non-interactive --agree-tos --email $email -d $domain

curl -s -H "X-Reload-Secret: $apache_reload_secret" \
     "http://apache/internal-certbot?domain=$domain&email=$email"

echo "after curl"

#set new URL
WP_CONFIG_PATH=$project_path/wp-config.php
NEW_URL=https://$domain
#refine images names in DB
echo "cd $project_path && wp search-replace http://$domain $NEW_URL"
cd $project_path && wp search-replace http://$domain $NEW_URL

# Replace WP_HOME
echo sed -i "s|define('WP_HOME'.*);|define('WP_HOME', '$NEW_URL');|" $WP_CONFIG_PATH
sed -i "s|define('WP_HOME'.*);|define('WP_HOME', '$NEW_URL');|" $WP_CONFIG_PATH


# Replace WP_SITEURL
echo sed -i "s|define('WP_SITEURL'.*);|define('WP_SITEURL', '$NEW_URL');|" $WP_CONFIG_PATH
sed -i "s|define('WP_SITEURL'.*);|define('WP_SITEURL', '$NEW_URL');|" $WP_CONFIG_PATH

cd /var/www/html/Pete && php artisan opcache:refresh
