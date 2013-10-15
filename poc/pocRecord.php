<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocRecord extends pocRow {

  # dependencies:
  #   uses pocEnv, pocDbh

  protected static $cache = array();
  protected static $lastInserted = NULL;

  protected $identifier;
  protected $userId = 1;
  protected $userName = "admin";
  protected $created = 0;
  protected $createdById = 1;
  protected $createdByName = "admin";
  protected $modified = 0;
  protected $modifiedById = 1;
  protected $modifiedByName = "admin";

  protected $cacheMe = FALSE;

  protected $runPriv = FALSE;
  protected $openPriv = TRUE;
  protected $selectPriv = TRUE;
  protected $insertPriv = FALSE;
  protected $updatePriv = FALSE;
  protected $deletePriv = FALSE;

  public function __construct($row = array()) {
    $identifier = get_class($this) . ":$row[id]";
    if ($row["updateFlag"]) {
      if ($original = self::$cache[$identifier]) {
        foreach ($row as $k => $v)
          $original->$k = $v;
      } else {
        pocError::create(404, "Not found", "for cache-update: $identifier");
      }
    } else {
      parent::__construct($row);
      $this->identifier = $identifier;
      if ($this->cacheMe && !self::$cache[$identifier])
        self::$cache[$identifier] = $this;
      if ($this->insertFlag)
        self::$lastInserted = $this;
    }
  }

  public function __toString() {
    if ($this->openPriv)
      return $this->content;
  }

  # db
  public function insert($name) {
    $class = get_class($this);
    if ($proc = $class::getInsertProc()) {
      pocError::fetch("$class->insert($name)");
      self::$lastInserted = NULL;
      $this->name = $name;
      pocEnv::call($proc, $this->this2params($class::getInsertParams()));
      return self::$lastInserted;
    }
  }

  public function update() {
    if (!$this->id)
      return;
    $class = get_class($this);
    if ($proc = $class::getUpdateProc()) {
      pocError::fetch("$class->update($this->id)");
      pocEnv::call($proc, $this->this2params($class::getUpdateParams()));
      return !pocError::hasError();
    }
  }

  public function delete() {
    if (!$this->id)
      return;
    $class = get_class($this);
    if ($proc = $class::getDeleteProc()) {
      pocError::fetch("$class->delete($this->id)");
      pocEnv::call($proc, array($this->id));
      if ($ok = !pocError::hasError())
        unset(self::$cache[$this->identifier]);
      return $ok;
    }
  }

  protected function select() { return array(); }

  #
  protected function attributeAttach($attribute) { }

  # public static
  public static function open($identifier = 0, $fresh = FALSE) {
    list($class, $id) = explode(":", $identifier);
    if (!$id)
      return new $class();
    if ($row = self::$cache[$identifier]) {
      if ($fresh)
        unset(self::$cache[$identifier]);
      else
        return $row;
    }
    if ($proc = $class::getOpenProc()) {
      pocError::fetch("$class::open($identifier)");
      pocEnv::call($proc, array($id));
      return self::$cache[$identifier];
    }
  }

  # "abstract" static
  protected static function getOpenProc() {}
  protected static function getInsertProc() {}
  protected static function getUpdateProc() {}
  protected static function getDeleteProc() {}

  protected static function getInsertParams() { return array("name", "title", "content"); }
  protected static function getUpdateParams() { return array("id", "name", "title", "content"); }

  # create params
  protected static function getCreateParams() {
    return array("name" => "", "title" => "", "content" => "");
  }

  # create params
  public static function dump() {
    foreach (self::$cache as $k => $v)
      pocEnv::echoHtml("[$k] => $v" . PHP_EOL);
  }

}

?>