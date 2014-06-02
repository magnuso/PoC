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
  public $mode;
  public $ignore = false;

  public function __construct($poc = ".", $mode = poc::NAVI_MODE) {
    if (is_string($poc)) {
      if (!$poc = poc::open($poc)) {
        pocError::create(404, "Not Found", "pocNavi->__construct can't open poc: '$poc'.");
        return NULL;
      }
    }
    $this->path = $poc->path;
    $this->mode = $mode;
  }

  public function getLinks() {
    $result = array();
    $u = new pocPath();
    foreach (new pocSelect($this->path, "flat", $this->mode) as $p) {
      $u->path = $p->path;
      $params = array("href" => $u->url);
      if ($u->here)
        $params["class"] = "here";
      $result[] = pocTag::create("a", $p->getTitle, $params);
    }
    return $result;
  }

  public function run($before = "", $after = "") {
    echo $before;
    foreach ($this->getLinks() as $a) {
      $a->run();
      echo $after;
    }
  }

  # IteratorAggregate

  public function getIterator() {
    return new ArrayIterator($this->getLinks());
  }

}

/******************************************************************************/

class pocNaviCrumb extends pocNavi {

  public function getLinks() {
    $result = array();
    $u = new pocPath();
    if (!$p = poc::open($this->path))
      return array();
    while ($p->id > 0) {
      $u->path = $p->path;
      if (!$this->mode || $p->mode & $this->mode)
        $result[] = pocTag::create("a", $p->getTitle, array("href" => $u->url));
      if (!$p = $p->parent)
        return array();
    }
    return array_reverse($result);
  }

}

/******************************************************************************/

class pocNaviStem extends pocNavi {

  public function getLinks() {
    $result = array();
    $u = new pocPath();
    foreach (new pocSelect($this->path, "stem", $this->mode) as $p) {
      $class = array(sprintf("node%02d", count(explode("/", $p->path))));
      $u->path = $p->path;
      if ($u->here)
        $class[] = "here";
      $result[] = pocTag::create("a", $p->getTitle, array("href" => $u->url, "class" => implode(" ", $class)));
    }
    return $result;
  }

}

/******************************************************************************/

class pocNaviTree extends pocNavi {

  public function getLinks() {
    $result = array();
    $u = new pocPath();
    foreach (new pocSelect($this->path, "tree", $this->mode) as $p) {
      $class = array(sprintf("node%02d", count(explode("/", $p->path))));
      $u->path = $p->path;
      if ($u->here)
        $class[] = "here";
      $result[] = pocTag::create("a", $p->getTitle, array("href" => $u->url, "class" => implode(" ", $class)));
    }
    return $result;
  }

}

?>