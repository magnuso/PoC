/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE FUNCTION pocPocSaveCheck (code TEXT)
RETURNS INT
BEGIN
  DECLARE ok INT DEFAULT 1;
  IF code REGEXP '<\\\\?' THEN
    SET ok = (@pocAdmin OR @pocCoder) + 0;
  END IF;
  RETURN ok;
END;