/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocPocCreatePocs ()
BEGIN
  DECLARE userName, groupName VARCHAR(64) DEFAULT '';
  DECLARE loopId, parentId, userId, groupId BIGINT DEFAULT 0;
  DECLARE done, userPrivs, groupPrivs, otherPrivs, runPriv, openPriv, selectPriv, insertPriv, updatePriv, deletePriv INT DEFAULT 0;
  DECLARE loopPath TEXT;
  bodyOfProc: BEGIN
    DECLARE cur CURSOR FOR SELECT id, path FROM pocTempSelect WHERE hit > 0 ORDER BY path;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocPocInsert' AS content;
    OPEN cur;
    theLoop: LOOP
      FETCH cur INTO loopId, loopPath;
      IF done THEN
        LEAVE theLoop;
      END IF;
      SELECT (@pocAdmin OR FIND_IN_SET('run', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('run', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('run', tp.groupPrivs))) + 0,
          1,
          (@pocAdmin OR FIND_IN_SET('select', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('select', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('select', tp.groupPrivs))) + 0,
          (@pocAdmin OR FIND_IN_SET('insert', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('insert', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('insert', tp.groupPrivs))) + 0,
          (@pocAdmin OR FIND_IN_SET('update', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('update', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('update', tp.groupPrivs))) + 0,
          (@pocAdmin OR FIND_IN_SET('delete', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('delete', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('delete', tp.groupPrivs))) + 0,
          tu.name, tg.name
        FROM pocPoc AS tp
        LEFT JOIN pocUser2Group AS tu2g ON tu2g.groupId = tp.groupId AND tu2g.userId = @pocUserId
        LEFT JOIN pocUser AS tu ON tu.id = tp.userId
        LEFT JOIN pocGroup AS tg ON tg.id = tp.groupId
        WHERE tp.id = loopId
        INTO runPriv, openPriv, selectPriv, insertPriv, updatePriv, deletePriv, userName, groupName;
      -- poc
      SELECT 'poc' AS className, loopPath AS path, tp.id, tp.groupId, tp.userId, tp.groupId, tp.created, tp.createdById, tp.modified, tp.modifiedById,
          tp.userPrivs + 0 AS userPrivs, tp.groupPrivs + 0 AS groupPrivs, tp.otherPrivs + 0 AS otherPrivs,
          tuc.name AS createdByName, tum.name AS modifiedByName,
          runPriv, openPriv, selectPriv, insertPriv, updatePriv, deletePriv, userName, groupName,
          tp.name, tp.title, tp.content, tp.mode + 0 AS mode, (SELECT COUNT(tc.id) FROM pocPoc AS tc WHERE tc.parentId = tp.id) AS children
        FROM pocPoc AS tp
        LEFT JOIN pocUser AS tuc ON tuc.id = tp.createdById
        LEFT JOIN pocUser AS tum ON tum.id = tp.modifiedById
        WHERE tp.id = loopId;
      -- pocAttributeChar
      SELECT ta.*, runPriv, openPriv, selectPriv, insertPriv, updatePriv, deletePriv, userName, groupName,
          tuc.name AS createdByName, tum.name AS modifiedByName
        FROM pocAttributeChar AS ta
        LEFT JOIN pocUser AS tuc ON tuc.id = ta.createdById
        LEFT JOIN pocUser AS tum ON tum.id = ta.modifiedById
        WHERE ta.creditId = loopId;
      -- pocAttributeDouble
      SELECT ta.*, runPriv, openPriv, selectPriv, insertPriv, updatePriv, deletePriv, userName, groupName,
          tuc.name AS createdByName, tum.name AS modifiedByName
        FROM pocAttributeDouble AS ta
        LEFT JOIN pocUser AS tuc ON tuc.id = ta.createdById
        LEFT JOIN pocUser AS tum ON tum.id = ta.modifiedById
        WHERE ta.creditId = loopId;
      -- pocAttributeInt
      SELECT ta.*, runPriv, openPriv, selectPriv, insertPriv, updatePriv, deletePriv, userName, groupName,
          tuc.name AS createdByName, tum.name AS modifiedByName
        FROM pocAttributeInt AS ta
        LEFT JOIN pocUser AS tuc ON tuc.id = ta.createdById
        LEFT JOIN pocUser AS tum ON tum.id = ta.modifiedById
        WHERE ta.creditId = loopId;
      -- pocAttributeText
      SELECT ta.*, runPriv, openPriv, selectPriv, insertPriv, updatePriv, deletePriv, userName, groupName,
          tuc.name AS createdByName, tum.name AS modifiedByName
        FROM pocAttributeText AS ta
        LEFT JOIN pocUser AS tuc ON tuc.id = ta.createdById
        LEFT JOIN pocUser AS tum ON tum.id = ta.modifiedById
        WHERE ta.creditId = loopId;
    END LOOP;
    CLOSE cur;
  END bodyOfProc;
END;
