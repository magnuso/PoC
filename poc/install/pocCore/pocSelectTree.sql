/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSelectTree (
  IN inId BIGINT,
  IN inNaviMode INT,
  IN outputMode INT)
bodyOfProc: BEGIN
  DECLARE n BIGINT DEFAULT 0;
  DECLARE path TEXT DEFAULT '';
  DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocSelectTree' AS content;
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
    IF STRCMP(path, '') > 0 THEN
      SET path = CONCAT(path, '/');
    END IF;
  END IF;
  --
  SET @q = pocSelectBuildWhereClause();
  IF LENGTH(@q) > 0 THEN
    SET @q = CONCAT('(', @q, ') + 0');
  ELSE
    SET @q = '1';
  END IF;
  SET @n = 0;
  SET @q = CONCAT('INSERT INTO pocTempSelect (id, sel, hit, root, path)\nSELECT DISTINCT poc.id, ?, ', @q,
    ', IF(ptid.root = 0, poc.id, ptid.root), CONCAT(ptid.path, poc.name)\n  FROM pocTempIds AS ptid\n    JOIN pocPoc AS poc ON poc.parentId = ptid.tempId', @pocSelectJoinClause,
    '\n  WHERE pocPocCheckPriv(poc.id, \'open\')');
  SELECT 'pocLog' AS className, 'pocSelectTree collect query' AS name, @q AS content;
  PREPARE stmt FROM @q;
  DELETE FROM pocTempSelect;
  DELETE FROM pocTempIds;
  INSERT INTO pocTempIds (tempId, root, path) VALUES (inId, 0, path);
  treeLoop: LOOP
    SET @n = @n + 1;
    EXECUTE stmt USING @n;
    IF ROW_COUNT() < 1 THEN
      LEAVE treeLoop;
    END IF;
    DELETE FROM pocTempIds;
    INSERT INTO pocTempIds (tempId, root, path)
      SELECT pts.id, pts.root, CONCAT(pts.path, '/') FROM pocTempSelect AS pts
        WHERE pts.sel = (@n) AND pocPocCheckPriv(pts.id, 'select');
  END LOOP;
  DEALLOCATE PREPARE stmt;
  DELETE FROM pocTempSelect WHERE hit = 0;
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
    SELECT 'pocLog' AS className, 'pocSelectTree output query' AS name, @q AS content;
    PREPARE stmt FROM @q;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
  SET @q = '';
END;
