/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSessionSave (
    IN sessionData TEXT,
    IN userData TEXT)
BEGIN
  DECLARE n, idUser BIGINT DEFAULT 0;
  bodyOfProc: BEGIN
    SELECT COUNT(ts.id), tu.id
      FROM pocSession AS ts
      LEFT JOIN pocUser AS tu ON tu.id = ts.userId
      WHERE ts.id = @pocSessionId
      INTO n, idUser;
    IF n > 0 THEN
      UPDATE pocSession SET content = sessionData WHERE id = @pocSessionId;
      IF idUser THEN
        UPDATE pocUser SET content = userData WHERE id = idUser;
      END IF;
    END IF;
  END bodyOfProc;
END;