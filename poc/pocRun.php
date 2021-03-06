<?PHP

/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

*******************************************************************************/

class pocRun {

  const MIME_ATTRIBUTE_NAME = "_mime";
  const TEMPLATE_ATTRIBUTE_NAME = "_template";
  const ETC_INIT = "etc/init";
  
  const RUN_MODE_NORMAL = 0;
  const RUN_MODE_CACHE = 1;
  const RUN_MODE_SEARCH = 2;
  const RUN_MODE_ROBOT = 3;

  private static $theTemplate = NULL;
  private static $runStack = array();

  # run
  # runs a single poc. don't call. use $poc->run(...) instead.
  #
  public static function run($poc, $params = array()) {
    if (is_a($poc, "poc") && $poc->runPriv) {
      pocError::mark("run($poc->path)");
      pocWatch::create("run", $poc->path);
      self::$runStack[] = $poc->path;
      $result = eval ("?>$poc->content");
      array_pop(self::$runStack);
      return $result;
    } else {
      pocError::create("die", "Abuse of pocRun::run()");
    }
  }

  public static function getLastRun() {
    return end(self::$runStack);
  }

  # main
  # starts PoC and auto-runs the poc submitted by server-variable PATH_INFO.
  # for using PoC in your application without auto-run, just create the environment:
  # pocEnv::create(session_id()); # see below
  #
  public static function main() {
    $watch = pocWatch::create(__FUNCTION__);
    session_set_cookie_params(pocEnv::$session["cookieExpires"]);
    session_start();
    $watch->time("cookie");

    set_error_handler("pocEnv::errorHandler");

    pocEnv::create(session_id());

    if (defined(_POC_SESSION_REGENERATE_ID_))
      session_regenerate_id();
    $watch->time("env");

    if ($poc = poc::open(self::ETC_INIT)) {
      foreach ($poc as $a)
        pocEnv::$env[$a->name] = $a->content;
      if(pocEnv::$env["pocAutoload"])
        pocEnv::initAutoload(pocEnv::$env["pocAutoload"]);
      if ($poc->runPriv)
        $poc->run();
      $watch->time("init");
    }

    if(!pocEnv::$env["PATH_INFO"])
      pocEnv::$env["PATH_INFO"] = pocEnv::$env["pocHome"];

    # redirect
    if ($poc = poc::open(".")) {
      while ($redirectTo = $poc["_redirect"]->debit) {
        $poc = $redirectTo;
        pocEnv::$env["PATH_INFO"] = $poc->path;
      }
    }

    # run
    pocEnv::$env["runMode"] = pocRun::RUN_MODE_NORMAL;
    if ($poc) {
      self::runWithTemplate($poc);
      if (!pocError::hasError())
        pocEnv::quit();
      $watch->time("main run($poc->path) has error.");
    }

    pocEnv::$env["pocError"] = pocError::fetch();

    # run error poc
    if ($poc = poc::open(pocEnv::$env["pocErrorPage"])) {
      self::runWithTemplate($poc);
      if (!pocError::hasError())
        pocEnv::quit();
    }

    # something's wrong
    $watch->time("still here?");
    pocEnv::header("HTTP/1.0 500 Internal Server Error");
    pocEnv::header();

?><!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>Häh?</title>
</head>
<body>
  <h1>Wo Du wolle?</h1>
  <p><a href="<?PHP echo pocEnv::makeHttpBase(); ?>">Home?</a></p>
  <h2>pocError::</h2>
  <pre><?PHP pocError::dump(); ?></pre>
  <h2>pocLog::</h2>
  <pre><?PHP pocLog::dump(); ?></pre>
  <h2>pocEnv::</h2>
  <pre><?PHP pocEnv::dump(); ?></pre>
</body>
</html>
<?PHP

    pocError::create("die", "still here!");
  }

  private static function runWithTemplate($poc) {
    if (self::$theTemplate) {
      $poc->run();
      return;
    }
    try {
      if ($template = $poc->climb(self::TEMPLATE_ATTRIBUTE_NAME)) {
        if ($template = $template->debit) {
          $watch = pocWatch::create("runWithTemplate");
          self::$theTemplate = $template;
          $template->run($poc);
          $watch = NULL;
        } else {
          pocError::create(404, "Not Found", "template on $template->path.");
        }
      } else {
        $watch = pocWatch::create("runWithoutTemplate");
        $poc->run();
        $watch = NULL;
        if (!pocError::hasError())
          pocEnv::quit();
      }
    } catch (Exception $e) {
      pocLog::create("pocRun::main", $e->getMessage());
    }
  }

}

?>