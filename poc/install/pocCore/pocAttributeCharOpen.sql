/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocAttributeCharOpen (
    IN inId BIGINT)
BEGIN
  DECLARE n INT DEFAULT 0;
  bodyOfProc: BEGIN
    IF inId > 0 THEN
      SELECT COUNT(*), creditId FROM pocAttributeChar WHERE id = inId INTO n, inId;
      IF n > 0 THEN
        CALL pocPocOpenById(inId);
      END IF;
    END IF;
  END bodyOfProc;
END;