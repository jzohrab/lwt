# To-do

(This list is likely incomplete.)  Sorted more-or-less by priority.  As things are done, they're moved to the Done list and possibly referenced in the top-level README.

## MVP Phase 1

The initial goal of this project is to get the minimum set of features implemented under the new Symfony framework:

* define Language - DONE
* create a text - DONE
* rework rendering - DONE
* right pane word definition pop-up
** create terms and multiword terms
* import a long text file
* ajax set statuses
* bulk status updates
* move parsing stuff to separate class with smaller interface.
** Note: after creating a multiword expression, just re-render the current page - the "insert expressions (mode)" stuff is just too messy.
** Remove all deps on database_connection.php in this class.
** On text open, just re-parse it.  This takes care of any parsing and expression issues, and should speed up status updates for the current page too.
* settings?  Not sure if needed at the moment.

A lot of things will be removed at first, and *might* be re-introduced after the MVP is done.  They should be removed for the MVP so that the code becomes more manageable, and old/really bad stuff can be tossed.

* all old pages and inc files.
* themes
* rss feeds
* all anki-like testing
* texts.TxAnnotatedText field, and all "Improved annotation" eg. `/print_impr_text.php?text=1` (this really needs to be reworked, it stores data in a structured but non-intuitive way in the Texts table ...)
* db import and export
* old documentation
* docker
* multi-word edit screen `/edit_words.php`
* bulk translation
* overlib


## MVP Phase 2

* statistics
* import terms
* help/info
* manage term tags
* manage text tags

## Small projects

* Listing of text tags with link to texts with tag
* Add repeatable migrations to db migrator
* Move trigger creation to repeatable migration
* Fix docs for exporting a DB backup, skip triggers (trigger in dumpfile was causing mysql to fail on import)
* Word list (very big for DataTables, will need to ajax things in)

## Unfinished work on existing pages

Some pages are sufficiently implemented for the MVP (minimum viable product) stage of Lute, but may need to be added to match the existing LWT feature set.

### Language listing `/language/`

* Add "reparse all texts".  Will actually be handled by the TextRepository.
* Possibly add link to get all texts for the language (maybe not needed, as the text listing can be filtered)
* Link to get all terms
* Link to all RSS feeds?
* Delete Language.  This is a big deal, will cause loss (or archive) of all texts.  Much better would be to just be to archive/deactivate the language (which would archive all the texts).

### Add/edit text `/text/1/edit`

* Check text length constraint - 65K too long.
* Playing media in /public/media or from other sources.

## Longer term

* remove all existing testing code

The current testing code isn't the best.  It assumes that it should just potentially test everything.  I should be able to select the terms I want to test, especially parent terms that implicitly include many child sentences.  Needs a big rearchitecture.

## Done

* Text tags
* Text actions from list: edit text from list, archive, unarchive.
* Language input form styling
