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

  private $time;
  private $level;

  public function __construct($row = array()) {
    $this->returnRow = FALSE;
    parent::__construct($row);
    $this->time = self::time();
    self::$entries[] = $this;
  }

  public function __toString() {
    return sprintf("%s%.4f %-18s %s", str_repeat("  ", $this->level), $this->time, $this->name, $this->content);
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

  public static function dump() {
    foreach(self::$entries as $log)
      echo $log->__toString() . PHP_EOL;
    printf("now: %.4f%s", selftime, PHP_EOL);
  }

  protected static function getCreateParams() {
    return array("name" => "pocLog", "content" => "", "level" => 0);
  }

}

/******************************************************************************/

class pocWatch extends pocRow {

  private static $level = 0;
  private static $currentWatch = NULL;

  private $time;
  private $firstTime;
  private $firstLog;
  private $saveWatch = NULL;

  public function __construct($row = array()) {
    parent::__construct($row);
    $log = pocLog::create($this->name, $this->content, self::$level++);
    $this->firstTime = $this->time = $log->time;
    $this->firstLog = $log;
    if (self::$currentWatch)
      self::$currentWatch->firstLog = NULL;
    $this->saveWatch = self::$currentWatch;
    self::$currentWatch = $this;
  }

  public function __destruct() {
    self::$level--;
    if ($this->firstLog) {
      $this->firstLog->content .= sprintf(": %.4f", pocLog::time() - $this->time);
      $this->firstLog = NULL;
    } else {
      $log = pocLog::create("$this->name kill", "", self::$level);
      $log->content = sprintf("%s: %.4f total: %.4f", $this->content, $log->time - $this->time, $log->time - $this->firstTime);
    }
    self::$currentWatch = $this->saveWatch;
  }

  public function time($stop) {
    $log = pocLog::create("$this->name stop", "", self::$level - 1);
    $time = $log->time - $this->time;
    $log->content = sprintf("%s %.4f", "$this->msg $stop", $time);
    $this->time = $log->time;
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

  private $previous = NULL;

  public function __construct($row = array()) {
    $this->returnRow = FALSE;
    parent::__construct($row);
    self::$errors[] = $this;
    $this->content = "$this->id $this->name: $this->content";
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

  public static function dump() {
    foreach (self::$errors as $err)
      echo $err->__toString() . PHP_EOL;
  }

  protected static function getCreateParams() {
    return array("id" => 418, "name" => "I’m a teapot", "content" => "", "previous" => NULL);
  }

}

/******************************************************************************/

class pocException extends Exception { }

?>