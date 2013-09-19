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

  # run
  # runs a single poc. don't call. use $poc->run(...) instead.
  #
  public static function run($poc, $params = array()) {
    if (is_a($poc, "poc") && $poc->runPriv) {
      pocError::mark("run($poc->path)");
      pocWatch::create("run", $poc->path);
      return eval ("?>$poc->content");
    } else {
      pocError::create("die", "Abuse of pocRun::run()");
    }
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

    pocEnv::create(session_id());

    if (defined(_POC_SESSION_REGENERATE_ID_))
      session_regenerate_id();
    $watch->time("env");

    if ($poc = poc::open(self::ETC_INIT)) {
      foreach ($poc as $a)
        pocEnv::$env[$a->name] = $a->content;
      if ($poc->runPriv)
        $poc->run();
      $watch->time("init");
    }

    if(!pocEnv::$env["PATH_INFO"])
      pocEnv::$env["PATH_INFO"] = pocEnv::$env["pocHome"];
  
    if ($poc = poc::open(".")) {
      self::runWithTemplate($poc);
      $watch->time("run($poc->path)");
    }

    $watch->time("Fehler");

    # something's wrong
    $errorPage = pocEnv::$env["pocErrorPage"] ? pocEnv::$env["pocErrorPage"] : pocEnv::$env["pocHome"];
    if ($poc = poc::open($errorPage)) {
      $watch->time("open error page");
      if ($template = $poc->climb(self::TEMPLATE_ATTRIBUTE_NAME)) {
        if ($template = $template->debit) {
          $template->run($poc);
          if (pocError::hasError())
            pocLog::dump();
          pocEnv::quit();
        }
        $watch->time("error template");
      } else {
        $poc->run();
        $watch->time("error run");
        if (pocError::hasError())
          pocLog::dump();
        pocEnv::quit();
      }
    }

  # something's definitely wrong
  $watch->time("still here?");
  if (!headers_sent()) {
    header("HTTP/1.0 500 Internal Server Error");
    header(_POC_HTML_HEADER_);
  }
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
    if (!$poc->runPriv) {
      pocError::create(403, "Forbidden", $poc->path);
      return;
    }
    if ($template = $poc->climb(self::TEMPLATE_ATTRIBUTE_NAME)) {
      if ($template = $template->debit) {
        if ($template->runPriv) {
          $template->run($poc);
          pocEnv::quit();
        } else {
          pocError::create(403, "Forbidden");
        }
      } else {
        pocError::create(404, "Not Found");
      }
    } else {
      $poc->run();
      pocEnv::quit();
    }
  }

}

?>