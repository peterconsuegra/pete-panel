#!/bin/bash
while getopts v:s:b:k: option 
do 
case "${option}" 
	in 
	v) server=${OPTARG};;
	s) sw=${OPTARG};; 
	b) base_path=${OPTARG};; 
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
	
	rm $base_path/public/.htaccess
	cp $base_path/templates/pete_htaccess_serverstatus.txt $base_path/public/.htaccess
	#a2enmod status

fi

if test "$sw" = 'off'; then
	
	rm $base_path/public/.htaccess
	cp $base_path/templates/pete_htaccess.txt $base_path/public/.htaccess
	#a2dismod status

fi

fi
