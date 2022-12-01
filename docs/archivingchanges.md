# Archiving changes.

LWT used to have separate tables for archived texts and their tags, and various functions for archiving and unarchiving.  It was brittle and contained a lot of duplicate code.

This project has a new boolean field, `texts.TxArchived`, which should be set when a text is archived.

The database migrations move any `archivedtexts` records back into the `texts`, along with their tags.