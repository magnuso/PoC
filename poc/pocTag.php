<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocTag extends pocArray implements ArrayAccess {

  public $tag;
  public $params;
  public $isHtml;

  protected $intro;
  protected $outro;

  public function __construct($row) {
    parent::__construct($row);
  }

  public function run() {
    echo $this->__toString();
  }

  # magic

  public function __toString() {
    preg_match('/^(\s*)(\S+)(\s*)$/', $this->tag, $tag);
    $out = "$tag[1]<$tag[2]";
    foreach ($this->params as $k => $v)
      $out .= " $k=\"$v\"";
    if ($this->content === NULL)
      return $out . " />$tag[3]";
    $out .= ">";
    foreach($this as $content)
      $out .= is_a($content, __CLASS__) ? $content->__toString() : ($this->isHtml ? $content : pocEnv::isHtml($content));
    return "$out$tag[3]</$tag[2]>";
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

  public static function createFromString($input) {
    $input = explode("<", $input);
    $rest = array_shift($input);
    $stack = array();
    $current = NULL;
    foreach ($input as $line) {
      preg_match('/^(\/?)(\w+)\s*([^>]*)>([^>]*)$/m', $line, $line);
      preg_match('/(\s+)$/m', $line[4], $tail);
      $tail = $tail[1];
      $text = rtrim($line[4]);
      if ($line[1]) {
        $current = array_pop($stack);
        if ($text)
          $current->push($text);
        $current->tag .= $rest;
      } else {
        $tag = new self("$rest$line[2]");
        preg_match_all('/(\w+)="([^"]*)"/m', $line[3], $params, PREG_SET_ORDER);
        foreach ($params as $par)
          $tag[$par[1]] = $par[2];
        if ($current)
          $current->push($tag);
        else
          $current = $tag;
        if (preg_match('/\/$/m', $line[3])) {
          if ($text)
            $current->push($text);
          $tag->content = NULL;
        } else {
          $stack[] = $current;
          $current = $tag;
        }
      }
      $rest = $tail;
    }
    return $current;
  }

  protected static function getCreateParams() {
    return array("tag" => "div", "content" => NULL, "params" => array(), "isHtml" => TRUE);
  }

}

?>