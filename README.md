# Learning with Texts

This is a fork of [Hugo Fara's fork](https://github.com/hugofara) of the original Learning with Texts project on [Sourceforge](https://sourceforge.net/projects/learning-with-texts).

## Introductory links:

* Project overview: See [old readme](./docs/old_README.md), or the README on Hugo's fork
* [Why this is forked](./docs/why_the_fork.md)

## Major differences with Hugo Fara's repo

* Wordpress logins give new users their own separate LWT database instance.  See [docs.wordpress](./docs/wordpress.md) for notes.
* Backup/restore removed from the UI: use `sqldump` from the command line, instead of verbose/error-prone PHP code.
* Added simple automatic database migrations.  See [db/README.md](./db/README.md).
* Added "parent term" for things like verb declensions etc.

## Installation, usage, etc.

Most of the docs at https://github.com/hugofara are still valid.  If anything changes vastly in this fork, I'll briefly document it, and will add detail if requested.

For installation from this fork, `git clone` this repo and use the master branch.


## Branches

* **master**: the main branch I use for my own LWT work.
* other branches: features I'm working on.

## Tests

```
# Run a single file
./vendor/bin/phpunit tests/splitCheckText_Test.php

# Run everything
./vendor/bin/phpunit tests
```

Some tests require 'load local infile' to be set to On, so you'll need to set that in your php.ini.  For me, for example, the file I changed was at `/usr/local/etc/php/8.1/php.ini`.

## Contribution

* Fork this repo
* Run `composer install --dev` to install dependencies
* Make and test your changes
* Open a PR


## Unlicense
Under unlicense, view [UNLICENSE.md](UNLICENSE.md), please look at [http://unlicense.org/].
