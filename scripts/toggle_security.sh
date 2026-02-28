#!/bin/bash
while getopts v:s:k: option 
do 
case "${option}" 
	in 
	v) apache_reload_secret=${OPTARG};; 
	s) mod_sw=${OPTARG};; 
	k) debug=${OPTARG};;	
esac 
done 
 
#DEBUG
if test "$debug" = 'active'; then

echo debug: $debug
echo server: $apache_reload_secret
echo mod_sw: $mod_sw

fi

curl -s -H "X-Reload-Secret: $apache_reload_secret" \
     "http://apache/modsecurity-status?sw=$mod_sw"


