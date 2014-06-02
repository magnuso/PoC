<?PHP

/*******************************************************************************

Copyright (c) 2013, Marcus Grundschok
All rights reserved.

lizenziert unter EUPL V. 1.1

marcus@grundschok.de

*******************************************************************************/

ini_set('variables_order', 'EGPCS');

set_include_path('..');

session_start();

if (get_magic_quotes_gpc())
  array_walk_recursive($_REQUEST, function(&$v) { $v = stripslashes($v); });

$button = 'Weiter';

$dbhOk = FALSE;
$includeOk = FALSE;
$configOk = FALSE;
$tablesOk = FALSE;
$userOk = FALSE;
$loginOk = FALSE;
$coreOk = FALSE;
$pocOk = FALSE;
$done = FALSE;

$screen = $_REQUEST['screen'] ? $_REQUEST['screen'] : 'pakets';

if ($_REQUEST['dbHost']) {
  $_SESSION['dbHost'] = $_REQUEST['dbHost'];
  $_SESSION['dbDb'] = $_REQUEST['dbDb'];
  $_SESSION['dbPort'] = $_REQUEST['dbPort'];
  $_SESSION['dbUser'] = $_REQUEST['dbUser'];
  $_SESSION['dbPassword'] = $_REQUEST['dbPassword'];
  unset($_SESSION['adminpw']);
  unset($_SESSION['username']);
  unset($_SESSION['userpw']);
}

if ($_REQUEST['logout']) {
  session_unset();
}

################################################################################

# check include-files
set_error_handler(function($code, $text, $file, $line) { global $includeOk; new error("Include fehlgeschlagen:\n$text\nin: '$file', Zeile: $line"); $includeOk = FALSE; });
include "pocInclude.php";
$includeOk = TRUE;
restore_error_handler();

# connect to db
if ($_SESSION['dbDb']) {
  try {
    new dbHandle ($_SESSION['dbDb'], $_SESSION['dbHost'], $_SESSION['dbPort'], $_SESSION['dbUser'], $_SESSION['dbPassword'], pocEnv::$session['dbOptions']);
    $dbhOk = TRUE;
  } catch (Exception $e) {
    new error("Datenbankverbindung fehlgeschlagen.\n$e");
  }
}

# check config
if ($dbhOk && $includeOk && $_SESSION['dbDb'] == pocEnv::$session['dbName'] && $_SESSION['dbHost'] == pocEnv::$session['dbHost'] && $_SESSION['dbPort'] == pocEnv::$session['dbPort']
    && $_SESSION['dbUser'] == pocEnv::$session['dbUser'] && $_SESSION['dbPassword'] == pocEnv::$session['dbPassword'])
  $configOk = TRUE;

# read pakets
if ($configOk) {
  pocPackage::scanDir();
  if (isset(pocPackage::$allObjects['pocUser']))
    $userOk = pocPackage::$allObjects['pocUser']->installed;
  else
    new error("Paket 'pocCore' beschädigt.");
}

