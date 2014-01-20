/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSelectInsertWhere (
    IN inWhere TEXT,
    IN inMode VARCHAR(16))
BEGIN
  INSERT INTO pocTempWhere (whereClause, whereMode) VALUES (inWhere, inMode);
END;
