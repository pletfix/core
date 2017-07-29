CREATE TABLE [table1] (
[id] INT NOT NULL IDENTITY(1,1) PRIMARY KEY,
  [small1] SMALLINT DEFAULT 33 NULL,
  [integer1] INT DEFAULT -44 NOT NULL,
  [unsigned1] INT DEFAULT 55 NULL,
  [bigint1] BIGINT DEFAULT 66 NULL,
  [numeric1] NUMERIC(10, 0) DEFAULT '1234567890' NOT NULL,
  [numeric2] NUMERIC(4, 1) DEFAULT '123.4' NULL,
  [float1] FLOAT DEFAULT 3.14 NOT NULL,
  [string1] NVARCHAR(255) DEFAULT 'Panama' NOT NULL,
  [string2] NVARCHAR(50) COLLATE Latin1_General_CS_AS NULL,
  [text1] TEXT COLLATE Latin1_General_CS_AS NULL,
  [guid1] UNIQUEIDENTIFIER NULL,
  [binary1] VARBINARY(2) NULL,
  [binary2] VARBINARY(3) NULL,
  [blob1] IMAGE NULL,
  [boolean1] BIT DEFAULT 1 NULL,
  [date1] DATE NULL,
  [datetime1] DATETIME NULL,
  [timestamp1] DATETIMEOFFSET DEFAULT CURRENT_TIMESTAMP NOT NULL,
  [time1] TIME NULL,
  [array1] TEXT NULL,
  [json1] TEXT NULL,
  [object1] TEXT NULL
);

EXEC sp_addextendedproperty 'MS_Description', 'A test table.', 'SCHEMA', 'dbo', 'TABLE', 'table1';
EXEC sp_addextendedproperty 'MS_Description', 'I am cool!', 'SCHEMA', 'dbo', 'TABLE', 'table1', 'COLUMN', 'integer1';
EXEC sp_addextendedproperty 'MS_Description', '(DC2Type:unsigned)', 'SCHEMA', 'dbo', 'TABLE', 'table1', 'COLUMN', 'unsigned1';
EXEC sp_addextendedproperty 'MS_Description', 'a rose is a rose', 'SCHEMA', 'dbo', 'TABLE', 'table1', 'COLUMN', 'float1';
EXEC sp_addextendedproperty 'MS_Description', 'lola', 'SCHEMA', 'dbo', 'TABLE', 'table1', 'COLUMN', 'string1';
EXEC sp_addextendedproperty 'MS_Description', 'a rose is a rose (DC2Type:array)', 'SCHEMA', 'dbo', 'TABLE', 'table1', 'COLUMN', 'array1';
EXEC sp_addextendedproperty 'MS_Description', '(DC2Type:json)', 'SCHEMA', 'dbo', 'TABLE', 'table1', 'COLUMN', 'json1';
EXEC sp_addextendedproperty 'MS_Description', '(DC2Type:object)', 'SCHEMA', 'dbo', 'TABLE', 'table1', 'COLUMN', 'object1';





