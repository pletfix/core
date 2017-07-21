CREATE TABLE "authors" (
"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" VARCHAR(255)
);

CREATE TABLE "books" (
"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "author_id" INT,
  "title" VARCHAR(255)
);

INSERT INTO authors (name) VALUES ('Author 1');
INSERT INTO books (author_id, title) VALUES (1, 'Book 1a');
INSERT INTO books (author_id, title) VALUES (1, 'Book 1b');

INSERT INTO authors (name) VALUES ('Author 2');
INSERT INTO books (author_id, title) VALUES (2, 'Book 2a');
INSERT INTO books (author_id, title) VALUES (2, 'Book 2b');
INSERT INTO books (author_id, title) VALUES (2, 'Book 2c');
