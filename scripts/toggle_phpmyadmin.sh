#!/bin/bash
while getopts v:s:k: option 
do 
case "${option}" 
	in 
	v) server=${OPTARG};;
	s) sw=${OPTARG};; 
	k) debug=${OPTARG};;	
esac 
done 
 
#DEBUG
if test "$debug" = 'active'; then

echo server: $server
echo mod_sw: $sw
echo debug: $debug

fi

if test "$server" = 'apache'; then

if test "$sw" = 'on'; then
	
	echo "cd /etc/apache2/sites-enabled && ln -s /etc/apache2/sites-available/phpmyadmin.conf phpmyadmin.conf"
	cd /etc/apache2/sites-enabled && ln -s /etc/apache2/sites-available/phpmyadmin.conf phpmyadmin.conf

fi

if test "$sw" = 'off'; then
	
	echo "rm -rf /etc/apache2/sites-enabled/phpmyadmin.conf"
	rm -rf /etc/apache2/sites-enabled/phpmyadmin.conf

fi

fi
