CREATE TABLE "table2" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL);

DELETE FROM _comments WHERE table_name = 'table2';
INSERT INTO _comments (table_name, column_name, content) VALUES ('table2', 'id', '(DC2Type:bigidentity)');