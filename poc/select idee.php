<?PHP


  public function select() {
    $args = func_get_args();
    pocError::fetch("poc->select($this->path)");
    $selectProc = "";
    $joines = array();
    $showResults = TRUE;
    if (count($args)) {
      $select = mb_split('\s+', strtolower(trim(array_shift($args))));
      $n = count($select);
      for ($i = 0; $i < $n; $i++) {
        $mode = $select[$i];
        switch ($mode) {
          case "flat":
            $selectProc = "pocPocSelect";
            break;
          case "tree":
            $selectProc = "pocPocSelectTree";
            break;
          case "stem":
            $selectProc = "pocPocSelectStem";
            break;
          case "noresult":
            $showResults = FALSE;
            break;
          default:
            $join = "";
            if ($mode == "join") {
              $join = $select[++$i];
            } elseif ($mode == "leftjoin") {
              $mode = $select[++$i];
              $join = "LEFT ";
            } elseif ($mode == "left") {
              $mode = $select[++$i];
              $join = "LEFT ";
            }
            $relation = "creditId";
            if ($mode == "credit") {
              $mode = $select[++$i];
            } elseif ($mode == "debit") {
              $mode = $select[++$i];
              $relation = "debitId";
            } elseif ($mode == "voucher") {
              $mode = $select[++$i];
              $relation = "voucherId";
            }
            if (class_exists($mode)) {
              $joines[] = array($mode, count($args) ? array_shift($args) : "", count($args) ? array_shift($args) : "");
            } else {
              pocError::create(400, "Bad Request", "unknown class: '$mode'");
              return array();
            }
            break;
        }
      }
    }
    if ($selectProc) {
      pocEnv::call($selectProc, array($this->id, count($args) ? array_shift($args) : SELECT_MODE_DEFAULT,  count($args) ? array_shift($args) : "%",  count($args) ? array_shift($args) : "%"));
    }
    foreach($joines as $join) {
      $class = array_shift($join);
      $class::callJoin($join);
    }
    if ($showResults) {
      return pocEnv::call("pocPocCreatePocs");
    }
    return array();
  }






  private static $joinCounter = 0;

  public static function join($mode = "", $nameClause = "", $contentClause = "", $on = "credit") {
    $as = "ta" . self::$joinCounter++;
    pocEnv::call($selectProc, array($table, $className, $mode, $as, $on, $where);
  }




################################################################################

class pocSelect

$pocSelect      = new pocSelect($poc, $selectMode, $pocMode, $nameLike, $contentLike, $likeMode)

(pocSelectJoin) = $pocSelect->join($class, $join, $name, $where, $whereMode);
(void)          = $pocSelect->where($where, $whereMode);

(array(poc))    = $pocSelect->select();
(array(array))  = $pocSelect->select("colunm1", ...);


class pocSelectJoin

$pocSelectJoin  = new pocSelectJoin($class, $join, $name, $where, $whereMode)

(string)        = $pocSelect->__toString();

(void)          = $pocSelect->where($where, $whereMode);

################################################################################


$thePoc = poc::open(pocEnv::$request["poc"]);

foreach ($thePoc->select() as $p) {
  # ...
}

foreach ($thePoc->select("flat", POC_NAVI_FLAG) as $p) {
  # ...
}

foreach ($thePoc->select("stem", POC_NAVI_FLAG) as $n) {
  # ...
}

################################################################################


$exposes = poc::open("home/foccos/exposes");
$select = $exposes->select("flat", NAVI_MODE);
$select->join("pocAttributeChar", "", "seite", "verkaufen");
$kategorie = $select->join("pocAttributeChar", "", "kategorie");

foreach ($select->select("$kategorie", "COUNT($kategorie)", "GROUP BY $kategorie", "ORDER BY $kategorie") as $row) {
  echo "<li>$row[0] ($row[1])</li>" . PHP_EOL;
}

$kategorie->where("Eigentumswohnung");

foreach ($select->select() as $expose)
  $expose->kleinanzeige();


?>