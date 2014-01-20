/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSelectStem (
  IN inId BIGINT,
  IN inNaviMode INT,
  IN outputMode INT)
bodyOfProc: BEGIN
  DECLARE n BIGINT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocSelectFlat' AS content;
  --
  IF inId > 0 THEN
    SET @inId = inId, @path = pocPocPathFromId(inId);
    IF @path IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocSelect' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(inId, 'select') THEN
      SELECT 403 AS id, 'Forbidden' AS name, 'pocPocSelect' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF STRCMP(@path, '') > 0 THEN
      SET @path = CONCAT(@path, '/');
    END IF;
  ELSE
    SET @inId = 0, @path = '';
  END IF;
  --
  SET @q = pocSelectBuildWhereClause();
  IF LENGTH(@q) > 0 THEN
    SET @q = CONCAT('\n    AND (', @q, ')');
  END IF;
  SET @q = CONCAT('INSERT INTO pocTempSelect (id, sel, hit, path)\nSELECT DISTINCT poc.id, 0, 1, CONCAT(?, poc.name)\n  FROM pocPoc AS poc', @pocSelectJoinClause,
    '\n  WHERE poc.parentId = ? AND pocPocCheckPriv(poc.id, \'open\')', @q);
  SELECT 'pocLog' AS className, 'pocSelectStem collect query' AS name, @q AS content;
  PREPARE stmt FROM @q;
  DELETE FROM pocTempSelect;
  stemLoop: LOOP
    IF pocPocCheckPriv(@inId, 'select') THEN
      EXECUTE stmt USING @path, @inId;
    END IF;
    IF @inId < 1 THEN
      LEAVE stemLoop;
    END IF;
    SELECT poc.parentId, IF(poc.parentId > 0, CONCAT(pocPocPathFromId(poc.parentId), '/'), '') FROM pocPoc AS poc WHERE poc.id = @inId INTO @inId, @path;
    IF @inId IS NULL THEN
      LEAVE stemLoop;
    END IF;
  END LOOP;
  DEALLOCATE PREPARE stmt;
  --
  IF outputMode = 1 THEN
    CALL pocPocCreatePocs;
  ELSE
    SET @q = CONCAT('SELECT DISTINCT \'pocResult\' AS className, ', @pocSelectColumns,
      '\n  FROM pocTempSelect AS pts\n    JOIN pocPoc AS poc ON poc.id = pts.id', @pocSelectJoinClause);
    IF LENGTH(@pocSelectGroupBy) > 0 THEN
      SET @q = CONCAT(@q, '\n  GROUP BY ', @pocSelectGroupBy);
    END IF;
    IF LENGTH(@pocSelectOrderBy) > 0 THEN
      SET @q = CONCAT(@q, '\n  ORDER BY ', @pocSelectOrderBy);
    END IF;
    SELECT 'pocLog' AS className, 'pocSelectStem output query' AS name, @q AS content;
    PREPARE stmt FROM @q;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
  SET @q = '';
END;
