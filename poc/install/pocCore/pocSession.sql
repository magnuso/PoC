/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE TABLE pocSession (
  id BIGINT NOT NULL auto_increment,
  userId BIGINT NOT NULL,
  created BIGINT NOT NULL,
  modified BIGINT NOT NULL,
  mode SET('stay'),
  name VARCHAR(64) NOT NULL,
  content TEXT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY name (name)
) ENGINE InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
