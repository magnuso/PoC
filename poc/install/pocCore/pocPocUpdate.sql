/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocUpdate (
    IN inId BIGINT,
    IN inName VARCHAR(64),
    IN inTitle VARCHAR(64),
    IN inContent TEXT,
    IN inMode INT)
BEGIN
  DECLARE n, userPrivs, groupPrivs, otherPrivs INT DEFAULT 0;
  DECLARE t, groupId BIGINT DEFAULT 0;
  DECLARE path TEXT DEFAULT '';
  bodyOfProc: BEGIN
    -- check
    IF inId < 1 THEN
      SELECT 406 AS id, 'Not Acceptable' AS name, 'pocPocUpdate' AS content;
      LEAVE bodyOfProc;
    END IF;
    SET path = pocPocPathFromId(inId);
    IF path IS NULL THEN
      SELECT 404 AS id, 'Not Found' AS name, 'pocPocUpdate' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocCheckPriv(inId, 'update') THEN
      SELECT 401 AS id, 'Unauthorized' AS name, 'pocPocUpdate' AS content;
      LEAVE bodyOfProc;
    END IF;
    IF NOT pocPocSaveCheck(inContent) THEN
      SELECT 403 AS id, 'Forbidden' AS name, 'pocPocUpdate' AS content;
      LEAVE bodyOfProc;
    END IF;
    -- update
    UPDATE pocPoc SET name = inName, title = inTitle, content = inContent, mode = inMode,
        modifiedById = @pocUserId, modified = UNIX_TIMESTAMP()
      WHERE id = inId;
    -- output
    SELECT 'poc' AS className, 1 AS updateFlag, tp.*, path,
        (@pocAdmin OR FIND_IN_SET('run', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('run', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('run', tp.groupPrivs))) + 0 AS runPriv,
        1 AS openPriv,
        (@pocAdmin OR FIND_IN_SET('select', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('select', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('select', tp.groupPrivs))) + 0 AS selectPriv,
        (@pocAdmin OR FIND_IN_SET('insert', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('insert', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('insert', tp.groupPrivs))) + 0 AS insertPriv,
        (@pocAdmin OR FIND_IN_SET('update', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('update', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('update', tp.groupPrivs))) + 0 AS updatePriv,
        (@pocAdmin OR FIND_IN_SET('delete', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('delete', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('delete', tp.groupPrivs))) + 0 AS deletePriv,
        tu.name AS userName, tg.name AS groupName, tuc.name AS createdByName, tum.name AS modifiedByName
      FROM pocPoc AS tp
      LEFT JOIN pocUser2Group AS tu2g ON tu2g.groupId = tp.groupId AND tu2g.userId = @pocUserId
      LEFT JOIN pocUser AS tu ON tu.id = tp.userId
      LEFT JOIN pocGroup AS tg ON tg.id = tp.groupId
      LEFT JOIN pocUser AS tuc ON tuc.id = tp.createdById
      LEFT JOIN pocUser AS tum ON tum.id = tp.modifiedById
      WHERE tp.id = inId;
  END bodyOfProc;
END;