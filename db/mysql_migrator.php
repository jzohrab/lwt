<?php

class MysqlMigrator {
  var $dbname;
  var $db;

  function __construct() {
    date_default_timezone_set('UTC');
  }

  function process($location, $host, $db, $user, $pass) {
    $this->log("mysql migrate (location: '$location', host: '$host', db: '$db', user: '$user', pass: '$pass')");
    $this->dbname = $db;
    $this->create_connection($host, $db, $user, $pass);
    if (is_dir($location)) {
      $this->process_folder($location);
    } else {
      $this->process_file($location);
    }
    $this->db->close();
  }

  private function log($message) {
    echo "$message\n";
  }

  private function create_connection($host, $db, $user, $pass) {
    $this->log("connecting to db: mysqli($host, $user, $pass, $db)");
    $this->db = new mysqli($host, $user, $pass, $db);
    if ($this->db->connect_errno) {
        $n = $mysqli->connect_errno;
        $e = $mysqli->connect_error;
        $this->log("Failed to connect to MySQL: ({$n}) {$e}\n");
        die;
    }
    $this->db->options(MYSQLI_READ_DEFAULT_GROUP,"max_allowed_packet=128M");
  }

  function process_folder($folder) {
    $this->create_migrations_table_if_needed();
    $this->log("processing folder: $folder");
    chdir($folder);
    $files = glob("*.sql");
    $outstanding = array_filter($files, fn($f) => $this->should_apply($f));
    foreach ($outstanding as $file) {
      try {
        $this->process_file($file);
      }
      catch (Exception $e) {
        $msg = $e->getMessage();
        echo "\nFile {$file} exception:\n{$msg}\n";
        echo "Quitting.\n\n";
        die;
      }
      $this->add_migration_to_database($file);
    }
  }

  function create_migrations_table_if_needed() {
    $check_sql = "SELECT TABLE_NAME FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = '{$this->dbname}' AND TABLE_NAME = '_migrations'";
    $res = $this->db->query($check_sql);
    if ($res->num_rows != 0) {
      return;
    }
    $this->log("Creating _migrations table in database");
    if (!$this->db->query("CREATE TABLE _migrations (filename varchar(255), PRIMARY KEY (filename))")) {
      $this->log("Table creation failed: (" . $this->db->errno . ") " . $this->db->error);
      die;
    }
  }

  function should_apply($filename) {
    if (is_dir($filename)) {
      return false;
    }
    $sql = "select filename from _migrations where filename = '{$filename}'";
    $res = $this->db->query($sql);
    return ($res->num_rows == 0);
  }

  function add_migration_to_database($file) {
    if (!$this->db->query("INSERT INTO _migrations values ('$file')")) {
      $this->log("Table insert failed: (" . $this->db->errno . ") " . $this->db->error);
      die;
    }
  }

  function process_file($file) {
    $this->log("  running $file");
    $commands = file_get_contents($file);

    /* execute multi query */
    $this->db->multi_query($commands);
    do {
      $this->db->store_result();
      if ($this->db->info) {
        $this->log($this->db->info);
      }
    } while ($this->db->next_result());

    if ($this->db->error) {
      $this->log("error:");
      $this->log($this->db->error);
      die;
    }
  }

}

?>