CREATE TABLE "table1" (
"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "small1" SMALLINT DEFAULT 33,
  "integer1" INT NOT NULL DEFAULT -44,
  "unsigned1" INT UNSIGNED DEFAULT 55,
  "bigint1" BIGINT DEFAULT 66,
  "numeric1" NUMERIC(10, 0) NOT NULL DEFAULT '1234567890',
  "numeric2" NUMERIC(4, 1) DEFAULT '123.4',
  "float1" DOUBLE NOT NULL DEFAULT 3.14,
  "string1" VARCHAR(255) NOT NULL DEFAULT 'Panama' COLLATE NOCASE,
  "string2" VARCHAR(50) COLLATE BINARY,
  "text1" TEXT COLLATE BINARY,
  "guid1" UUID,
  "binary1" VARBINARY(2),
  "binary2" VARBINARY(3),
  "blob1" BLOB,
  "boolean1" BOOLEAN DEFAULT 1,
  "date1" DATE,
  "datetime1" DATETIME,
  "timestamp1" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "time1" TIME,
  "array1" TEXT,
  "json1" TEXT,
  "object1" TEXT
);

DELETE FROM _comments WHERE table_name = 'table1';
INSERT INTO _comments (table_name, content) VALUES ('table1', 'A test table.');
INSERT INTO _comments (table_name, column_name, content) VALUES ('table1', 'integer1', 'I am cool!');
INSERT INTO _comments (table_name, column_name, content) VALUES ('table1', 'float1', 'a rose is a rose');
INSERT INTO _comments (table_name, column_name, content) VALUES ('table1', 'string1', 'lola');
INSERT INTO _comments (table_name, column_name, content) VALUES ('table1', 'array1', 'a rose is a rose (DC2Type:array)');
INSERT INTO _comments (table_name, column_name, content) VALUES ('table1', 'json1', '(DC2Type:json)');
INSERT INTO _comments (table_name, column_name, content) VALUES ('table1', 'object1', '(DC2Type:object)');