# MVP Phase 1

The initial goal of this project is to get the minimum set of features implemented under the new Symfony framework:

* saving read texts.
* settings?  Not sure if needed at the moment.

## Done

* define languages
* create a text
* parsing and rendering
* right pane word definition pop-up
* create terms and multiword terms
* remove all legacy code
* setting statuses, and bulk status updates.

## LWT features that were scrapped, and reasons for it.

Most or all of the legacy code should be scrapped, it's not manageable.  These features *might* be re-introduced after the MVP is done.

| Feature | Notes |
| --- | --- |
| anki-like testing | The current testing code isn't the best.  It assumes that it should just potentially test everything.  I should be able to select the terms I want to test, especially parent terms that implicitly include many child sentences.  Needs a big rearchitecture. |
| multi-word edit screen | i.e. `/edit_words.php` - to be replaced by a datatables-type view. |
| bulk translation | A good idea, but the code was pretty rough. |
| Docker | This is probably a very good thing to look into, but users would have to have docker on their systems, which might not be common.  Who knows?  And, of course, the docker-to-database issue, which was a big deal a few years ago, might be tricky. |
| theming | Symfony has theming options, should look into that. |
| rss feeds | The current code was brittle for me, at least, it didn't work.  My guess is that it can be simplified. |
| text annotations | i.e., texts.TxAnnotatedText field, and all "Improved annotation" such as `/print_impr_text.php?text=1`.  The data is currently stored in a non-relational way in the database, and doesn't need to be. |
| db import and export | This is important, but needs to be looked at more carefully -- probably some kind of command-line tool would be best.  See [Exporting and restoring the database](./db_export_restore.md). |
| overlib | an out-of-date library. |
| old documentation | Has too many screenshots that don't apply now! |


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

