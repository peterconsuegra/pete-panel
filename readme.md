# WordPress Pete (Proprietary)

> **WordPress Pete** is a Laravel-powered control panel that automates launching, cloning, migrating, and integrating WordPress inside a Docker LAMP stack. It’s built to speed up local and server workflows with opinionated tooling, WP-CLI automation, and handy admin utilities.

---

## ⚠️ License & Legal

**All rights reserved. WordPress Pete is _not_ open source.**  
Commercial licenses are available at **https://wordpresspete.com**.  
Unauthorized copying, modification, distribution, or use is strictly prohibited.

---

## Tech Stack

- **Laravel**: 10.x  
- **PHP**: 8.1 / 8.2 / 8.3  
- **Docker LAMP**: Apache + PHP-FPM + MySQL  
- Selected Composer packages:
  - `ifsnop/mysqldump-php`
  - `thamaraiselvam/mysql-import`
  - `laravel/sanctum`, `laravel/ui`, etc.

> This repository assumes you run Pete inside the provided Docker environment. The app source lives in `/var/www/html`.


## Otimpize app

php artisan route:cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

## Test in Development with Katelon Recorder
Delete all site with the admin
Enter to http://pete.petelocal.net/phpmyadmin/ and delete the users
Logout from http://pete.petelocal.net/phpmyadmin/
Uninstall all the plugins
Delete all backups
Delete WordPres+Laravel apps
Run all th test again