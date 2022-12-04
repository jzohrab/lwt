# Learning with Texts

This is a fork of [Hugo Fara's fork](https://github.com/hugofara) of the original Learning with Texts project on [Sourceforge](https://sourceforge.net/projects/learning-with-texts).

## Introductory links:

* Project overview: See [old readme](./docs/old_README.md), or the README on Hugo's fork
* [Why this is forked](./docs/why_the_fork.md)

## Major differences with Hugo Fara's repo

### Functionality changes

* Wordpress logins give new users their own separate LWT database instance.  See [docs.wordpress](./docs/wordpress.md) for notes.
* Added "parent term" for things like verb declensions etc.
* Removed most text archiving/unarchiving functions.  See [docs/archivingchanges.md](./docs/archivingchanges.md).
* Some changes to the text frame keyboard bindings (i.e. while reading), and new ones added.  See [docs/keybind.md](./docs/keybind.md).

### Code changes

* Backup/restore removed from the UI: use `sqldump` from the command line, instead of verbose/error-prone PHP code.
* Added simple automatic database migrations.  See [db/README.md](./db/README.md).
* Gradually/hopefully moving to different code organization, following ideas outlined in [on-structuring-php-projects](https://www.nikolaposa.in.rs/blog/2017/01/16/on-structuring-php-projects/).
* More tests, which can generally only be run using a "test_xxx" database.

## Installation, usage, etc.

Most of the docs at https://github.com/hugofara are still valid.  If anything changes vastly in this fork, I'll briefly document it, and will add detail if requested.

For installation from this fork, `git clone` this repo and use the master branch.

## Use the correct version of php on the server

The app uses components installed using composer, and if your server is running an outdated version of PHP it likely won't work.

For Mac installing later versions of PHP on MAMP, see [this link](https://gist.github.com/codeadamca/09efb674f54172cbee887f04f700fe7c)

I'm doing dev on version 8.1.12, and my MAMP server is running the same version.

## Development

The project will likely eventually _require_ composer to be run, so install it following [these directions](https://getcomposer.org/download/).

Then install dependencies:

`composer install --dev`

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
