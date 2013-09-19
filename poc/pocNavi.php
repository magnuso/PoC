<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocNavi implements IteratorAggregate {

  public $path;
  public $ignore = false;
  public $getTitle = true;

  public function __construct($path = "") {
    $this->path = $path;
  }

  public function getLinks() {
    $array = array();
    $poc = poc::open($this->path);
    if ($poc->errCode || !$poc->selectPriv)
      return $array;
    $u = new pocPath();
    foreach($poc->select() as $row) {
      if ($row["openPriv"] && ($this->ignore || ($row["mode"] & poc::NAVI_MODE))) {
        $u->path = $poc->path . "/" . $row["name"];
        $url = $u->url; # !
        $a = new pocTag("a", $this->getTitle? $u->title: $u->name, array("href" => $url));
        if ($u->here)
          $a["class"] = "here";
        $array[] = $a;
      }
    }
    return $array;
  }

  # IteratorAggregate

  public function getIterator() {
    return new ArrayIterator($this->getLinks());
  }

}

/******************************************************************************/

class pocBreadcrumbs extends pocNavi {

  public function getLinks() {
    $path = new pocPath($this->path);
    if (!$path->path)
      return array();
    $array = array();
    foreach ($path as $u) {
      if (!$poc = poc::open($u->path))
        return array();
      if ($this->ignore || $poc->navigatePriv) {
        $url = $u->url; # !
        $a = new pocTag("a", $poc->getTitle, array("href" => $url));
        if ($u->here)
          $a["class"] = "here";
        $array[] = $a;
      }
    }
    return $array;
  }

}

/******************************************************************************/

class pocStemNavi extends pocNavi {

  private $aggregate;

  private $styleLeft;
  private $styleNumber;
  private $styleRight;

  public function __construct($path = "", $style = "position:relative;left:1em;") {
    parent::__construct($path);
    if ($style) {
      $matches = array();
      preg_match('/(^\D*)([\d\.]*)(\D*$)/', $style, $matches);
      $this->styleLeft = $matches[1];
      $this->styleNumber = $matches[2] + 0.0;
      $this->styleRight = $matches[3];
    } 
  }

  public function getLinks() {
    $this->aggregate = array();
    $this->collect(explode("/", $this->path), array(), 0);
    return $this->aggregate;
  }

  private function collect($from, $to, $i) {
    $poc = poc::open(implode("/", $to));
    if ($poc->errCode || !$poc->selectPriv)
      return false;
    $u = new pocPath();
    foreach($poc->select() as $row) {
      if ($row["openPriv"]) {
        $u->path = $poc->path . "/" . $row["name"];
        $url = $u->url; # !
        if ($this->ignore || $u->navigatePriv) {
          $a = new pocTagA($this->getTitle? $u->title: $u->name, $url);
          if ($u->here)
            $a["class"] = "here";
          if (!$u->navigatePriv)
            $a["class"] = $a["class"]? $a["class"] . " noNavi": "noNavi";
          if ($i && $this->styleNumber > 0.0)
            $a["style"] = $this->styleLeft . ($this->styleNumber * $i) . $this->styleRight;
          $this->aggregate[] = $a;
        }
        if ($u->here && count($from)) {
          $to[] = array_shift($from);
          $this->collect($from, $to, $i + (int)($this->ignore || $row["navigatePriv"]));
        }
      }
    }
  } 

}

?>