<?php

/**
 * \file
 * \brief DB variables when LWT is integrated with WordPress.
 * 
 * @author https://sourceforge.net/projects/lwt/ LWT Project
 * @since  1.5.5
 */

/** These 'global' statements are required ... don't delete them! */
global $server;
global $userid;
global $passwd;
global $dbname;


// database server
$server = "127.0.0.1:8889";

// database userid
$userid = "root";

// database password
$passwd = "root";

// database name "root"
// (the wp user ID is appended to the end of the name
// so each user gets their own database instance.)
$rootdbname = "wordpress_lwt";

// DO NOT REMOVE THE NEXT LINE, it is required for the wordpress integration!
require_once 'inc/wp_logincheck.php';

?>
