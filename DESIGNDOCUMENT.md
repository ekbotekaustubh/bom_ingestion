- how your pipeline works end to end?
    Ans => 
    The pipeline ingests multi-format hierarchical files, validates them, structures them as an adjacency list, and persists them transactionally into a MySQL database.
    - **Upload & Route**: A client uploads a file through index.php. It is routed to api/v1/process.php
    - **Factory Selection**: ParserFactory determines the file type (CSV, JSON, XLS, or XLSX) and instantiates the correct parser class implementing BomImporterInterface.
    - **Parsing**: The importer reads the file and returns a standardized flat array of items containing part numbers, names, quantities, and their immediate parent part numbers.
    - **Transactional Database Upsert**: DbOperations executes:
        - Inserts or updates part records in the Part catalog table.
        - Computes unique database IDs.
        - Clears existing direct child relationships for any part acting as a parent in this upload.
        - Inserts the new hierarchical links into bom_relationships using ON DUPLICATE KEY UPDATE to aggregate duplicate child rows under the same parent.
- what are the DB tables?
    Ans => 

    **Part**: The main catalog of all unique part numbers. It stores normalized data about each part.

    Columns:

    id: Primary key (auto-increment)

    part_no: The part number (unique identifier)

    name: Description of the part

    unit: Unit of measure (e.g., "ea", "kg")

    ref: Reference or category (e.g., "BOM", "BOM Child")

    status: Active or inactive

    **bom_relationships**: A junction table that stores the hierarchical parent-child relationships between parts.

    Columns:

    id: Primary key (auto-increment)

    parent_part_id: Foreign key referencing the id in the Part table (the parent part)

    child_part_id: Foreign key referencing the id in the Part table (the child part)

    quantity: The amount of the child part required for one unit of the parent part

- How do you model and store BOM data?
    Ans => 

    We model the Bill of Materials (BOM) hierarchy as a **Directed Acyclic Graph (DAG)**. 

    1. **Nodes (Components/Parts)**:
    Represented by the `Part` table. Each physical part or sub-assembly exists as a single unique row in this table. This decouples the metadata of the part (name, unit of measure) from where it lives in the hierarchy.

    2. **Edges (Relationships/Quantities)**:
    Represented by the `bom_relationships` table. Each parent-child link constitutes a directed edge from a parent assembly to its child component, carrying an edge weight (`quantity`) indicating how many units of the child are required to produce one unit of the parent.

    By storing the BOM as a normalized set of nodes and edges rather than nesting them in a single table, we avoid data redundancy and prevent data integrity issues (e.g., if a part's name changes, we only update it in one place in the `Part` table).

- How does your data model support the required queries?
    Ans => 

    1. **Querying Top-Level Products (Root Nodes)**:
    In a Directed Acyclic Graph, a top-level product is a node with no incoming edges (it is never a child). The database supports this query by looking for active parts whose `id` does not exist in the `child_part_id` column of `bom_relationships`:
    ```sql
    SELECT id, part_no, name FROM Part 
    WHERE id NOT IN (SELECT DISTINCT child_part_id FROM bom_relationships)
        AND status = 'active';
    ```
    This query is fast and index-friendly.

    2. **Calculating Exploded BOM (Multi-level Rollup)**:
    The exploded BOM must return all sub-components under a product, with quantities multiplied down the branches and summed across duplicate nodes. 
    - **Direct Child Traversal**: To find direct children and their quantities, we query `bom_relationships` joined with `Part`:
        ```sql
        SELECT p.id, p.part_no, p.name, p.unit, r.quantity 
        FROM bom_relationships r
        JOIN Part p ON r.child_part_id = p.id
        WHERE r.parent_part_id = :parent_id AND p.status = 'active';
        ```
    - **Recursive Rollup (DFS)**: Starting from the root product, we fetch children, multiply their quantities by the parent's accumulated quantity, store them in a hash map keyed by `part_no`, and recursively call the function for each child.
    - **Summing Duplicates**: If a `part_no` already exists in our map, we add the newly calculated quantity to the existing quantity. This supports rollup when a component appears in multiple locations in the tree.
    - **Cycle Prevention**: We maintain a path history array. If we encounter a node already present in the history, a cycle is detected, and we throw an exception to halt recursion.

- what tradeoffs you made
    Ans => 

    - **Relational Schema (MySQL) vs. Graph Database (Neo4j)**: We chose MySQL because it is globally supported, lightweight, and ensures robust ACID transactional integrity. While a graph database like Neo4j would natively handle tree traversals in a single query, MySQL is a better fit for standard IT architectures and requires less operations overhead.
    - **Application-level Recursion (PHP) vs. Recursive Common Table Expressions (CTEs)**: We implemented tree traversal and quantity calculations in PHP rather than raw SQL CTEs. This makes cycle detection, custom error reporting, and in-memory key-based accumulation simpler to write and maintain, at the expense of making multiple database hits down the tree structure.
    - **Partial Overwrites (Parent-Based Deletion) vs. Full DB Wipe**: During a file upload, we delete and rebuild relationships only for the parents present in the uploaded BOM file. This prevents other unrelated BOMs in the database from being broken, but it means that unused/orphaned parts remain in the `Part` table unless a clean-up task is executed.

- what you intentionally left out of the prototype
    Ans => 

    - **BOM Versioning & Revisions**: Currently, re-uploading a BOM updates/overwrites the active relationships in-place. In a full production system, we would introduce a `version_id` or `revision_id` to maintain historical versions and track change-logs.
    - **Multi-Tenancy**: The part catalog is globally shared across all products. Production deployment would require separating parts by client or organization using a `tenant_id` column on both tables.
    - **Asynchronous Ingestion Queues**: Large BOM files (thousands of rows) processed synchronously might hit PHP timeout limits or block HTTP requests. We left out a queue worker system (such as Redis/RabbitMQ + worker runners) in this prototype to keep the setup simple and easy to run.

- What design patterns are used in this application?
    Ans => 

    **Factory Method Pattern**:
    The `ParserFactory` contains a static `create()` method that instantiates the appropriate importer class (`CsvImporter`, `JsonImporter`, or `ExcelImporter`) based on the uploaded file's extension. This decouples the client (`process.php`) from the instantiation logic of individual parser classes.

    **Strategy Pattern**:
    Every parser implements the `BomImporterInterface` defining the common `parse(string $filePath): array` algorithm. The client treats all importers interchangeably, allowing the application to swap out different file-parsing algorithms at runtime without changing the core controller logic.

    **Data Access Object (DAO) / Table Data Gateway Pattern**:
    All SQL queries, transactions, inserts, updates, and recursive tree traversals are encapsulated in the `DbOperations` class, separating persistence logic from routing and request-parsing logic.

    **Adjacency List Model (Graph Database Pattern)**:
    Hierarchical parent-child relationships are stored in the relational database by linking `parent_part_id` to `child_part_id` in a separate `bom_relationships` junction table.

    **Composite / Tree Traversal (Recursive Rollup)**:
    A Depth-First Search (DFS) algorithm recursively resolves tree structures to multiply parent quantities down branches and sum them across common nodes to construct the exploded BOM rollup.