<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

interface IPocRow { }

/******************************************************************************/

class pocRow implements IPocRow {

  protected $id = 0;
  protected $className = "";

  public $name = "";
  public $title = "";
  public $content = "";

  protected $returnRow = TRUE;

  public function __construct($row = array()) {
    foreach ($row as $k => $v)
      $this->$k = $v;
  }

  public function __get($key) {
    switch ($key) {
      case "getTitle":
        return $this->title ? $this->title : $this->name;
      default:
        return $this->$key;
    }
  }

  public function __set($key, $value) { }

  public function __toString() {
    return $this->content;
  }

  public function run() {
    echo $this->content;
  }

  # tool
  protected function this2params($paramsNames) {
    $result = array();
    foreach ($paramsNames as $key)
      $result[] = $this->$key;
    return $result;
  }

  # public create
  public static function create() {
    $class = get_called_class();
    $params = func_get_args();
    $row = array();
    foreach ($class::getCreateParams() as $k => $v)
      $row[$k] = isset($params[0]) ? array_shift($params) : $v;
    return new $class($row);
  }

  # static
  protected static function getCreateParams() {
    return array("name" => "", "content" => "");
  }

}

/******************************************************************************/

class pocResult extends pocRow implements ArrayAccess, IteratorAggregate {

  protected $row;

  public function __construct($row = array()) {
    $this->row = $row;
  }

  public function count() { return count($this->row); }

  # ArrayAccess
  public function offsetSet($name, $value) { $this->row[$name] = $value; }
  public function offsetExists($name) { return isset($this->row[$name]); }
  public function offsetUnset($name) { unset($this->row[$name]); }
  public function offsetGet($name) { return $this->row[$name]; }

  # IteratorAggregate for attributes
  public function getIterator() { return new ArrayIterator($this->row); }

}

/******************************************************************************/

class pocArray extends pocRow implements Iterator {

  private $contentIterator;

  public function shift() {
    if (!is_array($this->content))
      $this->content = $this->content ? array($this->content) : array();
    return array_shift($this->content);
  }

  public function unshift() {
    if (!is_array($this->content))
      $this->content = $this->content ? array($this->content) : array();
    foreach (func_get_args() as $content)
      unshift($this->content, $content);
  }

  public function push() {
    if (!is_array($this->content))
      $this->content = $this->content ? array($this->content) : array();
    foreach (func_get_args() as $content)
      $this->content[] = $content;
  }

  public function pop() {
    if (!is_array($this->content))
      $this->content = $this->content ? array($this->content) : array();
    return array_pop($this->content);
  }

  # interface Iterator
  public function rewind() {
    if (!is_array($this->content))
      $this->content = $this->content ? array($this->content) : array();
    $this->contentIterator = array_keys($this->content);
  }

  public function current() {
    return $this->content[$this->contentIterator[0]];
  }

  public function key() {
    return $this->contentIterator[0];
  }

  public function next() {
    array_shift($this->contentIterator);
  }

  public function valid() {
    return count($this->contentIterator) > 0;
  }

  # static
  protected static function getCreateParams() {
    return array("name" => "", "content" => array());
  }

}

/******************************************************************************/

class pocRowCollection extends pocArray {

  private static $currectCollection = NULL;

  private $saveCollection = NULL;

  public function __construct($row = array()) {
    parent::__construct($row);
    self::push($this);
    $this->saveCollection = self::$currectCollection;
    self::$currectCollection = $this;
  }

  public static function pushRow($row) {
    if (self::$currectCollection)
      self::$currectCollection->push($row);
  }

  protected static function stopCollecting() {
    if (self::$currectCollection)
      self::$currectCollection = self::$currectCollection->saveCollection;
  }

}

/******************************************************************************/

class pocRowCollectionStop extends pocRowCollection {

  public function __construct($row = array()) {
    $this->returnRow = FALSE;
    self::stopCollecting();
  }

}

/******************************************************************************/

class pocDefine extends pocRow {

  public function __construct($row = array()) {
    $this->returnRow = FALSE;
    unset($row["className"]);
    foreach ($row as $k => $v) {
      define($k, $v);
      pocLog::create(__CLASS__, "$k = $v");
    }
  }

  protected static function getCreateParams() {
    return new self(array_shift(func_get_args()));
  }

}

?>