<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocDir {

  private $path = "";
  private $accept;
  private $files = array();
  private $directories = array();

  public function __construct($path, $accept = array()) {
    $this->path = $path;
    $this->accept = $accept;
    if ($d = @dir($path)) {
      while (false !== ($entry = $d->read())) {
        if ($entry == "." || $entry == "..")
          continue;
        $f = $path ? "$path/$entry" : $entry;
        if (is_dir($f))
          $this->directories[$entry] = $f;
        elseif ($this->accept($entry))
          $this->files[$entry] = $f;
      }
      natcasesort($this->directories);
      natcasesort($this->files);
    } else {
      pocError::create(404, "File not found: $path", "new pocDir($path)");
      return NULL;
    }
  }
  
  public function __get($key) {
    return $this->$key;
  }

  public function delete() {
    if ($d = @dir($this->path)) {
      while (false !== ($entry = $d->read())) {
        if ($entry == "." || $entry == "..")
          continue;
        $f = $path ? "$path/$entry" : $entry;
        if (is_dir($f)) {
          $f = new pocDir($f);
          $f->delete();
        } else {
          @unlink($f);
        }
      }
      @rmdir($this->path);
    }
  }

  private function accept($entry) {
    if (count($this->accept)) {
      $entry = array_pop(explode(".", $entry));
      return in_array($entry, $this->accept);
    } else {
      return TRUE;
    }
  }

}

?>