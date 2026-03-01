# What Is Pete Panel?

Pete Panel is the developer-first control panel for launching, cloning, migrating, and syncing WordPress and Laravel projects in minutes.

Powered by an optimized Docker LAMP stack, it delivers massive scalability and can support billions of visits on low-cost cloud infrastructure. With production-ready environments, free SSL, OWASP-level security, and phpMyAdmin included, Pete gives you a simpler, faster, and more efficient way to build and scale modern websites and hybrid WordPress and Laravel applications.

Watch Pete Panel launch, clone, and sync WordPress and Laravel projects in minutes.

A smarter way to build: sync WordPress with Laravel

Let WordPress handle your marketing pages, subscriptions, orders, payments, ad pixel tracking, and more by leveraging its vast and battle-tested plugin ecosystem. Meanwhile, Laravel powers your custom client-side features built in React or any modern JavaScript framework. This pairing gives you the power and flexibility of a full framework with the speed and convenience of a CMS, cutting development time and accelerating MVP launches by up to 10x.

From setup to scalability, accelerate your entire workflow with Pete.
Whether you’re building in WordPress, Laravel, or syncing WordPress with Laravel, Pete gives you the speed, security, and control to ship faster and smarter.

One-Click Migrate, Deploy & Clone
Spin up new sites, migrate or duplicate existing ones instantly.

Performance You Can Feel
Deploy projects optimized with industry best practices. Leverage Apache’s MPM Event for efficient request handling and HTTP/2 for lightning-fast content delivery across any framework.

Security First by Design
Every site includes free SSL via Let’s Encrypt and OWASP CRS web-application protection. Stay secure out of the box and spend more time building, not patching.

Laravel + WordPress + Beyond
Combine frameworks seamlessly. Use WordPress for content, SEO, and marketing — and power your custom dashboards, APIs, or automations with Laravel or any PHP backend.

Built on Docker Best Practices
Pete runs on Docker for rock-solid consistency, portability, and scalability — ensuring your local setup matches production every time.

Dev Playgrounds for Experimentation
Test plugins, debug features, or prototype new ideas using your favorite editor inside Pete’s dedicated development playgrounds.

Switch PHP Versions Instantly
Toggle between PHP versions on the fly to test compatibility or performance — no reconfiguration needed.

Backup & Restore with Confidence
Export complete environments anytime in Pete format for extra peace of mind beyond your hosting provider’s backups.

Database Control Made Simple
Manage and debug your databases visually with phpMyAdmin — pre-installed and ready to use in every environment.

✅ In short: Pete Panel is your all-in-one control panel for modern web development  engineered for speed, security, and simplicity so you can build and launch projects faster than ever.

---

## ⚠️ License & Legal

**All rights reserved. Pete Panel is _not_ open source.**  
Commercial licenses are available at **https://deploypete.com**.  
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