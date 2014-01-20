<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocSelect implements IteratorAggregate {

  private $poc = NULL;
  private $pocMode = poc::NO_MODE;
  private $proc = "";
  private $count = 0;

  public function __construct($poc = ".", $selectMode = "flat", $pocMode = poc::NO_MODE,
      $nameLike = "", $contentLike = "") {
    if (!is_a("poc", $poc)) {
      if (!$poc = poc::open($poc)) {
        pocError::create(404, "Not Found", "pocSelect->__construct can't open poc.");
        return NULL;
      }
    }
    if (!$poc->selectPriv) {
      pocError::create(401, "Unauthorized", "pocSelect->__construct");
      return NULL;
    }
    $this->poc = $poc;
    switch ($selectMode) {
      case "flat":
        $this->proc = "pocSelectFlat";
        break;
      case "tree":
        $this->proc = "pocSelectTree";
        break;
      case "stem":
        $this->proc = "pocSelectStem";
        break;
      default:
        pocError::create(400, "Bad Request", "Bad select mode '$selectMode'.");
        return NULL;
    }
    $this->pocMode = $pocMode;
    pocEnv::call("pocSelectInit");
    if ($pocMode)
      $this->where("(poc.mode & $pocMode) > 0");
    if ($nameLike)
      $this->where("poc.name LIKE($nameLike)");
    if ($contentLike)
      $this->where("poc.content LIKE($contentLike)");
  }

  public function __get($key) {
    return $this->$key;
  }

  public function __set($key, $value) { }

  public function getIterator() {
    return new ArrayIterator($this->select());
  }

  public function select() {
    $args = func_get_args();
    if (count($args)) {
      pocEnv::call("pocSelectResetColumns");
      foreach ($args as $col) 
        if (strpos(strtoupper($col), "GROUP BY ") === 0) {
          pocEnv::call("pocSelectInsertGroupBy", array(self::parseClause(preg_replace('/^GROUP\s+BY\s+/i', "", $col))));
        } elseif ((strpos(strtoupper($col), "ORDER BY ") === 0)) {
          pocEnv::call("pocSelectInsertOrderBy", array(self::parseClause(preg_replace('/^ORDER\s+BY\s+/i', "", $col))));
        } else {
          pocEnv::call("pocSelectInsertColumn", array(self::parseClause($col)));
        }
      $mode = 0;
    } else {
      $mode = 1;
    }
    $result = pocEnv::call($this->proc, array($this->poc->id, $this->pocMode & poc::NAVI_MODE ? 1: 0, $mode));
    $this->count = count($result);
    return $result;
  }

  public function where($where, $whereMode = "AND") {
    pocError::fetch("pocSelect->where($where)");
    if (!$where)
      return TRUE;
    $whereMode = strtoupper($whereMode);
    if (!($whereMode == "AND" || $whereMode == "OR")) {
      pocError::create(400, "Bad Request", "Unknown where mode '$whereMode'.");
      return FALSE;
    }
    $where = pocSelect::parseClause($where);
    if (pocError::hasError())
      return FALSE;
    pocEnv::call("pocSelectInsertWhere", array($where, $whereMode));
    return !pocError::hasError();
  }

  public static function parseClause($clause, $substitute = "") {
    $tokens = token_get_all("<?PHP $clause");
    $clause = "";
    while (!is_array($token) || $token[0] != T_OPEN_TAG)
      $token = array_shift($tokens);
    foreach ($tokens as $token) {
      if (is_array($token)) {
        $token = $token[1];
      } else {
        switch ($token) {
          case '$':
            $token = $substitute;
            break;
          case ';':
            pocError::create(400, "Bad Request", "Evil token '$token', after '$where'");
            return NULL;
        }
      }
      $clause .= $token;
    }
    $clause = preg_replace('/\.content\./', ".", $clause);
    return $clause;
  }

}

/******************************************************************************/

class pocSelectJoin {

  const DEFAULT_AS = "ttj";

  private static $number = 0;

  private $as = "";
  private $asContent = "";

#                 __construct("poc", $pocSelectJoin, $name, $whereMode = "AND") {
  public function __construct($class, $name, $where = "", $whereMode = "AND") {
    pocError::fetch("pocSelectJoin->__construct($class...)");
    $this->as = self::DEFAULT_AS . self::$number++;
    $this->asContent = "$this->as.content";
    list($join, $class) = explode(" ", $class);
    if (!$class) {
      $class = $join;
      $join = "$this->asContent IS NOT NULL";
    } else {
      $join = "";
    }
    list($class, $on) = explode(".", $class);
    try {
      $table = $class::getTableName();
    } catch (Exception $e) {
      pocError::create(400, "Bad Request", "Can't join class '$class'.");
      return NULL;
    }
    #
    switch ($on) {
      case "";
        $on = "creditId";
        break;
      case "creditId";
      case "debitId";
      case "voucherId";
        break;
      case "both";
        $on = "creditId = poc.id OR $this->as.debitId";
        break;
      default;
        pocError::create(400, "Bad Request", "Unknown field '$on'.");
        return NULL;
    }
    #
    if (!preg_match('/^[\w\d]+$/', $name)) {
      pocError::create(400, "Bad Request", "Bad attribute name '$name'.");
      return NULL;
    }
    #
    if ($join)
      $this->where($join);
    if ($where)
      $this->where($where, $whereMode);
    #
    if (pocError::hasError())
      return NULL;
    pocEnv::call("pocSelectInsertJoin", array($table, $this->as, $on, $class, $name));
  }

  public function __get($key) {
    return $this->$key;
  }

  public function __set($key, $value) { }

  public function __toString() {
    return $this->asContent;
  }

  public function where($where, $whereMode = "AND") {
    pocError::fetch("pocSelectJoin->where($where)");
    if (!$where)
      return TRUE;
    $whereMode = strtoupper($whereMode);
    if (!($whereMode == "AND" || $whereMode == "OR")) {
      pocError::create(400, "Bad Request", "Unknown where mode '$whereMode'.");
      return FALSE;
    }
    if (preg_match('/^[\w\.]+$/', $where))
      $where = "$this->asContent = " . pocEnv::quote($where);
    else
      $where = pocSelect::parseClause($where, $this->asContent);
    if (pocError::hasError())
      return FALSE;
    pocEnv::call("pocSelectInsertWhere", array($where, $whereMode));
    return !pocError::hasError();
  }

}

?>