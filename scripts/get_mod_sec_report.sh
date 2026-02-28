#!/bin/bash

while getopts l:o:b: option 
do 
case "${option}" 
	in 
	l) log_path_dest=${OPTARG};;
	o) log_path_origin=${OPTARG};;
	b) base_path=${OPTARG};;
esac 
done 

echo "entro en get_mod_sec_report"
cp $log_path_origin $log_path_dest
chown www-data:www-data $log_path_dest
echo "cd $base_path/mod_sec_report && ./mod_sec_report $log_path_dest > modsec-audit.html"
rm $base_path/mod_sec_report/modsec-audit.html
cd $base_path/mod_sec_report && ./mod_sec_report $log_path_dest > modsec-audit.html