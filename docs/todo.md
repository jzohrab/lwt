# To-do

(This list is likely incomplete.)  Sorted more-or-less by priority.  As things are done, they're moved to the Done list and possibly referenced in the top-level README.

## MVP

The initial goal of this project is to get the minimum set of features implemented under the new Symfony framework:

* define Language - DONE
* create a text - DONE
* rework rendering
* import a long text file
* create terms and multiword terms
* set statuses
* manage term tags
* bulk status updates

A lot of things will be removed at first:

* rss feeds
* all anki-like testing
* db import and export
* old documentation
* docker
* texttag lists
* multi-word edit screen
* bulk translation

and they might be implemented at some point in the future.

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
