/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSelectFlat (
  IN inId BIGINT,
  IN inNaviMode INT,
  IN outputMode INT)
bodyOfProc: BEGIN
  DECLARE n BIGINT DEFAULT 0;
  DECLARE clause, path TEXT DEFAULT '';
  DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocSelectFlat' AS content;
  --
  IF inId > 0 THEN
    SET path = pocPocPathFromId(inId);
    IF path IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocSelect' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(inId, 'select') THEN
      SELECT 403 AS id, 'Forbidden' AS name, 'pocPocSelect' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF LENGTH(path) > 0 THEN
      SET path = CONCAT(path, '/');
    END IF;
  END IF;
  --
  SET @q = pocSelectBuildWhereClause();
  IF LENGTH(@q) > 0 THEN
    SET @q = CONCAT('\n    AND (', @q, ')');
  END IF;
  SET @q = CONCAT('\n  WHERE poc.parentId = ', inId, ' AND pocPocCheckPriv(poc.id, \'open\')', @q);
  IF outputMode = 1 THEN
    DELETE FROM pocTempSelect;
    SET @q = CONCAT('INSERT INTO pocTempSelect (id, sel, hit, path) SELECT poc.id, 0, 1, CONCAT(\'',
      path, '\', poc.name)\n  FROM pocPoc AS poc', @pocSelectJoinClause, @q);
    SELECT 'pocLog' AS className, 'pocSelectFlat collect query' AS name, @q AS content;
    PREPARE stmt FROM @q;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    CALL pocPocCreatePocs;
  ELSE
    SET @q = CONCAT('SELECT \'pocResult\' AS className, ', @pocSelectColumns,
        '\n  FROM pocPoc AS poc', @pocSelectJoinClause, @q);
    IF LENGTH(@pocSelectGroupBy) > 0 THEN
      SET @q = CONCAT(@q, '\n  GROUP BY ', @pocSelectGroupBy);
    END IF;
    IF LENGTH(@pocSelectOrderBy) > 0 THEN
      SET @q = CONCAT(@q, '\n  ORDER BY ', @pocSelectOrderBy);
    END IF;
    SELECT 'pocLog' AS className, 'pocSelectFlat output query' AS name, @q AS content;
    PREPARE stmt FROM @q;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
  SET @q = '';
END;
