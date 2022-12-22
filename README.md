# LUTE - Learning Using Texts

This is a fork and ground-up rewrite of [Hugo Fara's fork](https://github.com/hugofara) of the original Learning with Texts project on [Sourceforge](https://sourceforge.net/projects/learning-with-texts).

See [the docs](./docs/README.md) for notes about this project, why it was forked, to-dos, etc.

> TODO:docs - add a screencast gif.

## Installation, usage, etc.

> TODO:docs - the docs for installation need work, and perhaps some things can be simplified, such as removing vhosts.

This project does not have "GitHub releases" yet, so just clone the repo to your machine, or get the latest [zipfile from GitHub](https://github.com/jzohrab/lute/archive/refs/heads/master.zip), and unpack it in a directory in your "Public" folder.

The setup for this project is much the same as [LWT](https://github.com/HugoFara/lwt):

* get an Apache server with PHP and MySQL
* Enable "load local infile" for MySQL server
* create a "connect.inc.php"

Unlike LWT, which just uses plain php files, Lute uses the [symfony](https://symfony.com/) framework, and so has more requirements:

* You'll need [composer](https://getcomposer.org/download/) to install the dependencies
* PHP version least 8.1
* Apache: enable virtual hosts and URL rewrites.
* Apache: create a Virtual Host to redirect requests to the Lute "front controller", and edit your `etc/hosts`.

My personal Lute is running Apache/2.4.54, with PHP version 8.1.13.

### connect.inc.php

Copy the file `connect.inc.php.example` to `connect.inc.php`, and specify your values for the variables (server, userid, password, db name).

### PHP version

For Mac installing later versions of PHP on MAMP, see [this link](https://gist.github.com/codeadamca/09efb674f54172cbee887f04f700fe7c)

## Apache virtual host

Ref https://davescripts.com/set-up-a-virtual-host-on-mamp-on-mac-os-x

For me on my mac, the virtual hosts file was at `/usr/local/etc/httpd/extra/httpd-vhosts.conf`, and I added the following, specifying my particular path:

```
<VirtualHost *:8080>
    DocumentRoot "/Users/jeff/Public/lute/public"
    ServerName lute.local
    <Directory "/Users/jeff/Public/lute/public">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

After saving the file, restart Apache, and check the configuration:

```
sudo apachectl restart    # Restart :-)
apachectl -S              # Check your vhost config:
```

Note that the `ServerName` above is `lute.local` ... so then you need to edit your `/etc/hosts` file so that you can enter "http://lute.local:8080/" in your browser.

For a mac, do the following:

```
sudo vi /etc/hosts
```

Add the line

```
127.0.0.1       lute.local
```

Then in a browser window, go to http://lute.local:8080/ - if it pops up, your basic mappings are fine.

### MySQL load local infile

ref https://dba.stackexchange.com/questions/48751/enabling-load-data-local-infile-in-mysql

The app and many tests require 'load local infile' to be set to On, so you'll need to set that in your php.ini.  For me, for example, the file I changed was at `/usr/local/etc/php/8.1/php.ini`.

## Development

Install [composer](https://getcomposer.org/download/).

Then install dependencies:

`composer install --dev`

## Branches

* **master**: the main branch I use for Lute.
* other branches: features I'm working on.

## Tests

Most tests hit the database, and refuse to run unless the database name starts with 'test_'.  This prevents you from destroying real data!

In your connect.inc.php, change the `$dbname` to `test_<whatever>`, and create the `test_<whatever>` db using a dump from your actual db, or just create a new one.  Then the tests will work.

**You have to use the config file phpunit.xml.dist when running tests!**  So either specify that file, or use the composer test command:

```
./bin/phpunit -c phpunit.xml.dist tests/src/Repository/TextRepository_Test.php

composer test tests/src/Repository/TextRepository_Test.php
```

Examples:

```
# Run everything
composer test tests

# Single file
composer test tests/src/Repository/TextRepository_Test.php

# Tests marked with '@group xxx'
composer test:group xxx
```

## Useful composer commands during dev

(from `composer list`):

```
 db
  db:migrate               Run db migrations.
  db:newrepeat             Runs the db:newrepeat script as defined in composer.json
  db:newscript             Make a new db migration script
  db:which                 What db connecting to
 dev
  dev:class                Show public interface methods of class
  dev:data                 Abuse the testing system to load the dev db with some data.
  dev:dumpserver           Start the dump server
  dev:find                 search specific parts of code using grep
  dev:minify               Regenerate minified CSS
  dev:nukecache            blow things away, b/c symfony likes to cache
  dev:psalm                Run psalm and start crying
 test <filename|blank>     Run tests
  test:group               Run tests with a given '@group xxxx' annotation
 todo
  todo:list                Show code todos
  todo:types               Show types of todos
```

* re dumpserver: ref https://symfony.com/doc/current/components/var_dumper.html


## Contribution

* Fork this repo
* Run `composer install --dev` to install dependencies
* Make and test your changes
* Open a PR


## Unlicense
Under unlicense, view [UNLICENSE.md](UNLICENSE.md), please look at [http://unlicense.org/].
