# Database migrations

This folder contains simple db scripts and and migrations for db schema management for LWT.

The schema is managed following the ideas outlined at https://github.com/jzohrab/DbMigrator/blob/master/docs/managing_database_changes.md.

* All migrations are stored in the `migrations` folder, and are applied once only, in filename-sorted order.
* The main class `mysql_migrator.php` is lifted from https://github.com/jzohrab/php-migration.

## Changes are automatically applied once per user session!

The DB migrations are applied automatically during LWT operation (see the end of the file `inc/database_connect.php`), but are only applied _once_ per user session!  So, if you're developing or merge in changes and you've already run the code, that session var is set, and you'll need to apply new changes manually (see below).

## Usage

### Applying the migrations manually

```
$ composer db:migrate
```

### Creating new migration scripts

```
$ composer db:newscript <some_name_here>
```

These migration scripts should be committed to the DB.