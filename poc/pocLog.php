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

?>