# the great install all
if ($dbhOk && $includeOk && $configOk && !$userOk && $_REQUEST['installPoc']) {
  $mistakes = 0;
  if ($_REQUEST['adminpw']) {
    if ($_REQUEST['adminpw'] != $_REQUEST['adminpw2']) {
      new error('Administrator-Passwort falsch wiederholt.');
      $_SESSION['adminpw'] = '';
      $mistakes++;
    } else {
      $_SESSION['adminpw'] = $_REQUEST['adminpw'];
    }
  } else {
      new error('Administrator-Passwort fehlt.');
      $_SESSION['adminpw'] = '';
      $mistakes++;
  }
  if ($_REQUEST['username']) {
    $_SESSION['username'] = $_REQUEST['username'];
    if ($_REQUEST['userpw']) {
      if ($_REQUEST['userpw'] != $_REQUEST['userpw2']) {
        new error('Benutzer-Passwort falsch wiederholt.');
        $_SESSION['userpw'] = '';
        $mistakes++;
      } else {
        $_SESSION['userpw'] = $_REQUEST['userpw'];
      }
    } else {
        new error('Benutzer-Passwort fehlt.');
        $_SESSION['userpw'] = '';
        $mistakes++;
    }
  } else {
      new error('Benutzername fehlt.');
      $_SESSION['username'] = '';
      $mistakes++;
  }
echo "Mistakes: $mistakes.";
  if ($mistakes == 0) {
    foreach (pocPackage::$packages as $p)
      $p->install();
    pocPackage::postInstall();
    if (isset(pocPackage::$allObjects['pocUser']))
      $userOk = pocPackage::$allObjects['pocUser']->installed;
    else
      new error("Paket 'pocCore' beschädigt.");
    if ($userOk) {
      $a = dbHandle::slash($_SESSION['adminpw']);
      dbHandle::run("UPDATE pocUser SET pw = SHA1($a) WHERE id = 1;");
      $u = dbHandle::slash($_SESSION['username']);
      $pw = dbHandle::slash($_SESSION['userpw']);
      dbHandle::run("UPDATE pocUser SET name = $u, pw = SHA1($pw) WHERE id = 2;");
      if ($loginOk = count(dbHandle::select("SELECT id FROM pocUser WHERE name = 'admin' AND pw = SHA1($a);")) == 1)
        $_SESSION['password'] = $_SESSION['adminpw'];
      else
        new error("Anlegen des Administrators fehlgeschlagen. Paket 'pocCore' beschädigt.");
    } else {
      new error("Anlegen der Benutzertabelle fehlgeschlagen. Paket 'pocCore' beschädigt.");
    }
  }
}

# hard login
if ($userOk && $_REQUEST['login']) {
  $u = dbHandle::slash($_REQUEST['login']);
  $pw = dbHandle::slash($_REQUEST['password']);
  if ($loginOk = count(dbHandle::select("SELECT id FROM pocUser WHERE name = $u AND pw = SHA1($pw);")) == 1) {
    $_SESSION['password'] = $_REQUEST['password'];
  } else {
    $_SESSION['password'] = '';
    new error("Login fehlgeschlagen.");
  }
}

# soft login
if ($userOk && $_SESSION['password'] && !$_REQUEST['installPoc']) {
  $pw = dbHandle::slash($_SESSION['password']);
  $loginOk = count(dbHandle::select("SELECT id FROM pocUser WHERE name = 'admin' AND pw = SHA1($pw);")) == 1;
  if (!$loginOk) {
    $_SESSION['password'] = '';
    new error("Login fehlgeschlagen.");
  }
}

# some action
if ($loginOk) {
  if ($_REQUEST['dropObject'])
    pocPackage::$allObjects[$_REQUEST['dropObject']]->drop();
  if ($_REQUEST['installObject'])
    pocPackage::$allObjects[$_REQUEST['installObject']]->install();
  if ($_REQUEST['dropPackage'])
    pocPackage::$packages[$_REQUEST['dropPackage']]->drop();
  if ($_REQUEST['installPackage'])
    pocPackage::$packages[$_REQUEST['installPackage']]->install();
  pocPackage::postInstall();
  $userOk = $loginOk = pocPackage::$allObjects['pocUser']->installed;
}

# check done
if ($loginOk)
  $done = pocPackage::$packages['pocCore']->installed();

################################################################################
################################################################################
################################################################################

new screen('dsn', '1. Datenbank', $dbhOk, function() {
?>
  <h1>1. Datenbank</h1>
  <p>Geben Sie hier die Zugangsdaten zur MySQL-Datenbank ein.</p>
  <ul class="inputs">
    <li><label for="dbHost">Host:</label><input type="text" name="dbHost" value="<?PHP echo $_SESSION['dbHost']; ?>" /></li>
    <li><label for="dbDb">Datenbank:</label><input type="text" name="dbDb" value="<?PHP echo $_SESSION['dbDb']; ?>" /></li>
    <li><label for="dbPort">Port:</label><input type="text" name="dbPort" value="<?PHP echo $_SESSION['dbPort']; ?>" /></li>
    <li><label for="dbUser">Benutzername:</label><input type="text" name="dbUser" value="<?PHP echo $_SESSION['dbUser']; ?>" /></li>
    <li><label for="dbPassword">Passwort:</label><input type="password" name="dbPassword" value="<?PHP echo $_SESSION['dbPassword']; ?>" /></li>
  </ul>
<?PHP
});

