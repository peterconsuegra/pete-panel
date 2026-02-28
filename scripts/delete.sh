#!/bin/bash

while getopts v:n:r:q:a:s:p: option 
do 
case "${option}" 
	in 
	v) os=${OPTARG};;
	n) project_name=${OPTARG};; 
	r) route=${OPTARG};; 
	q) base_path=${OPTARG};; 
	a) server_conf=${OPTARG};;
	s) id=${OPTARG};;
	p) os_distribution=${OPTARG};;
esac 
done 

rm -rf $route/$project_name
rm /etc/apache2/sites-enabled/$project_name.conf
rm /etc/apache2/sites-enabled/$project_name-le-ssl.conf
rm /etc/apache2/sites-available/$project_name.conf
rm /etc/apache2/sites-available/$project_name-le-ssl.conf

cd /var/www/html/Pete && php artisan opcache:refresh
