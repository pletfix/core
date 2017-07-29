CREATE TABLE "departments" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" VARCHAR(255)
);

CREATE TABLE "employees" (
"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "name" VARCHAR(255)
);

CREATE TABLE "department_employee" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "department_id" INT,
  "employee_id" INT
);

CREATE TABLE "pictures" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "imageable_type" VARCHAR(255),
  "imageable_id" INT,
  "name" VARCHAR(255)
);

CREATE TABLE "profiles" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "employee_id" INT,
  "name" VARCHAR(255)
);

CREATE TABLE "salaries" (
"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  "employee_id" INT,
  "name" VARCHAR(255)
);

INSERT INTO employees (name) VALUES
  ('Anton'),
  ('Berta'),
  ('Charles');

INSERT INTO departments (name) VALUES
  ('Development'),
  ('Marketing'),
  ('HR'),
  ('Sales');

INSERT INTO department_employee (department_id, employee_id) VALUES
  (1, 1),
  (2, 1),
  (2, 2),
  (3, 2);

INSERT INTO pictures (imageable_type, imageable_id, name) VALUES
  ('Core\Tests\Models\Employee',   1, 'Picture Anton 1'),
  ('Core\Tests\Models\Employee',   1, 'Picture Anton 2'),
  ('Core\Tests\Models\Employee',   2, 'Picture Berta 1'),
  ('Core\Tests\Models\Department', 1, 'Picture Development'),
  ('Core\Tests\Models\Department', 2, 'Picture Marketing');

INSERT INTO profiles (employee_id, name) VALUES
  (1, 'Profile Anton'),
  (2, 'Profile Berta');

INSERT INTO salaries (employee_id, name) VALUES
  (1, 'Salary Anton 1'),
  (1, 'Salary Anton 2'),
  (2, 'Salary Berta 1'),
  (2, 'Salary Berta 2');