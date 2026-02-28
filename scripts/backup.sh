#!/bin/bash

while getopts n:p:r:q:m:v:w:u:d:s:b:f:k: option 
do 
case "${option}" 
	in 
	n) project_name=${OPTARG};; 
	r) route=${OPTARG};; 
	q) base_path=${OPTARG};; 
	u) site_url=${OPTARG};;
	d) site_id=${OPTARG};;
	f) file_name=${OPTARG};;
	k) debug=${OPTARG};;
esac 
done 

if test "$debug" = 'active'; then

echo project_name: $project_name
echo route: $route
echo site_url: $site_url
echo file_name: $file_name
echo debug: $debug
echo base_path: $base_path

fi

echo "Compressing your site..."

CONFIG_FILE="$route/$project_name/wp-config.php"

# Extract table prefix
TABLE_PREFIX=$(grep "^\$table_prefix" "$CONFIG_FILE" | awk -F"'" '{print $2}')

mkdir -p $base_path/operations/backups/$project_name
backup_route=$base_path/operations/backups/$project_name
rm -rf backup_route
mkdir -p $backup_route/massive_file

echo "domain: '$site_url'
platform: 'WordPress'
prefix: '$TABLE_PREFIX'
" > $backup_route/massive_file/config.txt

cp -a $route/$project_name $backup_route/massive_file/filem
chmod -R 755 $backup_route/massive_file
cd $backup_route && tar czf massive_file.tar.gz massive_file

mkdir -p $base_path/backups/$project_name
mv $backup_route/massive_file.tar.gz $base_path/backups/$project_name/$file_name
echo "Compression complete..."
rm -rf $backup_route

