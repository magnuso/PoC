<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocImage {

  private $filename = "";
  private $realWidth = 0;
  private $realHeight = 0;
  private $imagetype = "";
  private $mimetype = "";
  private $resource = "";

  public $width = 0;
  public $height = 0;
  public $alt = "";
  public $quality = 80;

  public function __construct($filename, $width = 0, $height = 0) {
    $this->filename = $filename;
    if(!$info = @getimagesize($filename)) {
      pocError::create(400, "Bad Request", "pocImage: getimagesize($filename)");
      return NULL;
    }
    $this->realWidth = $info[0];
    $this->realHeight = $info[1];
    $this->imagetype = $info[2];
    $this->mime = $info["mime"];
    $this->fit($width, $height);
  }
  
  public function __destruct() {
    if ($this->resource)
      imagedestroy($this->resource);
  }
  
  public function __get($key) {
    return $this->$key;
  }

  public function isPortrait() {
    return $this->realHeight > $this->realWidth;
  }

  public function fit($width, $height) {
    $w = $this->realWidth;
    $h = $this->realHeight;
    if ($width && $w > $width) {
      $w = $width;
      $h = intval(round($h * $w / $this->realWidth));
    }
    if ($height && $h > $height) {
      $h = $height;
      $w = intval(round($this->realWidth * $h / $this->realHeight));
    }
    $this->width = $w;
    $this->height = $h;
  }

  public function resize() {
    if (!$this->resource)
      return FALSE;
    if (!$dest = imagecreatetruecolor($this->width, $this->height)) {
      pocError::create(400, "Bad Request", "pocImage: imagecreatetruecolor($this->width, $this->height)");
      return FALSE;
    }
    if (imagecopyresampled($dest, $this->resource, 0, 0, 0, 0, $this->width, $this->height, $this->realWidth, $this->realHeight)) {
      $this->resource = $dest;
      $this->realWidth = $this->width;
      $this->realHeight = $this->height;
    } else {
      pocError::create(400, "Bad Request", "pocImage: imagecopyresampled(dest, src, 0, 0, 0, 0, $this->width, $this->height, $this->realWidth, $this->realHeight)");
      return FALSE;
    }
    return TRUE;
  }

  public function watermark($filename) {
    if (!$watermark = new pocImage($filename))
      return FALSE;
    $watermark->load();
    if (!imagecopyresampled($this->resource, $watermark->resource, 0, 0, 0, 0, $this->realWidth, $this->realHeight, $watermark->realWidth, $watermark->realHeight)) {
      pocError::create(400, "Bad Request", "pocImage: imagecopyresampled(dest, watermark, 0, 0, 0, 0, $this->realWidth, $this->realHeight, $watermark->realWidth, $watermark->realHeight)");
      return FALSE;
    }
    return TRUE;
  }

  public function load() {
    switch ($this->imagetype) {
      case IMAGETYPE_JPEG:
        if (!$this->resource = imagecreatefromjpeg($this->filename))
          pocError::create(400, "Bad Request", "pocImage: imagecreatefromjpeg($filename)");
        break;
      case IMAGETYPE_PNG:
        if (!$this->resource = imagecreatefrompng($this->filename))
          pocError::create(400, "Bad Request", "pocImage: imagecreatefrompng($filename)");
        break;
      case IMAGETYPE_GIF:
        if (!$this->resource = imagecreatefromgif($this->filename))
          pocError::create(400, "Bad Request", "pocImage: imagecreatefromgif($filename)");
        break;
      default:
        return NULL;
    }
    return $this->resource != "";
  }

  public function save($filename = "") {
    $filename = $filename ? $filename : $this->filename;
    switch ($this->imagetype) {
      case IMAGETYPE_JPEG:
        if (!imagejpeg($this->resource, $filename, $this->quality)) {
          pocError::create(400, "Bad Request", "pocImage: imagejpeg(resource, $filename, $this->quality)");
          return FALSE;
        }
        break;
      case IMAGETYPE_PNG:
        if (!imagepng($this->resource, $filename)) {
          pocError::create(400, "Bad Request", "pocImage: imagepng(resource, $filename)");
          return FALSE;
        }
        break;
      case IMAGETYPE_GIF:
        if (!imagegif($this->resource, $filename)) {
          pocError::create(400, "Bad Request", "pocImage: imagegif(resource, $filename)");
          return FALSE;
        }
        break;
      default:
        return NULL;
    }
    return TRUE;
  }

  public function getTag() {
    return pocTag::create("img", NULL, array("src" => $this->filename, "alt" => $this->alt, "width" => $this->width, "height" => $this->height));
  }

  public function run() {
    $tag = $this->getTag();
    $tag->run();
  }

}

?>