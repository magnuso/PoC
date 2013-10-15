<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocPath implements ArrayAccess, IteratorAggregate {

  public static $finishUrlFunction = NULL; # !!! pocPath::$finishUrlFunction = function($url, $path, $params, $ancor, $target) { return $url; };

  private $myPath = "";
  private $myName = "";
  private $myParent = "";

  public $params = array(); # ArrayAccess
  public $ancor= "";
  public $target= "";

  public function __construct($path = "", $params = array(), $ancor = "", $target = "") {
    $this->path = $path;
    $this->params = $params;
    $this->ancor = $ancor;
    $this->target = $target;
  }

  # magic
  public function __get($key) {
    switch ($key) {
      case "a":
        if ($poc = poc::open($this->myPath))
          return pocTag::create("a", $poc->getTitle, array("href" => $this->url), TRUE);
        return "";
      case "path":
        return $this->myPath;
      case "name":
        return $this->myName;
      case "parent":
        return $this->myParent;
      case "url":
        return self::urlCulator($this->myPath, $this->params, $this->ancor, $this->target);
      case "here":
        return strpos("/" . pocEnv::$env["PATH_INFO"] . "/", "/$this->myPath/") == 1;
    }
  }

  public function __set($key, $value) {
    switch ($key) {
      case "path":
        $this->myPath = self::pathCulator($value);
        $parent = explode("/", $this->myPath);
        $this->myName = array_pop($parent);
        $this->myParent = implode("/", $parent);
        break;
      case "append":
        if ($value = self::trim($value))
          $this->path = self::pathCulator($this->myPath ? "$this->myPath/$value" : $value);
        break;
    }
  }

  public function __toString() {
    return $this->myPath;
  }

  # ArrayAccess
  public function offsetSet($offset, $value) {
    if (!is_null($offset))
      $this->params[$offset] = $value;
  }

  public function offsetExists($offset) {
    return isset($this->params[$offset]);
  }

  public function offsetUnset($offset) {
    unset($this->params[$offset]);
  }

  public function offsetGet($offset) {
    return $this->params[$offset];
  }

  # IteratorAggregate
  public function getIterator() {
    $out = array();
    $path = "";
    $slash = "";
    foreach (explode("/", $this->myPath) as $name) {
      $path .= "$slash$name";
      $out[$path] = new pocPath($path);
      $slash = "/";
    }
    return new ArrayIterator($out);
  }

  # trim
  public static function trim($path) {
    return preg_replace('/^\s*\/*/', "", preg_replace('/\/*\s*$/', "", $path));;
  }

  # path
  public static function pathCulator($path) {
    $path = self::trim($path);
    if (!$path)
      return "";
    $path = explode("/", $path);
    $outPath = array();
    $dotdot = TRUE;
    foreach ($path as $v) {
      switch ($v) {
        case ".":
          $outPath = explode("/", pocEnv::$env["PATH_INFO"]);
          break;
        case "~":
          $outPath = explode("/", pocEnv::$env["pocHome"]);
          break;
        case "..":
          if ($dotdot)
            $outPath = explode("/", pocEnv::$env["PATH_INFO"]);
          array_pop($outPath);
          break;
        default:
          $outPath[] = $v;
          break;
      }
      $dotdot = false;
    }
    return self::trim(implode("/", $outPath));
  }

  # url
  public static function urlCulator($path, $params = array(), $ancor = "", $target = "") {
    $url = pocEnv::$urlBase . ($path ? "/$path" : "")
      . (count($params) ? "?" . http_build_query($params) : "")
      . ($ancor ? "#$ancor" : "") . ($target ? "\" target=\"$target" : "");
    if (self::$finishUrlFunction)
      return self::$finishUrl($url, $path, $params, $ancor, $target);
    return $url;
  }

}

?>