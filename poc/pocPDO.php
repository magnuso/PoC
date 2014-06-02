<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

abstract class pocPDO extends PDO {

  # overwrite constructor!

  # overwrite
  public static function dsn($name, $host, $port) { return "a proper dsn-string"; }

}

/******************************************************************************/

class pocMySqlPDO extends pocPDO {

  public function __construct($dsn, $username, $dbPassword, $driver_options) {
    $driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
    parent::__construct($dsn, $username, $dbPassword, $driver_options);
    $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  public static function dsn($name, $host, $port) {
    return $port ? "mysql:dbname=$name;host=$host;port=$port": "mysql:dbname=$name;host=$host";
  }

}

?>