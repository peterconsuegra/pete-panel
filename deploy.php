<?php
namespace Deployer;

#dep deploy

require 'recipe/laravel.php';

// Project name
set('application', '/var/www/Pete4');

// Project repository
set('repository', 'git@bitbucket.org:ozone777/wordpresspete3.git');

set('shared_dirs', ['vendor','trash','backups']);
set('shared_files', ['.env']);
set('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);
set('log_files', 'storage/logs/*.log');

// Hosts

//host('45.33.97.148','45.79.14.236')
//host('45.33.97.148','45.79.14.236','104.237.135.104','69.164.197.235','50.116.40.151','45.79.223.4','173.255.196.80')
//host('45.33.97.148','45.79.14.236')
//host('45.33.97.148','45.79.14.236','104.237.135.104','69.164.197.235','50.116.40.151','45.79.223.4','173.255.196.80')
//host('45.33.97.148','45.79.14.236','104.237.135.104','69.164.197.235','50.116.40.151','45.79.223.4')
//host('173.255.196.80')
//host('45.33.97.148','45.79.14.236','104.237.135.104','69.164.197.235','50.116.40.151','45.79.223.4','173.255.196.80')
//host('173.255.196.80')
//host('45.33.97.148')
//LINODE
//host('45.79.14.236','104.237.135.104','69.164.197.235','50.116.40.151','45.79.223.4','173.255.196.80')
//GOOGLE CLOUD
host('34.121.65.246','34.123.50.203','35.238.135.38')
	->port(13727)
	->user('root')
	->identityFile('~/.ssh/id_rsa')
	->set('branch', '4')
    ->set('deploy_path', '{{application}}')
	->set('keep_releases', 5);
	
    
// Tasks

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

task('deploy:owner', function () {
  run('chown -R www-data:www-data {{application}}');
});

task('deploy:run_composer', function () {
  //run('cd {{release_path}} && rm -rf .git');
  run('cp {{release_path}}/composer_full.json {{release_path}}/composer.json');
  //run('cd {{release_path}} && echo "yes" | composer install');
});

task('deploy:run_laravel_commands', function () {
  run('cd {{release_path}} && php artisan optimize:clear');
  run('cd {{release_path}} && php artisan config:cache');
  run('cd {{release_path}} && php artisan config:clear');
  run('cd {{release_path}} && composer dump-autoload');
});

// Migrate database before symlink new release.
after('deploy:symlink', 'deploy:owner');
after('deploy:symlink', 'deploy:run_composer');
after('deploy:symlink', 'deploy:run_laravel_commands');
before('deploy:symlink', 'artisan:migrate');
before('deploy:vendors', 'deploy:run_composer');

