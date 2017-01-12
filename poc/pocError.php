<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

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

class pocApplicationError extends pocError {

  private static $applicationErrors = array();

  public function __construct($row = array()) {
    $row["id"] = 400;
    $row["name"] = "Application Error";
    parent::__construct($row);
    $this->content = $row["content"];
    self::$applicationErrors[] = $this;
  }

  public function __toString() {
    return $this->content;
  }

  public static function hasError() {
    return count(self::$applicationErrors) > 0;
  }

  public static function fetchAll() {
    $errors = self::$applicationErrors;
    self::$applicationErrors = array();
    return $errors;
  }

  protected static function getCreateParams() {
    return array("content" => "");
  }

}

/******************************************************************************/

class pocException extends Exception { }

?>