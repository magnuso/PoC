<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocLog extends pocRow {

  private static $entries = array();
  private static $startTime = -1.0;

  protected $time;
  protected $level;

  public function __construct($row = array()) {
    $this->returnRow = FALSE;
    parent::__construct($row);
    $this->time = self::time();
    self::$entries[] = $this;
  }

  public function __toString() {
#    return sprintf("%3d %.3f %-18s %s", $this->level, $this->time, $this->name, $this->content);
    return sprintf("%s%.3f %-18s %s", str_repeat("  ", $this->level), $this->time, $this->name, $this->content);
  }

  public static function time() {
    if (self::$startTime < 0.0) {
      self::$startTime = microtime(TRUE);
      return 0.0;
    }
    return microtime(TRUE) - self::$startTime;
  }

  public static function getStartTime() {
    return self::$startTime;
  }

  public static function dump($html = FALSE) {
    $out = "";
    foreach (self::$entries as $log)
      $out .= $log->__toString() . PHP_EOL;
    $out .= sprintf("now: %.3f", self::time()) . PHP_EOL;
    if ($html)
      pocEnv::echoHtml($out);
    else
      echo $out;
  }

  protected static function getCreateParams() {
    return array("name" => "pocLog", "content" => "", "level" => 0);
  }

}

/******************************************************************************/

class pocWatch extends pocRow {

  private static $level = 0;

  private $time;
  private $lastTime;
  private $firstLog;

  public function __construct($row = array()) {
    parent::__construct($row);
    $log = pocLog::create($this->name, $this->content, self::$level++);
    $this->time = $log->time;
    $this->lastTime = $log->time;
    $this->firstLog = $log;
  }

  public function __destruct() {
    self::$level--;
    if ($this->firstLog) {
      $this->firstLog->content .= sprintf(":: %.3f", pocLog::time() - $this->time);
      $this->firstLog = NULL;
    } else {
      $log = pocLog::create("$this->name kill", "", self::$level);
      $log->content = sprintf("%.3f total: %.3f", $log->time - $this->lastTime, $log->time - $this->time);
    }
  }

  public function time($stop = "") {
    $log = pocLog::create("$this->name stop", "", self::$level - 1);
    $time = $log->time - $this->lastTime;
    $log->content = sprintf("%.3f %s", $time, "$this->msg $stop");
    $this->lastTime = $log->time;
    $this->firstLog = NULL;
    return $time;
  }

  protected static function getCreateParams() {
    return array("name" => "pocWatch", "content" => "");
  }

}

/******************************************************************************/

class pocError extends pocRow {

  private static $errors = array();
  private static $trace = array();
  private static $last = NULL;

  protected $previous = NULL;

  public function __construct($row = array()) {
    $this->returnRow = FALSE;
    parent::__construct($row);
    self::$errors[] = $this;
    if ($poc = pocRun::getLastRun())
      $poc = "in poc '$poc'";
    $this->content = "$this->id $this->name: $this->content $poc";
    pocWatch::create("Error Log", $this->content);
    if (count(self::$trace))
      $this->content .= " trace:" . PHP_EOL . "    " . implode(PHP_EOL . "    ", self::$trace);
    self::$trace = array();
    self::$last = $this;
    if ($this->id == "die")
      pocEnv::quit("Died on $this");
    elseif ($this->id >= 500)
      throw new pocException($this->__toString(), $this->id, $this->previous);
  }

  public function __toString() {
    return "pocError: " . $this->content;
  }

  public function brief() {
    $brief = array_pop(explode("*", $this->content));
    $brief = array_shift(explode(PHP_EOL, $brief));
    return "pocError: " . array_shift(explode(PHP_EOL, $this->brief)) . " - $brief";
  }

  public static function hasError() {
    return self::$last ? TRUE : FALSE;
  }

  public static function mark($mark) {
    self::$trace[] = $mark;
  }

  public static function fetch($mark = "") {
    self::$trace[] = "* $mark";
    $last = self::$last;
    self::$last = NULL;
    return $last;
  }

  public static function fetchAll() {
    $errors = self::$errors;
    self::$errors = array();
    self::$trace = array();
    self::$last = NULL;
    return $errors;
  }

  public static function dump($html = FALSE) {
    $out = "";
    foreach (self::$errors as $err)
      $out .= $err->__toString() . PHP_EOL;
    if ($html)
      pocEnv::echoHtml($out);
    else
      echo $out;
  }

  protected static function getCreateParams() {
    return array("id" => 418, "name" => "I’m a teapot", "content" => "", "previous" => NULL);
  }

}

/******************************************************************************/

class pocEcho extends pocLog {

  public function __construct($row = array()) {
    unset($row["className"]);
    $newRow = array("name" => "pocEcho", "content" => "", "level" => 0);
    if ($row["name"]) {
      $newRow["name"] = $row["name"];
      unset($row["name"]);
    }
    echo $newRow["name"] . PHP_EOL;
    foreach ($row as $k => $v) {
      $line = "  $k: $v" . PHP_EOL;
      echo $line;
      $newRow["content"] += $line;
    }
    parent::__construct($newRow);
  }

}

/******************************************************************************/

class pocException extends Exception { }

?>