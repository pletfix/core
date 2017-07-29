CREATE TABLE "table1" (
"id" SERIAL PRIMARY KEY NOT NULL,
  "small1" SMALLINT DEFAULT 33,
  "integer1" INTEGER NOT NULL DEFAULT -44,
  "unsigned1" INTEGER DEFAULT 55,
  "bigint1" BIGINT DEFAULT 66,
  "numeric1" NUMERIC(10, 0) NOT NULL DEFAULT '1234567890',
  "numeric2" NUMERIC(4, 1) DEFAULT '123.4', 
  "float1" DOUBLE PRECISION NOT NULL DEFAULT 3.14, 
  "string1" VARCHAR(255) NOT NULL DEFAULT 'Panama',
  "string2" VARCHAR(50) COLLATE "de_DE",
  "text1" TEXT COLLATE "de_DE",
  "guid1" UUID, 
  "binary1" BYTEA, 
  "binary2" BYTEA, 
  "blob1" BYTEA, 
  "boolean1" BOOLEAN DEFAULT TRUE, 
  "date1" DATE, 
  "datetime1" TIMESTAMP(0) WITHOUT TIME ZONE, 
  "timestamp1" TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  "time1" TIME(0) WITHOUT TIME ZONE, 
  "array1" TEXT, 
  "json1" JSONB, 
  "object1" TEXT
);

COMMENT ON TABLE "table1" IS 'A test table.';
COMMENT ON COLUMN "table1"."integer1" IS 'I am cool!';
COMMENT ON COLUMN "table1"."unsigned1" IS '(DC2Type:unsigned)';
COMMENT ON COLUMN "table1"."float1" IS 'a rose is a rose';
COMMENT ON COLUMN "table1"."string1" IS 'lola';
COMMENT ON COLUMN "table1"."blob1" IS '(DC2Type:blob)';
COMMENT ON COLUMN "table1"."array1" IS 'a rose is a rose (DC2Type:array)';
COMMENT ON COLUMN "table1"."object1" IS '(DC2Type:object)';