/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE TABLE pocAttributeDouble (
  id BIGINT NOT NULL auto_increment,
  creditId BIGINT NOT NULL,
  debitId BIGINT NOT NULL,
  voucherId BIGINT NOT NULL,
  created BIGINT NOT NULL,
  createdById BIGINT NOT NULL,
  modified BIGINT NOT NULL,
  modifiedById BIGINT NOT NULL,
  className VARCHAR(64) NOT NULL,
  name VARCHAR(64) NOT NULL,
  title VARCHAR(255) NOT NULL,
  content DOUBLE NOT NULL,
  value DOUBLE NOT NULL,
  PRIMARY KEY (id),
  INDEX (creditId, name),
  FOREIGN KEY (creditId) REFERENCES pocPoc(id) ON DELETE CASCADE
) ENGINE InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
