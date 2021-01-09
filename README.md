Antidot Framework - Getting Started App
=================

## Install

````bash
git clone git@github.com:antidot-framework/getting-started-app.git
cd getting-started-app
touch var/database.sqlite
composer install
bin/console orm:schema-tool:create
php -S 127.0.0.1:8000 -t public
````