################################################################################

new screen('config', '2. Konfiguration', $configOk, function() {
?>
  <h1>2. Konfiguration</h1>
  <p>Speichern Sie folgenden Text in die Datei: 'include/pocConfig.php'.</p>
  <textarea name="config" wrap="off" readonly="readonly">&lt;?PHP

/*******************************************************************************

Copyright (c) 2013, Marcus Grundschok
All rights reserved. Lizenziert unter EUPL V. 1.1

marcus@grundschok.de
http://www.poc-online.net/

*******************************************************************************/

if (!defined('_POC_SESSION_ID_')) {

  pocEnv::$session = $_SERVER; # $_ENV should do too
  pocEnv::$request = $_REQUEST;

  pocEnv::$session["dbName"] = "da";
  pocEnv::$session["dbHost"] = "localhost";
  pocEnv::$session["dbPort"] = "";
  pocEnv::$session['pocDbUser'] = "<?PHP echo $_SESSION['dbUser']; ?>";
  pocEnv::$session['pocDbPassword'] = "<?PHP echo $_SESSION['dbPassword']; ?>";
  pocEnv::$session['pocDbOptions'] = array(); # NO NO NEVER EVER USE A PERSISTANT CONNECTION!!!!!!!!

  pocEnv::$session['pocSessionLifetime'] = 1200;
  pocEnv::$session['pocSessionCookieLifetime'] = 86400;

  # rather change these as attributes of etc/init
  pocEnv::$session['pocErrorPage'] = 'www/error';

  pocEnv::$session['pocDateTimeFormat'] = 'd.m.Y  H:i';
  pocEnv::$session['pocDateFormat'] = 'd.m.Y';
  pocEnv::$session['pocTimeFormat'] = 'H:i:s';

}

?&gt;</textarea>
<?PHP
});

################################################################################

new screen('passwords', '3. Passwörter', $userOk, function() {
  global $userOk;
  if ($userOk) {
?>
  <h1>3. Passwörter</h1>
  <p>Geben Sie das Administrator-Passwort ein.</p>
  <ul class="inputs">
    <li><label for="password">Passwort:</label><input type="password" name="password" value="<?PHP echo $_SESSION['password']; ?>" /></li>
  </ul>
  <input type="hidden" name="login" value="admin" />
<?PHP
  } else {
?>
  <h1>3. Passwörter</h1>
  <p>Legen Sie ein Administrator-Passwort fest und einen Benutzer an.</p>
  <ol>
    <li>Administrator-Passwort:
      <ul class="inputs">
        <li><label for="adminpw">Admin-Passwort:</label><input type="password" name="adminpw" value="<?PHP echo $_SESSION['adminpw']; ?>" /></li>
        <li><label for="adminpw2">Passwort wiederholen:</label><input type="password" name="adminpw2" value="<?PHP echo $_SESSION['adminpw']; ?>" /></li>
      </ul>
    </li>
    <li>Benutzer:
      <ul class="inputs">
        <li><label for="username">Benutzername:</label><input type="text" name="username" value="<?PHP echo $_SESSION['username']; ?>" /></li>
        <li><label for="userpw">Passwort:</label><input type="password" name="userpw" value="<?PHP echo $_SESSION['userpw']; ?>" /></li>
        <li><label for="userpw2">Passwort wiederholen:</label><input type="password" name="userpw2" value="<?PHP echo $_SESSION['userpw']; ?>" /></li>
      </ul>
    </li>
  </ol>
  <input type="hidden" name="installPoc" value="yes" />
<?PHP
  }
});

################################################################################

