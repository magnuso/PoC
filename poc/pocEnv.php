<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocEnv extends pocRow {

  private static $dbh = NULL;
  private static $singleton = NULL;

  public static $env = array();
  public static $request = array();
  public static $session = array();
  public static $user = array();

  public static $urlBase = ""; # !!!

  public function __construct($row = array()) {
    if (self::$dbh || self::$singleton) {
      pocError::create(403, "Forbidden", "pocEnv is singleton");
      return NULL;
    }
    self::$singleton = $this;
    $this->returnRow = FALSE;
  }

  public function __destruct() {
    if (self::$dbh && _POC_SESSION_ID_)
      self::call("pocSessionSave", array(serialize(self::$session), serialize(self::$user)));
    self::$dbh = NULL;
  }

  public static function create() {
    if (self::$dbh || self::$singleton) {
      pocError::create(423, "Locked", "pocEnv set");
      return;
    }
    # singleton
    new self();
    #
    pocError::fetch("pocEnv::create()");
    $sessionId = array_shift(func_get_args());
    # one more time UTF-8
    mb_internal_encoding(_POC_MB_ENCODING_);
    # fix $_REQUEST
    if (get_magic_quotes_gpc())
      array_walk_recursive(self::$request, function(&$v) { $v = stripslashes($v); });
    # fix PATH_INFO
    self::$env["PATH_INFO"] = pocPath::trim(self::$env["PATH_INFO"]);
    # set base url
    self::$urlBase = self::makeHttpBase();
    # db
    $dbhClass = self::$session["pocPDOClass"];
    self::$dbh = new $dbhClass($dbhClass::dsn(self::$session["dbName"], self::$session["dbHost"], self::$session["dbPort"]),
      self::$session["dbUser"], self::$session["dbPassword"], self::$session["dbOptions"]);
    # session
    self::call("pocSessionStart", array($sessionId, self::$request["logout"] ? 1 : 0, self::$request["sessionMode"],
      self::$session["sessionExpires"], self::$session["cookieExpires"], self::$request["login"], self::$request["passw"]));
  }

  # call db
  public static function call($procedure, $args = array()) {
    if (pocError::hasError()) {
      pocError::create(400, "Bad Request", "Fetch Errors before DB-Call");
      return array();
    }
    $call = "CALL $procedure(" . implode(",", array_fill(0, count($args), "?")) . ")";
    pocError::mark($call);
    $watch = pocWatch::create("call", $call);
    try {
      self::$dbh->beginTransaction();
      $q = self::$dbh->prepare($call);
      $q->execute($args);
    } catch (Exception $e) {
      try {
        self::$dbh->rollBack();
      } catch (Exception $e) { }
      pocError::create(500, "SQL-Error", $e->getMessage());
    }
    $watch->time("execute");
    $rows = array();
    $rowOK = TRUE;
    try {
      do {
        while ($row = $q->fetch()) {
          $rowOK = $row["className"];
          $rows[] = $row;
        }
      } while ($q->nextRowset());
    } catch (Exception $e) {
      pocError::mark("# " . $e->getMessage());
    }
    $watch->time("fetch");
    try {
      if ($rowOK) {
        self::$dbh->commit();
      } else {
        self::$dbh->rollBack();
      }
    } catch (Exception $e) {
      pocError::mark("#" . $e->getMessage());
    }
    $watch->time("close");
    $result = array();
    foreach ($rows as $row) {
      if (!$row["className"])
        $row["className"] = "pocError";
# print_r($row);
      $row = new $row["className"]($row);
      if ($row->returnRow)
        $result[] = $row;
    }
    $watch->time("create");
    return pocError::hasError() ? array() : $result;
  }

  protected static function quote($string) {
    return self::$dbh->quote($string);
  }

  # html output
  public static function html($text) {
    return htmlspecialchars($text);
  }

  public static function html2br($text) {
    return nl2br(htmlspecialchars($text));
  }

  public static function echoHtml($text) {
    echo htmlspecialchars($text);
  }

  public static function echoHtml2br($text) {
    echo nl2br(htmlspecialchars($text));
  }

  # http header
  public static function header($header = _POC_HTML_HEADER_) {
    header($header);
  }

  # http base
  public static function makeHttpBase() {
    return strtolower(array_shift(explode("/", self::$env["SERVER_PROTOCOL"])))
      . "://" . self::$env["HTTP_HOST"] . self::$env["SCRIPT_NAME"];
  }

  # started
  public static function started() {
    return self::$dbh ? TRUE : FALSE;
  }

  # exit
  public static function quit($status = 0) {
    self::$singleton = NULL;
    exit($status);
  }

  # debug
  public static function pre_r($r) {
    echo PHP_EOL . "<pre>";
    self::echoHtml(print_r($r, TRUE));
    echo "</pre>" . PHP_EOL;
  }

  # dump
  public static function dump() {
    echo "pocEnv::\$env". PHP_EOL;
    print_r(self::$env);
    echo "pocEnv::\$request". PHP_EOL;
    print_r(self::$request);
    echo "pocEnv::\$session". PHP_EOL;
    print_r(self::$session);
    echo "pocEnv::\$user". PHP_EOL;
    print_r(self::$user);
    echo PHP_EOL;
  }

}

/******************************************************************************/

class pocSessionInit extends pocDefine {

  private static $sessionSet = FALSE;

  public function __construct($row = array()) {
    if (self::$sessionSet) {
      pocError::create(423, "Locked", "Session set");
      return;
    }
    pocEnv::$session = array();
    if (isset($row["sessionData"])) {
      if ($row["sessionData"])
        pocEnv::$session = unserialize($row["sessionData"]);
      unset($row["sessionData"]);
    }
    if (isset($row["userData"])) {
      if ($row["userData"])
        pocEnv::$user = unserialize($row["userData"]);
      unset($row["userData"]);
    }
    parent::__construct($row);
    self::$sessionSet = TRUE;
  }

}

?>