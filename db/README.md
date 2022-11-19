# Database migrations

This folder contains simple db scripts and and migrations for db schema management for LWT.

The schema is managed following the ideas outlined at https://github.com/jzohrab/DbMigrator/blob/master/docs/managing_database_changes.md.

All migrations are stored in the `migrations` folder, and are applied once only, in filename-sorted order.

The main class `mysql_migrator.php` is lifted from https://github.com/jzohrab/php-migration.


## Applying the migrations

```
$ php db/apply_migrations.php 
```

## Creating new migration scripts

```
$ php db/create_migration_script.php <some_name_here>
```

These migration scripts should be committed to the DB.