new screen('pakets', '4. Pakete', $loginOk, function() {
  global $button, $done; 
  $button = $done? 'Fertig!': '';
?>
  <h1>4. Pakete</h1>
  <p>Installieren/Deinstallieren Sie Pakete sowie einzelne Objekte.</p>
  <ol class="package">
<?PHP    
    foreach (pocPackage::$packages as $p) {
?>
    <li>Paket: <?PHP 
      echo $p->name;
      echo $p->echoLink();
      if (count($p->dbObjects)) {
?>
      <ul class="object">
<?PHP

        foreach ($p->dbObjects as $o) {
?>
        <li<?PHP if ($o->installed) echo ' class="installed"'; ?>><?PHP echo $o->name; $o->echoLink(); ?></li>
<?PHP
        }

?>
      </ul>
<?PHP
      }
?></li>
<?PHP
    }
?>
  </ol>
  <input type="hidden" name="screen" value="done" />
<?PHP
});

################################################################################

new screen('done', '5. Fertig!', $done, function() {
?>
  <h1>5. Fertig!</h1>
  <p>PoC ist nun fertig installiert und einsatzbereit.</p>
  <h3 align="center"><a href="../poc.php?logout=ok">Start...</a></h3>
<?PHP
});

################################################################################
################################################################################
################################################################################

if ($screen == 'done' && !$done)
  $screen = 'pakets';

if ($screen == 'pakets' && !$loginOk)
  $screen = 'passwords';

if ($screen == 'passwords' && !$includeOk)
  $screen = 'config';

if ($screen == 'config' && !$dbhOk)
  $screen = 'dsn';

if (!$dbhOk || !isset(screen::$screens[$screen]))
  $screen = 'dsn';

header("Content-Type: text/html; charset=utf-8");

?><!DOCTYPE HTML>
<html>
<head>
<title>pocInstall - <?PHP screen::echoTitle($screen); ?></title>
<style type="text/css">

body { width: 40em; padding: 5em 0em 0em 0em; margin: auto; }

form { position: relative; width: 38em; min-height: 28em; padding: 1em; border: 1px solid black; }

ol { list-style-type: decimal; }
.package li { width: 20em; padding: 0.1em; clear: right; }
.package a:link { float: right; }

