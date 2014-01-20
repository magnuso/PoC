<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

abstract class pocAttribute extends pocRecord implements IteratorAggregate {

  # dependencies:
  #   uses pocEnv, poc, pocError

  public $value = 0.0;

  protected $debitId = 0;
  protected $creditId = 0;
  protected $voucherId = 0;
  protected $deleted = FALSE;

  public function __construct($row = array()) {
    $this->cacheMe = TRUE;
    $this->returnRow = FALSE;
    parent::__construct($row);
    if ($poc = $this->credit)
      $poc->attributeAttach($this);
  }

  public function __get($key) {
    switch ($key) {
      case "credit":
        return poc::open($this->creditId);
      case "debit":
        return poc::open($this->debitId);
      case "voucher":
        return poc::open($this->voucherId);
      case "get":
        return self::format($this->content);
      case "runPriv":
      case "openPriv":
      case "selectPriv":
      case "insertPriv":
      case "updatePriv":
      case "deletePriv":
      case "userPrivs":
      case "groupPrivs":
      case "otherPrivs":
        return $this->credit->$key;
      default:
        return parent::__get($key);
    }
  }

  public function __set($key, $value) {
    switch ($key) {
      case "set":
        $this->content = self::parse($value);
        break;
      case "debit":
        if (is_a($value, "poc"))
          $this->debitId = $value->id;
        elseif ($poc = poc::open($value))
          $this->debitId = $poc->id;
        else
          $this->debitId = 0;
        break;
      case "voucher":
        if (is_a($value, "poc"))
          $this->voucherId = $value->id;
        elseif ($poc = poc::open($value))
          $this->voucherId = $poc->id;
        else
          $this->voucherId = 0;
        break;
      default:
        parent::__set($key, $value);
        break;
    }
  }

  public function __toString() {
    if ($this->openPriv)
      return self::format($this->content);
  }

  public function getInput() {
    return pocTag::create("input", NULL, array("value" => self::format($this->content), "name" => $this->identifier));
  }

  # db
  public function insert($poc) {
    if (is_a($poc, "poc"))
      $this->creditId = $poc->id;
    elseif ($poc = poc::open($poc))
      $this->creditId = $poc->id;
    else
      return;
    $this->value = floatval($this->value);
    return parent::insert($this->name);
  }

  public function delete() {
    if ($ok = parent::delete() && $poc = $this->credit)
      $poc->attributeDetach($this);
    return $ok;
  }

  # IteratorAggregate. Fake
  public function getIterator() {
    return new ArrayIterator(array($this));
  }

  # static
  protected static function getInsertParams() { return array("className", "debitId", "creditId", "voucherId", "name", "title", "content", "value"); }
  protected static function getUpdateParams() { return array("id", "debitId", "voucherId", "name", "title", "content", "value"); }
  protected static function getCreateParams() { return array("name" => "", "title" => "", "content" => "", "value" => 0.0); }

  # "abstract" static
  public static function getTableName() { return ""; }

  # i/o
  public static function format($content, $format = NULL) { return $content; }
  public static function parse($content, $format = NULL) { return $content; }

}

/******************************************************************************/

class pocAttributeChar extends pocAttribute {

  public static function getTableName() { return "pocAttributeChar"; }

  protected static function getOpenProc() { return "pocAttributeCharOpen"; }
  protected static function getInsertProc() { return "pocAttributeCharInsert"; }
  protected static function getUpdateProc() { return "pocAttributeCharUpdate"; }
  protected static function getDeleteProc() { return "pocAttributeCharDelete"; }

}

/******************************************************************************/

class pocAttributeDouble extends pocAttribute {

  public static function getTableName() { return "pocAttributeDouble"; }

  protected static function getOpenProc() { return "pocAttributeDoubleOpen"; }
  protected static function getInsertProc() { return "pocAttributeDoubleInsert"; }
  protected static function getUpdateProc() { return "pocAttributeDoubleUpdate"; }
  protected static function getDeleteProc() { return "pocAttributeDoubleDelete"; }

  public static function parse($content, $format = NULL) { return floatval($content); }
  public static function format($content, $format = NULL) { return sprintf("%G", $content); }

}

/******************************************************************************/

class pocAttributeInt extends pocAttribute {

  public static function getTableName() { return "pocAttributeInt"; }

  protected static function getOpenProc() { return "pocAttributeIntOpen"; }
  protected static function getInsertProc() { return "pocAttributeIntInsert"; }
  protected static function getUpdateProc() { return "pocAttributeIntUpdate"; }
  protected static function getDeleteProc() { return "pocAttributeIntDelete"; }

  public static function parse($content, $format = NULL) { return intval($content); }

}

/******************************************************************************/

class pocAttributeText extends pocAttribute {

  public function getInput() {
    return pocTag::create("textarea", self::format($this->content), array("name" => $this->identifier), FALSE);
  }

  public static function getTableName() { return "pocAttributeText"; }

  protected static function getOpenProc() { return "pocAttributeTextOpen"; }
  protected static function getInsertProc() { return "pocAttributeTextInsert"; }
  protected static function getUpdateProc() { return "pocAttributeTextUpdate"; }
  protected static function getDeleteProc() { return "pocAttributeTextDelete"; }

}

/******************************************************************************/

class pocAttributeTag extends pocAttributeChar {

}

class pocAttributeStatement extends pocAttributeDouble {

}

class pocAttributePosition extends pocAttributeDouble {

}

class pocAttributeSearch extends pocAttributeText {

}

?>