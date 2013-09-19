/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE FUNCTION pocPocPathFromId (inId BIGINT)
RETURNS TEXT
BEGIN
  DECLARE name VARCHAR(64) DEFAULT '';
  DECLARE n, parentId BIGINT DEFAULT 0;
  DECLARE priv INT DEFAULT 0;
  DECLARE path, cache TEXT DEFAULT '';
  CREATE TEMPORARY TABLE IF NOT EXISTS pocTempPath (id BIGINT, path TEXT);
  IF inId < 1 THEN
    RETURN '';
  END IF;
  SELECT COUNT(tt.id), tt.path FROM pocTempPath AS tt WHERE tt.id = inId INTO n, path;
  IF n > 0 THEN
    RETURN path;
  END IF;
  SELECT COUNT(tp.id), tp.parentId, tp.name,
      @pocAdmin OR FIND_IN_SET('open', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('open', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('open', tp.groupPrivs))
    FROM pocPoc AS tp
    LEFT JOIN pocUser2Group AS tu2g ON tu2g.groupId = tp.groupId AND tu2g.userId = @pocUserId
    WHERE tp.id = inId
    INTO n, parentId, path, priv;
  IF n < 0 OR priv IS NULL THEN
    INSERT INTO pocTempPath (id, path) VALUES (inId, NULL);
    RETURN NULL;
  END IF;
  pathLoop: LOOP
    IF parentId > 0 THEN
      SELECT COUNT(tt.id), tp.parentId, tp.name, tt.path,
          @pocAdmin OR FIND_IN_SET('open', tp.otherPrivs) OR (@pocUserId = tp.userId AND FIND_IN_SET('open', tp.userPrivs)) OR (tu2g.groupId AND FIND_IN_SET('open', tp.groupPrivs))
        FROM pocPoc AS tp
        LEFT JOIN pocUser2Group AS tu2g ON tu2g.groupId = tp.groupId AND tu2g.userId = @pocUserId
        LEFT JOIN pocTempPath AS tt ON tt.id = inId
        WHERE tp.id = parentId
        INTO n, parentId, name, cache, priv;
      IF priv IS NULL THEN
        INSERT INTO pocTempPath (id, path) VALUES (inId, NULL);
        RETURN NULL;
      END IF;
      IF n = 1 THEN
        IF cache IS NULL THEN
          INSERT INTO pocTempPath (id, path) VALUES (inId, NULL);
          RETURN NULL;
        ELSE
          SET path = CONCAT(cache, '/', path);
          INSERT INTO pocTempPath (id, path) VALUES (inId, path);
          RETURN path;
        END IF;
      END IF;
      SELECT CONCAT(name, '/', path) INTO path;
    ELSE
      LEAVE pathLoop;
    END IF;
  END LOOP;
  INSERT INTO pocTempPath (id, path) VALUES (inId, path);
  RETURN path;
END;