.logout { float: right; }
.error { background-color: #f99; padding: 0.5em; margin: 0.5em 0em 0.5em 0em; }

.object li { list-style: none outside; width: 20em; padding: 0.1em; clear: right; }
.object li.installed { list-style: disc outside; }
.object a:link { float: right; }

.inputs li { list-style: none outside; width: 20em; padding: 0.1em; clear: right; }
.inputs input { float: right; }

#topp { padding: 0em; margin: 0em; text-align: right; }
#navi { padding: 0em; margin: 0em; text-align: center; color: #999; font-size: 75%; }
#navi a:link { color: black; text-decoration: none; }
#navi a:visited { color: black; text-decoration: none; }
#navi a:hover { color: black; text-decoration: underline; }
#navi .here { font-weight: bold; }
#submitButton { position: absolute; bottom: 1em; right: 1em; }

textarea { width: 100%; height: 20em; }

</style>
</head>
<body>
<form>
  <p id="topp">&nbsp;<?PHP

if ($dbhOk) {
?><a href="?logout=yes">logout</a><?PHP
}

?></p>
  <p id="navi"><?PHP

$z = '';
foreach (screen::$screens as $k => $v) {
  echo $z;
  $v->echoLink($k == $screen ? 'here' : '');
  $z = ' &gt; ';
}

?></p>
<?PHP

screen::show($screen);

foreach (error::$errors as $e) {
?>
  <h3 class="error"><?PHP echo nl2br($e->message); ?></h3>
<?PHP
}

if ($button && $screen != 'done') {
?>
  <input id="submitButton" type="submit" name="submit" value="<?PHP echo $button; ?>" />
<?PHP
}

?>
</form>
</body>
</html>
<?PHP

################################################################################
################################################################################
################################################################################

class error {

  public static $errors = array();

  public $message;

  public function __construct($message) {
    $this->message = $message;
    self::$errors[] = $this;
  }

}

################################################################################

class dbHandle {

  private static $dbh;

  public function __construct($db, $host, $port, $user, $password, $options) {
    if (isset(self::$dbh))
      return null;
    $class = pocEnv::$session["pocPDOClass"];
    self::$dbh = new $class($class::dsn($db, $host, $port), $user, $password, $options);
  }

  public static function getDbh() {
    return self::$dbh;
  }

  public static function select($sql) {
    try {
      $q = self::$dbh->query($sql);
      return $q->fetchAll();
    } catch (Exception $e) {
      new error("$e");
      return array();
    }
  }

  public static function run($sql) {
    try {
      return self::$dbh->query($sql);
    } catch (Exception $e) {
      new error("$e\n in: $sql");
    }
  }

  public static function slash($txt) {
    return self::$dbh->quote($txt);
  }

}

################################################################################

class screen {

  public static $screens = array();

  public $name;
  public $title;
  public $active;
  public $bodyFunction;

  public function __construct($name, $title, $active, $bodyFunction) {
    $this->name = $name;
    $this->title = $title;
    $this->active = $active;
    $this->bodyFunction = $bodyFunction;
    self::$screens[$name] = $this;
  }

  public static function show($name) {
    return self::$screens[$name]->run();
  }

  public static function echoTitle($name) {
    echo self::$screens[$name]->title;
  }

  public function run() {
    $f = $this->bodyFunction;
    dumpinst();
    return $f();
  }

  public function echoLink($class = '') {
    if ($this->active) {
      if ($class == '') {
?><a href="?screen=<?PHP echo $this->name; ?>"><?PHP echo $this->title; ?></a><?PHP
      } else {
?><a href="?screen=<?PHP echo $this->name; ?>" class="<?PHP echo $class; ?>"><?PHP echo $this->title; ?></a><?PHP
      }
    } else {
      if ($class == '') {
        echo $this->title;
      } else {
?><span class="<?PHP echo $class; ?>"><?PHP echo $this->title; ?></span><?PHP
      }
    }
  }

}

################################################################################

class pocPackage {

  public static $packages = array();
  public static $allObjects = array();
  public static $postObjects = array();

  public $name;
  public $dbObjects;
  
  public static function scanDir($path = '.', $sort = TRUE) {
    $d = dir($path);
    while (FALSE !== ($entry = $d->read())) {
      if ($entry == '.' || $entry == '..')
        continue;
      $entry = "$path/$entry";
      if (is_dir($entry)) {
        self::scanDir($entry, FALSE);
      } else {
        if ($o = pocTable::createFromFilename($entry))
          self::addObject($o);
      }
    }
    if ($sort)
      uksort(self::$packages, function($a, $b) {
        if ($a == $b)
          return 0;
        if ($a == 'pocCore');
          return 1;
        if ($b == 'pocCore');
          return -1;
        return strcmp($a, $b);
      });
      foreach (self::$packages as $p)
        $p->sort();
    return self::$packages;
  }

  public static function postInstall() {
    foreach(self::$postObjects as $o)
      $o->install();
    self::$postObjects = array();
  }

  public function echoLink() {
    if ($this->installed()) {
?><a href="?screen=pakets&dropPackage=<?PHP echo $this->name; ?>">Drop</a><?PHP
    } else {
?><a href="?screen=pakets&installPackage=<?PHP echo $this->name; ?>">Install</a><?PHP
    }
  }

  private static function addObject($o) {
    $p = isset(self::$packages[$o->package]) ? self::$packages[$o->package] : new pocPackage($o->package);
    $p->dbObjects[$o->name] = $o;
    self::$allObjects[$o->name] = $o;
  }
  
  private function __construct($name) {
    $this->name = $name;
    $this->dbObjects = array();
    self::$packages[$name] = $this;
  }
  
  private function sort() {
    usort($this->dbObjects, function($a, $b) { return $a->compare($b); } );
    foreach ($this->dbObjects as $o)
      $o->check();
  }

  public function installed() {
    foreach ($this->dbObjects as $o)
      if(!$o->check())
        return FALSE;
    return TRUE;
  }

  public function install() {
    foreach ($this->dbObjects as $o)
      if (!$o->installed)
        $o->install();
  }

  public function drop() {
    foreach (array_reverse($this->dbObjects) as $o)
      if ($o->installed)
        $o->drop();
  }

}

class pocTable {
  public $filename;
  public $name;
  public $package;
  public $installed;
  
  public function getRang() { return 0; }
  public function getDesc() { return 'table'; }
  
  public function check() {
    $n = dbHandle::slash($this->name);
    return $this->installed = count(dbHandle::select("SHOW TABLE STATUS LIKE $n;")) == 1;
  }
  
  public function drop() {
    dbHandle::run("DROP TABLE $this->name;");
    $this->check();
  }
  
  public function install() {
    dbHandle::run(file_get_contents($this->filename));
    $this->check();
  }

  public function echoLink() {
    if ($this->installed) {
?><a href="?screen=pakets&dropObject=<?PHP echo $this->name; ?>">Drop</a><?PHP
    } else {
?><a href="?screen=pakets&installObject=<?PHP echo $this->name; ?>">Install</a><?PHP
    }
  }

  public static function createFromFilename($filename) {
    $sql = file_get_contents($filename);
    if ($sql === FALSE) {
      new error("Datei '$filename' lesen fehlgeschlagen.");
      return FALSE;
    }
    $matches = array();
    $package = '.';
    $o = null;
    if (preg_match('/^\s*--\s+pocpackage\s*:\s*(\w+)/im', $sql, $matches))
      $package = $matches[1];
    if (preg_match('/\.sql$/i', $filename)) {
      $sql = preg_replace('/\/\*.*\*\//s', '', $sql);
      $sql = preg_replace('/\s*--.*$/m', '', $sql);
      $sql = preg_replace('/\s*#.*$/m', '', $sql);
      $sql = preg_replace('/^\s*/', '', $sql);
      if (preg_match('/^create\s+(\w+)\s+(\w+)/i', $sql, $matches)) {
        switch (strtolower($matches[1])) {
          case 'table':
            if (preg_match('/FOREIGN\s+KEY/i', $sql))
              $o = new pocForeignTable($filename, $matches[2], $package);
            else
              $o = new pocTable($filename, $matches[2], $package);
            break;
          case 'function':
            $o = new pocFunction($filename, $matches[2], $package);
            break;
          case 'procedure':
            $o = new pocProcedure($filename, $matches[2], $package);
            break;
        }
      }
    } elseif (preg_match('/\.poc$/i', $filename)) {
      if (preg_match('/^\s*--\s+pocpath\s*:\s*([\w\/]+)/im', $sql, $matches))
        $o = new pocPoc($filename, $matches[1], $package);
    }
    return $o;
  }

  public function compare($other) {
    if ($this->getRang() == $other->getRang())
      return strcmp($this->name, $other->name);
    return $this->getRang() > $other->getRang() ? +1 : -1;
  }
  
  public function __construct($filename, $name, $package) {
    $this->filename = $filename;
    $this->name = $name;
    $this->package = $package;
  }

}

class pocForeignTable extends pocTable {
  public function getRang() { return 1; }
  public function getDesc() { return 'table'; }
}

class pocFunction extends pocTable {
  public function getRang() { return 5; }
  public function getDesc() { return 'function'; }

  public function check() {
    $n = dbHandle::slash($this->name);
    return $this->installed = count(dbHandle::select("SHOW FUNCTION STATUS LIKE $n;")) == 1;
  }
  
  public function drop() {
    dbHandle::run("DROP FUNCTION $this->name;");
    $this->check();
  }
  
}

class pocProcedure extends pocTable {
  public function getRang() { return 6; }
  public function getDesc() { return 'procedure'; }

  public function check() {
    $n = dbHandle::slash($this->name);
    return $this->installed = count(dbHandle::select("SHOW PROCEDURE STATUS LIKE $n;")) == 1;
  }
  
  public function drop() {
    dbHandle::run("DROP PROCEDURE $this->name;");
    $this->check();
  }
  
}

class pocPoc extends pocTable {
  private $pocOk;
  
  public function getRang() { return 10; }
  public function getDesc() { return 'poc'; }

  public function check() {
    if (!isset(pocPackage::$allObjects['pocPoc']) || !pocPackage::$allObjects['pocPoc']->installed)
      return $this->installed = FALSE;
    $ok = TRUE;
    $pid = 0;
    foreach(explode('/', $this->name) as $name) {
      $name = dbHandle::slash($name);
      if ($row = array_shift(dbHandle::select("SELECT id FROM pocPoc WHERE name = $name AND parentId = $pid;"))) {
        $pid = $row['id'];
      } else {
        $ok = FALSE;
        break;
      }
    }
    return $this->installed = $ok;
  }
  
  public function drop() {
    if (!isset(pocPackage::$allObjects['pocPoc']) || !pocPackage::$allObjects['pocPoc']->installed)
      return FALSE;
  }
  
  public function install() {
    if (!isset(pocPackage::$allObjects['pocPoc']) || !pocPackage::$allObjects['pocPoc']->installed)
      return FALSE;
    $content = file_get_contents($this->filename);
    if ($content === FALSE) {
      new error("Datei '$this->filename' lesen fehlgeschlagen.");
      return FALSE;
    }
    if ($id = self::createId($this->name)) {
      if ($row = array_shift(dbHandle::select("SELECT userId, groupId, userPrivs + 0 AS userPrivs, groupPrivs + 0 AS groupPrivs, otherPrivs + 0 AS otherPrivs, mode + 0 AS mode FROM pocPoc WHERE id = $id;"))) {
        $userId = $row['userId'];
        $groupId = $row['groupId'];
        $userPrivs = $row['userPrivs'];
        $groupPrivs = $row['groupPrivs'];
        $otherPrivs = $row['otherPrivs'];
        $mode = $row['mode'];
        $title = '';
        $matches = array();
        if (preg_match('/^\s*--\s+pocUser\s*:\s*(\w+)/im', $content, $matches))
          $userId = $matches[1] == 'user' ? 2 : 1;
        if (preg_match('/^\s*--\s+pocGroup\s*:\s*(\w+)/im', $content, $matches))
          $groupId = $matches[1] == 'user' ? 3 : 1;
        if (preg_match('/^\s*--\s+pocMode\s*:\s*([\w-]+)/im', $content, $matches))
          $navigate = $this->string2Mode($matches[1]);
        if (preg_match('/^\s*--\s+pocPrivileges\s*:\s*([\w-]+)\s+([\w-]+)\s+([\w-]+)/im', $content, $matches)) {
          $userPrivs = $this->string2Privs($matches[1]);
          $groupPrivs = $this->string2Privs($matches[2]);
          $otherPrivs = $this->string2Privs($matches[3]);
        }
        if (preg_match('/^\s*--\s+pocTitle\s*:(.*)/im', $content, $matches))
          $title = trim($matches[1]);
        pocPocAttribute::distillAttributes($id, $content);
        $t = time();
        $title = dbHandle::slash($title);
        $content = dbHandle::slash($content);
        dbHandle::run("UPDATE pocPoc SET userId = $userId, groupId = $groupId, modified = $t, modifiedById = 1,
          userPrivs = $userPrivs, groupPrivs = $groupPrivs, otherPrivs = $otherPrivs, mode = $mode,
          title = $title, content = $content WHERE id = $id");
      } else {
        new error("Bearbeitung von '$this->name' fehlgeschlagen.");
      }
    } else {
      new error("Anlegen von '$this->name' fehlgeschlagen.");
    }
    $this->check();
  }
  
  public function echoLink() {
    if (!isset(pocPackage::$allObjects['pocPoc']) || !pocPackage::$allObjects['pocPoc']->installed)
      return;
    parent::echoLink();
  }

  private function string2Privs($str) {
    $priv = 0;
    $str = " $str";
    if (stripos($str, 'r')) $priv += poc::RUN_PRIV;
    if (stripos($str, 'o')) $priv += poc::OPEN_PRIV;
    if (stripos($str, 's')) $priv += poc::SELECT_PRIV;
    if (stripos($str, 'i')) $priv += poc::INSERT_PRIV;
    if (stripos($str, 'u')) $priv += poc::UPDATE_PRIV;
    if (stripos($str, 'd')) $priv += poc::DELETE_PRIV;
    return $priv;
  }

  private function string2Mode($str) {
    $mode = 0;
    $str = " $str";
    if (stripos($str, 'u')) $mode += poc::USER_NAV;
    if (stripos($str, 'g')) $mode += poc::GROUP_NAV;
    if (stripos($str, 'o')) $mode += poc::OTHER_NAV;
    return $mode;
  }

  public static function createId($path) {
    $pid = 0;
    foreach(explode('/', $path) as $name) {
      $name = dbHandle::slash($name);
      if ($row = array_shift(dbHandle::select("SELECT id FROM pocPoc WHERE name = $name AND parentId = $pid;"))) {
        $pid = $row['id'];
      } else {
        $t = time();
        dbHandle::run("INSERT INTO pocPoc (name, parentId, userId, groupId, created, createdById, modified, modifiedById, userPrivs, groupPrivs, otherPrivs, mode)
          SELECT $name, id, 1, groupId, $t, 1, $t, 1, userPrivs, groupPrivs, otherPrivs, mode FROM pocPoc WHERE id = $pid;");
        if ($row = array_shift(dbHandle::select("SELECT LAST_INSERT_ID() AS id;"))) {
          $pid = $row['id'];
        } else {
          return FALSE;
        }
      }
    }
    return $pid;
  }

}

class pocPocAttribute extends pocTable {

  private $creditId;
  private $class;
  private $debitPath = '';
  private $content = '';

  public static function distillAttributes($creditId, $src) {
    $matches = array();
    if (preg_match_all('/^\s*--\s+(pocAttribute\w*)\s*:\s*(.*)/im', $src, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        new pocPocAttribute($creditId, $match[1], $match[2]);
      }
    }
  }

  public function __construct($creditId, $class, $line) {
    $this->creditId = $creditId;
    $this->class = $class;
    $matches = array();
    $content = explode('"', $line);
    $line = array_shift($content);
    array_pop($content);
    $this->content = count($content) ? implode('"', $content) : NULL;
    $line = preg_replace('/,?\s*$/', '', $line);
    $line = preg_split('/,\s*/', $line);
    switch (count($line)) {
      case 1:
        $this->name = $line[0];
        pocPackage::$postObjects[] = $this;
        break;
      case 2:
        if ($this->content === NULL) {
          $this->name = $line[0];
          $this->content = $line[1];
        } else {
          $this->debitPath = $line[0];
          $this->name = $line[1];
        }
        pocPackage::$postObjects[] = $this;
        break;
      case 3:
        $this->debitPath = $line[0];
        $this->name = $line[1];
        $this->content = $line[2];
        pocPackage::$postObjects[] = $this;
        break;
    }
  }

  public function getRang() { return 100; }
  public function getDesc() { return 'attribute'; }
  public function check() { }
  public function drop() { }

  public function install() {
    $debitId = $this->debitPath ? pocPoc::createId($this->debitPath) : 0;
    $class = dbHandle::slash($this->class);
    $name = dbHandle::slash($this->name);
    $content = dbHandle::slash($this->content);
    $t = time();
    dbHandle::run("INSERT INTO $this->class (creditId, debitId, className, name, content, created, createdById, modified, modifiedById)
      SELECT id, $debitId, $class, $name, $content, $t, userId, $t, userId FROM pocPoc WHERE id = $this->creditId;");
  }

  public function echoLink() { }

}

function dumpinst() {
  global $button, $dbhOk, $includeOk, $configOk, $tablesOk, $userOk, $loginOk, $coreOk, $pocOk, $done;
  echo "<p>Button:&nbsp;$button, dbhOk:&nbsp;$dbhOk, includeOk:&nbsp;$includeOk, configOk:&nbsp;$configOk, tablesOk:&nbsp;$tablesOk,
    userOk:&nbsp;$userOk, loginOk:&nbsp;$loginOk, coreOk:&nbsp;$coreOk, pocOk:&nbsp;$pocOk, done:&nbsp;$done</p>\n";
}
?>