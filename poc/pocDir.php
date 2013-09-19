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
  private $files = array();
  private $directories = array();

  public function __construct($path, $accept = array()) {
    $this->path = $path;
    if ($d = @dir($path)) {
      while (false !== ($entry = $d->read())) {
        if ($entry == "." || $entry == "..")
          continue;
        $f = $path ? "$path/$entry" : $entry;
        if (is_dir($f))
          $this->directories[$entry] = $f;
        elseif ($this->accept($entry, $accept))
          $this->files[$entry] = $f;
      }
      ksort($this->directories);
      ksort($this->files);
    } else {
      pocError::create(404, "File not found: $path", "new pocDir($path)");
      return NULL;
    }
  }
  
  public function __get($key) {
    return $this->$key;
  }

  private function accept($entry, $accept) {
    if (count($accept)) {
      foreach ($accept as $a)
        if (preg_match('/\\.$a\$/i', $entry))
          return TRUE;
    } else {
      return TRUE;
    }
    return FALSE;
  }

}

?>