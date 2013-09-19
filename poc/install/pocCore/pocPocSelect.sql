/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocSelect (
    IN inId BIGINT,
    IN resultMode INT,
    IN pocMode INT,
    IN likeName VARCHAR(64),
    IN likeContent TEXT)
BEGIN
  DECLARE n BIGINT DEFAULT 0;
  DECLARE selectPriv INT DEFAULT 0;
  DECLARE path TEXT DEFAULT '';
  bodyOfProc: BEGIN
    DELETE FROM pocTempSelect;
    IF inId > 0 THEN
      SET path = pocPocPathFromId(inId);
      IF path IS NULL THEN
        SELECT 404 AS id, 'Not Found' AS name, 'pocPocSelect' AS content;
        LEAVE bodyOfProc;
      END IF;
      IF NOT pocPocCheckPriv(inId, 'select') THEN
        SELECT 404 AS id, 'Not Found' AS name, 'pocPocSelect' AS content;
        LEAVE bodyOfProc;
      END IF;
      SET path = CONCAT(path, '/');
    END IF;
    --
    INSERT INTO pocTempSelect (id, sel, hit, path) SELECT id, 0, 1, CONCAT(path, name)
      FROM pocPoc
      WHERE parentId = inId AND pocPocCheckPriv(id, 'open') AND
        (pocMode = 0 OR pocMode & mode) AND (name LIKE(likeName) OR content LIKE(likeContent));
    SELECT 'pocCountSelect' AS className, COUNT(*) AS count FROM pocTempSelect;
    --
    IF resultMode = 0 THEN
      CALL pocPocCreatePocs;
    ELSEIF resultMode = 1 THEN
      CALL pocPocCreateResults('');
    END IF;
  END bodyOfProc;
END;
