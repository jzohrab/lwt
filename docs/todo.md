# MVP Phase 1

The initial goal of this project is to get the minimum set of features implemented under the new Symfony framework:

* run all legacy code tests in parallel, to ensure DB compatibility of old code.
* ajax set statuses
* bulk status updates
** Remove all deps on database_connection.php in this class.
** On text open, just re-parse it.  This takes care of any parsing and expression issues, and should speed up status updates for the current page too.
* settings?  Not sure if needed at the moment.
* _REMOVE ALL LEGACY CODE_.

## Done

* define Language
* create a text
* rework rendering
* right pane word definition pop-up
** create terms and multiword terms
* move parsing stuff to separate class with smaller interface.

## LWT features to be scrapped

Most or all of the legacy code should be scrapped, it's not manageable.  These features *might* be re-introduced after the MVP is done.

### All anki-like testing

The current testing code isn't the best.  It assumes that it should just potentially test everything.  I should be able to select the terms I want to test, especially parent terms that implicitly include many child sentences.  Needs a big rearchitecture.

### multi-word edit screen

`/edit_words.php` - to be replaced by a datatables-type view.

### bulk translation

A good idea, but the code was pretty rough.

### docker

This is probably a very good thing to look into, but users would have to have docker on their systems, which might not be common.  Who knows?  And, of course, the docker-to-database issue, which was a big deal a few years ago, might be tricky.

### theming (needs big re-architecture)

Symfony has theming options, should look into that.

### rss feeds

The current code is too crazy to work with, and for me at least, setting up the import didn't work.

### text annotations

i.e., texts.TxAnnotatedText field, and all "Improved annotation" such as `/print_impr_text.php?text=1`.  The data is currently stored in a non-relational way in the database, and doesn't need to be.

### db import and export

Exports are pretty trivial, but imports are a *big deal* -- it's too easy to write over things.  With database migrations, it's also easy (aka bad) for users to restore non-compatible schema versions, and they'd have to run db migrations.

In a real-world system, devs or DBAs would handle backups and restores.

So, this is important, but needs to be looked at more carefully -- probably some kind of command-line tool would be best.

### overlib

An out-of-date library.

### old documentation

Has too many screenshots that don't apply now!


# MVP Phase 2

A rough list of big-ish features to add once MVP1 is done:

* statistics
* import terms
* help/info
* manage term tags
* manage text tags
* import a long text file

# Small-ish features to add at any point

* Command-line job to fix bad multiword expressions.  See devscripts/verify_mwords.sql for a starting point.
* Check text length constraint - 65K too long.
* Add repeatable migrations to db migrator
* Move trigger creation to repeatable migration
* Fix docs for exporting a DB backup, skip triggers (trigger in dumpfile was causing mysql to fail on import)
* Language "reparse all texts" on language change - or just reparse on open

# Post-MVP

* anki export
* bulk translation (?)
* archive language.  (Deleting would lose all texts, so just archive the language and all its texts, and have option to re-activate).
* Playing media in /public/media or from other sources.


# Why scrapping

* remove all existing anki testing code

