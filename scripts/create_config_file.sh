#!/bin/bash

while getopts l:n:u:a:o:d:e:r:p:c: option 
do 
case "${option}" 
	in 
	l) logs_route=${OPTARG};; 
	n) project_name=${OPTARG};; 
	u) project_url=${OPTARG};; 
	a) server_conf=${OPTARG};;
	o) app_name=${OPTARG};; 
	d) os_distribution=${OPTARG};; 
	e) server=${OPTARG};; 
	r) app_root=${OPTARG};; 
	p) base_path=${OPTARG};; 
	c) os=${OPTARG};; 
esac 
done 

echo logs_route $logs_route
echo project_name $project_name
echo project_url $project_url
echo server_conf $server_conf
echo app_name $app_name
echo os_distribution $os_distribution
echo "app_name: $app_name"
echo "server: $server"
echo "os: $os"

if [[ $app_name == "WordPress" && $server == "apache" ]]; then
	
	echo "WordPress + apache"
	
	rm $server_conf/$project_name.conf
	
	mkdir $logs_route/$project_name
	touch $logs_route/$project_name/error.log
	touch $logs_route/$project_name/access.log

	if [[ $os == "docker" ]]; then

		echo "entro en modo docker"
		
		echo "
		<VirtualHost *:80>
		ServerName   $project_url
		ServerAlias  www.$project_url

		# Point at your WP install
		DocumentRoot $app_root/$project_name

		<Directory $app_root/$project_name>
			# enable .htaccess-based permalinks & basic overrides
			Options       FollowSymLinks
			AllowOverride All
			Require       all granted
		</Directory>

		<FilesMatch '\.php$'>
        	SetHandler 'proxy:fcgi://php:9000'
    	</FilesMatch>

		# (Optional) log files
		ErrorLog $logs_route/$project_name/error.log
		CustomLog $logs_route/$project_name/access.log combined
		</VirtualHost>" > $server_conf/$project_name.conf

	else

		echo "
		<VirtualHost *:80>
		ServerName   $project_url
		ServerAlias  www.$project_url

		# Point at your WP install
		DocumentRoot $app_root/$project_name

		<Directory $app_root/$project_name>
			# enable .htaccess-based permalinks & basic overrides
			Options       FollowSymLinks
			AllowOverride All
			Require       all granted
		</Directory>

		# (Optional) log files
		ErrorLog $logs_route/$project_name/error.log
		CustomLog $logs_route/$project_name/access.log combined
		</VirtualHost>" > $server_conf/$project_name.conf

	fi
	
	if test "$os_distribution" = 'ubuntu'; then
		cd /etc/apache2/sites-enabled && ln -s /etc/apache2/sites-available/$project_name.conf $project_name.conf
	fi	
		
elif [[ $app_name == "WordPress+Laravel" && $server == "apache" ]]; then
	
	echo "entro en app_name: WordPress+Laravel + apache"
	
	rm $server_conf/$project_name.conf

	if [[ $os == "docker" ]]; then

			echo "
		<VirtualHost *:80>
		
			ServerName $project_url
			ServerAlias www.$project_url
			DocumentRoot $app_root/$project_name/public		
			
			<Directory $app_root/$project_name>
				SetOutputFilter DEFLATE
				Options FollowSymLinks
				AllowOverride All
				Require all granted
			</Directory>

			<FilesMatch '\.php$'>
   				# neutralise any other handler that may already be set
    			SetHandler none
    			# now forward to PHP-FPM (the 'php' service from docker-compose)
    			SetHandler 'proxy:fcgi://php:9000'
			</FilesMatch>

			# optional – if you use +MultiViews anywhere
			<IfModule mod_negotiation.c>
    			Options -MultiViews
			</IfModule>
			
			ErrorLog $logs_route/$project_name/error.log
			CustomLog $logs_route/$project_name/access.log combined
			
		</VirtualHost>" > $server_conf/$project_name.conf

	else

		echo "
		<VirtualHost *:80>
		
			ServerName $project_url
			ServerAlias www.$project_url
			DocumentRoot $app_root/$project_name/public		
			
			<Directory $app_root/$project_name>
				SetOutputFilter DEFLATE
				Options FollowSymLinks
				AllowOverride All
				Require all granted
			</Directory>
			
			ErrorLog $logs_route/$project_name/error.log
			CustomLog $logs_route/$project_name/access.log combined
			
		</VirtualHost>" > $server_conf/$project_name.conf

	fi
	
	if test "$os_distribution" = 'ubuntu'; then
		cd /etc/apache2/sites-enabled && ln -s /etc/apache2/sites-available/$project_name.conf $project_name.conf
	fi
	
	cd $app_root/$project_name && wp plugin deactivate really-simple-ssl
	cd $app_root/$project_name && wp plugin deactivate ssl-insecure-content-fixer
	
elif [[ $app_name == "Laravel" && $server == "apache" ]]; then
	
	echo "entro en app_name: Laravel + apache"
	
	rm $server_conf/$project_name.conf
		
	echo "
	<VirtualHost *:80>
	
	    ServerName $project_url
	    ServerAlias www.$project_url
	    DocumentRoot $app_root/$project_name/public		
		  
        <Directory $app_root/$project_name>
        	SetOutputFilter DEFLATE
        	Options FollowSymLinks
        	AllowOverride All
        	Require all granted
        </Directory>
		
	    ErrorLog $logs_route/$project_name/error.log
	    CustomLog $logs_route/$project_name/access.log combined
		
	</VirtualHost>" > $server_conf/$project_name.conf
	
	if test "$os_distribution" = 'ubuntu'; then
		cd /etc/apache2/sites-enabled && ln -s /etc/apache2/sites-available/$project_name.conf $project_name.conf
	fi
	
	cd $app_root/$project_name && wp plugin deactivate really-simple-ssl
	cd $app_root/$project_name && wp plugin deactivate ssl-insecure-content-fixer
	
fi


if [[ $app_name == "WordPress" && $server == "apache" ]]; then

	echo "entro en app_name: WordPress + apache"
	echo "adding .htaccess"

	#Security .htaccess best practices
	if [ ! -f $route/$project_name/.htaccess ]; then
	    cp $base_path/templates/htaccess_main.txt $app_root/$project_name/.htaccess
	fi
	
	rm $app_root/$project_name/wp-content/uploads/.htaccess		
	rm $app_root/$project_name/wp-includes/.htaccess


fi
