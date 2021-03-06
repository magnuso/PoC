<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class poc extends pocRecord implements ArrayAccess, IteratorAggregate {

  # dependencies:
  #   pocEnv, pocPath

  const NO_PRIV = 0;
  const RUN_PRIV = 1;
  const OPEN_PRIV = 2;
  const SELECT_PRIV = 4;
  const INSERT_PRIV = 8;
  const UPDATE_PRIV = 16;
  const DELETE_PRIV = 32;
  const OWNER_PRIVS = 62;
  const OTHER_PRIVS = 6;

  const NO_MODE = 0;
  const NAVI_MODE = 1;
  const SEARCH_MODE = 2;
  const CACHE_MODE = 4;

  const MAGIC_DROP_QUEUE = "_listener";
  const VARCHAR_LIMIT = 191;

  const RESULT_MODE_OFF = 0;
  const RESULT_MODE_POCS = 1;
  const RESULT_MODE_ROWS = 2;

  const SELECT_DEFAULT_RESULT_MODE = 1; # !!! TO DO
  const SELECT_DEFAULT_POC_MODE = 1; # NAVI_MODE

  private static $cacheByPath = array();

  protected static $countSelect = 0;

  public $mode = poc::NO_MODE;

  protected $parentId = 0;
  protected $path = "";
  protected $groupId = 1;
  protected $groupName = "admin";
  protected $userPrivs = poc::OWNER_PRIVS;
  protected $groupPrivs = poc::OWNER_PRIVS;
  protected $otherPrivs = poc::OTHER_PRIVS;
  protected $children = 0;

  protected $attributes = array();
  protected $attributesCache = array();

  public function __construct($row = array()) {
    $this->cacheMe = TRUE;
    parent::__construct($row);
    self::$cacheByPath[$this->path] = self::$cache[$this->identifier];
  }

  public function __get($key) {
    switch ($key) {
      case "parent":
        return poc::open($this->parentId);
      case "count":
        return self::$countSelect;
      case "size":
        return strlen($this->content);
      default:
        return parent::__get($key);
    }
  }

  # magic
  public function __call($name, $params) {
    if ($method = $this->drop($name, self::MAGIC_DROP_QUEUE)) {
      if ($method = $method->debit)
        return $method->run($this, $params);
      else
        pocError::create(400, "Bad Request", "Magic Call: $this->path" . "->$name(..)");
    } else {
      return parent::__call($name, $params);
    }
  }

  public function callParent($that, $params) {
    if ($listener = $this->climb(poc::MAGIC_DROP_QUEUE)) {
      if ($listener = $listener->debit) {
        if ($method = $listener->drop($this->name, self::MAGIC_DROP_QUEUE)) {
          if ($method = $method->debit) {
            return $method->run($that, $params);
          }
        }
      }
    }
  }

  # arrayaccess to attributes
  #
  #  $poc[] = $attribute; # inserts attribute. name from $attribute->name.
  #  $poc["name"] = $attribute; # inserts attribute. name from "name".
  #  # name doesn't has to be unique.
  #  # inserting attributes with the same name will create an array of attributes.
  #
  #  $poc["name"] = $scalar; # updates attribute. error occurs if name is not unique.
  #  # no check on scalar!!!
  #
  #  $attribute = $poc["name"]; # get attribute or array of attributes if name is not unique.
  #  $scalar = $poc["name"]->content; # get attribute-content. error occurs if name is not unique.
  #
  #  foreach ($poc["name"] as $attribute) # will always work,
  #  # because for this single purpose class pocAttribute implements IteratorAggregate too.

  public function offsetSet($name, $value) {
    if (is_a($value, "pocAttribute")) {
      if ($name)
        $value->name = $name;
      $value->insert($this);
    } else {
      $this->attributes[$name]->content = $value;
      $this->attributes[$name]->update();
    }
  }

  public function offsetExists($name) {
    return isset($this->attributes[$name]);
  }

  public function offsetUnset($name) {
    foreach ($this->attributes[$name] as $attribute)
      $attribute->delete();
  }

  public function offsetGet($name) {
    if (isset($this->attributes[$name]))
      return $this->attributes[$name];
  }

  # IteratorAggregate for attributes
  public function getIterator() {
    uasort($this->attributesCache, function ($a, $b) { return strcmp($a->name, $b->name); } );
    return new ArrayIterator($this->attributesCache);
  }

  # catch attributes
  public function climb($attribute) {
    if (array_key_exists($attribute, $this->attributes))
      return $this->attributes[$attribute];
    if ($this->parentId)
      return $this->parent->climb($attribute);
  }

  public function drop($attribute, $queue = self::MAGIC_DROP_QUEUE, $climb = TRUE) {
    if (array_key_exists($attribute, $this->attributes))
      return $this->attributes[$attribute];
    if (isset($this->attributes[$queue]))
      foreach ($this->attributes[$queue] as $listener)
        if ($found = $listener->debit->drop($attribute, $queue, FALSE))
          return $found;
    if ($climb && $this->parentId)
      return $this->parent->drop($attribute, $queue);
  }

  # poc
  public function run() {
    if ($this->runPriv)
      return pocRun::run($this, func_get_args());
    else
      pocError::create(403, "Forbidden");
  }

  # db
  public function insert($path) {
    $path = new pocPath($path);
    if (!$path->name) {
      pocError::create(400, "Bad Request", "poc->insert($path) has no name.");
      return NULL;
    }
    if (!$parent = poc::open($path->parent)) {
      pocError::create(404, "Not Found", "poc->insert($path) can't open parent.");
      return NULL;
    }
    $this->parentId = $parent->id;
    if ($newPoc = parent::insert($path->name)) {
      foreach ($this as $attribute)
        $attribute->insert($newPoc);
    }
    return $newPoc;
  }

  public function update() {
    unset(self::$cacheByPath[$this->path]);
    return parent::update();
  }

  public function delete() {
    if ($ok = parent::delete()) {
      foreach($this->attributesCache as $attribute)
        unset(self::$cache[$attribute->identifier]);
      $this->attributes = array();
      $this->attributesCache = array();
    }
    return $ok;
  }

  public function copy($to) {
    pocError::fetch("poc->copy($this->path, $to)");
    $to = self::open($to);
    if ($this->updatePriv && $to->insertPriv)
      pocEnv::call("pocPocCopy", array($this->id, $to->id));
    else
      pocError::create(401, "Unauthorized");
    return !pocError::hasError();
  }

  public function move($to) {
    pocError::fetch(__CLASS__ . "::" . __METHOD__ . "($this->id)");
    $to = self::open($to);
    if ($this->updatePriv && $to->insertPriv)
      pocEnv::call("pocPocMove", array($this->id, $to->id));
    else
      pocError::create(401, "Unauthorized");
    return !pocError::hasError();
  }

  public function chown($user, $group, $recursive) {
    pocError::fetch(__CLASS__ . "::" . __METHOD__ . "($this->id)");
    pocEnv::call("pocPocChown", array($this->id, $user ? $user : "", $group ? $group : "", $recursive ? 1 : 0));
    return !pocError::hasError();
  }

  public function chmod($userPrivs, $groupPrivs, $otherPrivs, $recursive) {
    pocError::fetch(__CLASS__ . "::" . __METHOD__ . "($this->id)");
    pocEnv::call("pocPocChmod", array($this->id, $userPrivs, $groupPrivs, $otherPrivs, $recursive ? 1 : 0));
    return !pocError::hasError();
  }

  # select($resultClass, $pocMode, $nameLike, $contentLike, );
  # also creates in the db a temporary table of results for subsequent finds.
  # ATTENTION! the temporary table can't be nested.
  #
  public function select($selectMode = "flat", $pocMode = poc::NO_MODE, $nameLike = "", $contentLike = "") {
    pocError::fetch("poc->select($this->path)");
    return new pocSelect($this, $selectMode, $pocMode, $nameLike, $contentLike);
  }

  # manage attributes-cache
  protected function attributeAttach($attribute) {
    if ($this->attributesCache[$attribute->identifier])
      return;
    $this->attributesCache[$attribute->identifier] = $attribute;
    if ($this->attributes[$attribute->name]) {
      if (is_array($this->attributes[$attribute->name]))
        $this->attributes[$attribute->name][] = $attribute;
      else
        $this->attributes[$attribute->name] = array($this->attributes[$attribute->name], $attribute);
    } else {
      $this->attributes[$attribute->name] = $attribute;
    }
  }

  protected function attributeDetach($attribute) {
    unset($this->attributesCache[$attribute->identifier]);
    if (is_array($this->attributes[$attribute->name])) {
      foreach ($this->attributes[$attribute->name] as $k => $v) {
        if ($v->identifier == $attribute->identifier)
          break;
      }
      unset($this->attributes[$attribute->name][$k]);
    } else {
      unset($this->attributes[$attribute->name]);
    }
  }

  public static function open($identifier = 0, $fresh = FALSE) {
    if (!$identifier)
      return new self();
    if (strval(intval($identifier)) == $identifier) {
      return parent::open(__CLASS__ . ":$identifier", $fresh);
    } elseif (preg_match('/^poc:\d*/', $identifier)) {
      return pocRecord::open($identifier);
    } else {
      $path = new pocPath($identifier);
      if (!$identifier = $path->path)
        return new self();
      if ($poc = self::$cacheByPath[$identifier]) {
        if ($fresh)
          unset(self::$cacheByPath[$identifier]);
        else
          return $poc;
      }
      if (!$parent = self::open($path->parent))
        return NULL;
      pocError::fetch("poc::open($identifier)");
      pocEnv::call("pocPocOpenByName", array($parent->id, $path->name));
      return self::$cacheByPath[$path->path];
    }
  }

  # static
  protected static function getOpenProc() { return "pocPocOpenById"; }
  protected static function getInsertProc() { return "pocPocInsert"; }
  protected static function getUpdateProc() { return "pocPocUpdate"; }
  protected static function getDeleteProc() { return "pocPocDelete"; }

  protected static function getInsertParams() { return array("parentId", "name", "title", "content", "mode"); }
  protected static function getUpdateParams() { return array("id", "name", "title", "content", "mode"); }

  public static function getTableName() { return "pocPoc"; }

  # create params
  protected static function getCreateParams() {
    return array("name" => "", "title" => "", "content" => "", "mode" => 0);
  }

  # some tools...
  public static function rwx($priv) {
    $str = $priv & poc::RUN_PRIV? "r": "-";
    $str .= $priv & poc::OPEN_PRIV? "o": "-";
    $str .= $priv & poc::SELECT_PRIV? "s": "-";
    $str .= $priv & poc::INSERT_PRIV? "i": "-";
    $str .= $priv & poc::UPDATE_PRIV? "u": "-";
    $str .= $priv & poc::DELETE_PRIV? "d": "-";
    return $str;
  }

  public static function xyMode($mode) {
    $str = $mode & poc::NAVI_MODE?   "n": "-";
    $str .= $mode & poc::SEARCH_MODE? "s": "-";
    $str .= $mode & poc::CACHE_MODE?  "c": "-";
    return $str;
  }

}

/******************************************************************************/

class pocCountSelect extends poc {

  public function __construct($row = array()) {
    $this->returnRow = FALSE;
    self::$countSelect = $row["count"];
  }

  public static function create() { return NULL; }

}

?>