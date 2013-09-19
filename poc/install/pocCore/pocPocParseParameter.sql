/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE FUNCTION pocPocParseParameter (inText TEXT)
RETURNS TEXT
BEGIN
  DECLARE pos INT DEFAULT 0;
  DECLARE outText, restText TEXT DEFAULT '';

  SET pos = LOCATE('\n', inText);
  SELECT LEFT(inText, pos - 1), CONCAT(SUBSTRING(inText, pos + 1), '\n') INTO restText, inText;
  theLoop: LOOP
    SELECT LOCATE('?', restText) INTO pos;
    IF pos < 1 THEN
      LEAVE theLoop;
    END IF;
    SELECT LEFT(restText, pos - 1), SUBSTRING(restText, pos + 1) INTO outText, restText;
    SET pos = LOCATE('\n', inText);
    SELECT CONCAT(outText, LEFT(inText, pos - 1)), SUBSTRING(inText, pos + 1) INTO outText, inText;
  END LOOP;
  RETURN outText;
END;
