/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE PROCEDURE pocTempTablesReset ()
BEGIN
  DELETE FROM pocTempSelect;
  DELETE FROM pocTempIds;
  DELETE FROM pocTempWhere;
  SET @pocSelectJoinClause = '', @pocSelectOrderBy = '', @pocSelectGroupBy = '', @pocSelectColumns = '';
  SELECT 'pocCountSelect' AS className, 0 AS count;
END;
