/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocSelectInsertJoin (
    IN inTable VARCHAR(64),
    IN inAs VARCHAR(32),
    IN inOn VARCHAR(64),
    IN inClass VARCHAR(64),
    IN inName VARCHAR(64))
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION SELECT 400 AS id, 'SQLEXCEPTION' AS name, 'pocSelectInsertJoin' AS content;
  --
  SET @pocSelectJoinClause = CONCAT(@pocSelectJoinClause, '\n    LEFT JOIN ', inTable, ' AS ', inAs,
      ' ON ', inAs, '.', inOn, ' = poc.id AND ', inAs, '.className = \'', inClass, '\' AND ', inAS, '.name = \'', inName, '\'');
END;
