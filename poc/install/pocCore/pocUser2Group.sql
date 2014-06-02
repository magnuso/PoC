/*******************************************************************************

PoC Web-Application-Framwork
http://poc-online.net/

Copyright (c) 2013, PoC - Marcus Grundschok
Released under the MIT license
http://poc-online.net/license

-- pocPackage: pocCore

*******************************************************************************/

CREATE TABLE pocUser2Group (
  userId BIGINT NOT NULL,
  groupId BIGINT NOT NULL,
  mode SET('admin'),
  UNIQUE KEY (userId, groupId),
  FOREIGN KEY (userId) REFERENCES pocUser(id) ON DELETE CASCADE,
  FOREIGN KEY (groupId) REFERENCES pocGroup(id) ON DELETE CASCADE
) ENGINE InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
--
INSERT INTO pocUser2Group VALUES (2, 1, '');
