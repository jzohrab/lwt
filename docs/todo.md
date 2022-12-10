# To-do

(This list is likely incomplete.)  Sorted more-or-less by priority.  As things are done, they're moved to the Done list and possibly referenced in the top-level README.

## To-do

* Listing of text tags with link to texts with tag
* Language input form styling
* Add repeatable migrations to db migrator
* Move trigger creation to repeatable migration
* Fix docs for exporting a DB backup, skip triggers (trigger in dumpfile was causing mysql to fail on import)
* Word list (very big for DataTables, will need to ajax things in)

## Longer term

* remove all existing testing code

The current testing code isn't the best.  It assumes that it should just potentially test everything.  I should be able to select the terms I want to test, especially parent terms that implicitly include many child sentences.  Needs a big rearchitecture.

## Done

* Text tags
* Text actions from list: edit text from list, archive, unarchive.
