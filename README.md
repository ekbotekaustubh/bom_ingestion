# BOM Ingestion System

A containerized Bill of Materials (BOM) ingestion pipeline written in PHP. It supports CSV, Excel, and JSON formats, parses the nested parts structure, and handles transactional upserts into a MySQL database.

## Prerequisites

Before starting, ensure you have the following installed on your machine:
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

## Running with Docker

You can easily run the application, MySQL database, and phpMyAdmin using the provided Docker Compose configuration.

### 1. Build and Start the Containers

From the root directory of the project, run:

```bash
docker compose up -d --build
```

This will download/build and spin up three containers:
- **Web Application** (`web`) running on **PHP 8.2 + Apache** at [http://localhost:8083](http://localhost:8083)
- **Database** (`db`) running on **MySQL 8.0** at `db:3306` (locally exposed on port `3306`)
- **Database Management** (`phpmyadmin`) at [http://localhost:8081](http://localhost:8081)

### 2. Automatic Schema Initialization

The MySQL database schema is automatically initialized on the first startup. 
- The [schema.sql](schema.sql) file is mounted into `/docker-entrypoint-initdb.d/` inside the MySQL container.
- When the container first boots, it runs this SQL script, which creates the `bom_ingestion` database and the `Part` table.

## Accessing the Interfaces

- **BOM Ingestion UI**: [http://localhost:8083/index.php](http://localhost:8083/index.php) (upload BOM files here)
- **phpMyAdmin UI**: [http://localhost:8081](http://localhost:8081)
  - **Host**: `db`
  - **Username**: `root`
  - **Password**: `Root@1234`

## Stopping the Containers

To stop and remove the containers, run:

```bash
docker compose down
```

If you want to completely clear the database volumes to perform a clean re-initialization:

```bash
docker compose down -v
```
