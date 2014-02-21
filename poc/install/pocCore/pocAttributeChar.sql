/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE TABLE pocAttributeChar (
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
  title VARCHAR(64) NOT NULL,
  content VARCHAR(256) NOT NULL,
  value DOUBLE NOT NULL,
  PRIMARY KEY (id),
  INDEX (creditId, name),
  FOREIGN KEY (creditId) REFERENCES pocPoc(id) ON DELETE CASCADE,
  FOREIGN KEY (debitId) REFERENCES pocPoc(id) ON DELETE SET NULL,
  FOREIGN KEY (voucherId) REFERENCES pocPoc(id) ON DELETE SET NULL
) ENGINE InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
--
INSERT INTO pocAttributeChar VALUES (1, 8, 0, 0, 0, 1, 0, 1, 'pocAttributeChar', 'pocHTTP', '', 'http://', 0.0);
INSERT INTO pocAttributeChar VALUES (2, 8, 0, 0, 0, 1, 0, 1, 'pocAttributeChar', 'pocHome', '', 'www/home', 0.0);
