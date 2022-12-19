# Learning with Texts

This is a fork of [Hugo Fara's fork](https://github.com/hugofara) of the original Learning with Texts project on [Sourceforge](https://sourceforge.net/projects/learning-with-texts).

## Introductory links:

* Project overview: See [old readme](./docs/old_README.md), or the README on Hugo's fork
* [Why this is forked](./docs/why_the_fork.md)

## Major differences with Hugo Fara's repo

*Also see [the todo list](./docs/todo.md)*.

### Functionality changes

* Wordpress logins give new users their own separate LWT database instance.  See [docs.wordpress](./docs/wordpress.md) for notes.
* Added "parent term" for things like verb declensions etc.
* Removed most text archiving/unarchiving functions.  See [docs/archivingchanges.md](./docs/archivingchanges.md).
* Some changes to the text frame keyboard bindings (i.e. while reading), and new ones added.  See [docs/keybind.md](./docs/keybind.md).

### Code changes

Biggest changes:

* Added simple automatic database migrations.  See [db/README.md](./db/README.md).
* Introduced [Symfony](https://symfony.com/), gradually moving the application to that.  See [docs/symfony.md](./docs/symfony.md).

Other changes:

* Backup/restore removed from the UI: use `sqldump` from the command line, instead of verbose/error-prone PHP code.
* More tests, which can generally only be run using a "test_xxx" database.

## Installation, usage, etc.

Most of the docs at https://github.com/hugofara are still valid.  If anything changes vastly in this fork, I'll briefly document it, and will add detail if requested.

For installation from this fork, `git clone` this repo and use the master branch.

## Use the correct version of php on the server

The app uses components installed using composer, and if your server is running an outdated version of PHP it likely won't work.

For Mac installing later versions of PHP on MAMP, see [this link](https://gist.github.com/codeadamca/09efb674f54172cbee887f04f700fe7c)

I'm doing dev on version 8.1.12, and my MAMP server is running the same version.

## Apache virtual host - TODO

Ref https://davescripts.com/set-up-a-virtual-host-on-mamp-on-mac-os-x

- edit vhosts
- edit server conf to enable vhosts, url rewrite, some other tweaks
- edit your local hosts file

## MySQL load local infile

ref https://dba.stackexchange.com/questions/48751/enabling-load-data-local-infile-in-mysql

## Development

Install [composer](https://getcomposer.org/download/).

Then install dependencies:

`composer install --dev`

## Branches

* **master**: the main branch I use for my own LWT work.
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
```

Some tests require 'load local infile' to be set to On, so you'll need to set that in your php.ini.  For me, for example, the file I changed was at `/usr/local/etc/php/8.1/php.ini`.

## Useful composer commands during dev

(from `composer list`):

```
  class <name>             Show public interface methods of class
  dumpserver               Start the dump server
  find <string>            search specific parts of code using grep
  nukecache                blow things away, b/c symfony likes to cache

  test <filename|blank>    Run tests
  testdata                 Abuse the testing system to load the dev db with some data.
  testgroup <group>        Runs the testgroup script as defined in composer.json

  db:migrate               Run db migrations.
  db:newscript             Make a new db migration script
  db:which                 What db connecting to
```

### Notes

* re "dumpserver" : ref https://symfony.com/doc/current/components/var_dumper.html


## Contribution

* Fork this repo
* Run `composer install --dev` to install dependencies
* Make and test your changes
* Open a PR


## Unlicense
Under unlicense, view [UNLICENSE.md](UNLICENSE.md), please look at [http://unlicense.org/].
