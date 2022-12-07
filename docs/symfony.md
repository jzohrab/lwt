# Moving to Symfony

Originally, I had hoped to restructure the code per the ideas in [on-structuring-php-projects](https://www.nikolaposa.in.rs/blog/2017/01/16/on-structuring-php-projects/), but then felt that this was just delaying the much-needed massive code rewrite.

I started to introduce a "no framework" restructure, per [the "no framework" tutorial](https://github.com/PatrickLouys/no-framework-tutorial), but after a few steps I felt that I was merely re-implementing the Symfony framework.

I looked at a few frameworks (laravel, yii), and based on gut feeling only, went with Symfony.

## Overview

Fundamentally, it appears to me that the LWT code really consists of a few core things:

* parsing texts into tokens, which users can then mark as "words" for known topics.
* the reading pane, where users interact with texts.

It appears that the data model for the above is decent: `texts`, `words`, `textitems2`, etc are decent structures (even if some of the table names are odd, for historical reasons).  There's no need to change much there.

The parsing and pane interaction have some hairy javascript, php, and sql, so those just need some more tests to ensure things are good.

Pretty much everything else is just "CRUD" (i.e., it's a simple database "Create-Retrieve-Update-Delete" app).  Most of that can be handled with simpler code, or generated code.

## Current state

(as of Dec 7, 2022, and hopefully things have changed since then)

* introduced a "front controller", so everything is handled by `public/index.php` at first.
* the app needs to be entered in `/etc/hosts`, and the host configured in Apache vhosts, for URL rewriting to work.

### App pages

* The text listing is at `/text/`
* All the legacy pages are currently still in place and are accessed from the site root (e.g., `/edit_texts.php`), and should still work.
* If any page looks terrible, it's incomplete and will likely not work!  I'll try to break links if the accompanying page is not implemented.
