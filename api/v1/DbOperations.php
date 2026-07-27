<?php

/**
 * Class DbOperations
 * Handles database persistence and queries for BOM products, parts, and relationships.
 */
class DbOperations {
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * DbOperations constructor.
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Upsert a Bill of Materials (Parts and Relationships) in a transaction.
     *
     * @param array $parsedData
     * @return void
     * @throws Exception If database operations fail
     */
    public function upsertBom(array $parsedData): void {
        $this->pdo->beginTransaction();

        try {
            // 1. Prepare statements for upserting parts
            $stmtSelectPart = $this->pdo->prepare("
                SELECT id FROM Part 
                WHERE part_no = :part_no 
                LIMIT 1
            ");

            $stmtInsertPart = $this->pdo->prepare("
                INSERT INTO Part (part_no, name, unit, ref, status) 
                VALUES (:part_no, :name, :unit, :ref, 'active')
            ");

            $stmtUpdatePart = $this->pdo->prepare("
                UPDATE Part 
                SET name = :name, 
                    unit = :unit, 
                    ref = :ref, 
                    status = 'active'
                WHERE id = :id
            ");

            $partNoToIdMap = [];
            foreach ($parsedData as $part) {
                // Check if the part already exists in Part catalog
                $stmtSelectPart->execute([
                    ':part_no' => $part['part_no']
                ]);
                $existingPart = $stmtSelectPart->fetch();

                $unitVal = $part['unit'] !== '' ? $part['unit'] : 'ea';

                if ($existingPart) {
                    $partId = (int)$existingPart['id'];
                    $stmtUpdatePart->execute([
                        ':name' => $part['name'],
                        ':unit' => $unitVal,
                        ':ref' => $part['ref'],
                        ':id' => $partId
                    ]);
                } else {
                    $stmtInsertPart->execute([
                        ':part_no' => $part['part_no'],
                        ':name' => $part['name'],
                        ':unit' => $unitVal,
                        ':ref' => $part['ref']
                    ]);
                    $partId = (int)$this->pdo->lastInsertId();
                }

                // Store resolved ID for reference
                $partNoToIdMap[$part['part_no']] = $partId;
            }

            // 2. Identify all parts that act as parents in the incoming BOM
            $parentIds = [];
            foreach ($parsedData as $part) {
                $parentPartNo = $part['parent_part_no'];
                if ($parentPartNo !== null && isset($partNoToIdMap[$parentPartNo])) {
                    $parentIds[] = $partNoToIdMap[$parentPartNo];
                }
            }
            $parentIds = array_unique($parentIds);

            // 3. Clear existing relationships for these parent parts to start fresh
            if (!empty($parentIds)) {
                $inClause = implode(',', array_fill(0, count($parentIds), '?'));
                $stmtDeleteRelations = $this->pdo->prepare("
                    DELETE FROM bom_relationships 
                    WHERE parent_part_id IN ($inClause)
                ");
                $stmtDeleteRelations->execute(array_values($parentIds));
            }

            // 4. Insert or update the new relationships
            $stmtInsertRelation = $this->pdo->prepare("
                INSERT INTO bom_relationships (parent_part_id, child_part_id, quantity)
                VALUES (:parent_part_id, :child_part_id, :quantity)
                ON DUPLICATE KEY UPDATE quantity = quantity + :update_quantity
            ");

            foreach ($parsedData as $part) {
                $parentPartNo = $part['parent_part_no'];
                if ($parentPartNo !== null && isset($partNoToIdMap[$parentPartNo])) {
                    $parentId = $partNoToIdMap[$parentPartNo];
                    $childId = $partNoToIdMap[$part['part_no']];
                    $qty = (float)$part['required_quantity'];

                    $stmtInsertRelation->execute([
                        ':parent_part_id' => $parentId,
                        ':child_part_id' => $childId,
                        ':quantity' => $qty,
                        ':update_quantity' => $qty
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get all top-level products (parts that are not children of any other part).
     *
     * @return array
     */
    public function getTopLevelProducts(): array {
        $stmt = $this->pdo->query("
            SELECT id, part_no, name, unit, status 
            FROM Part 
            WHERE id NOT IN (SELECT DISTINCT child_part_id FROM bom_relationships)
              AND status = 'active'
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get exploded BOM for a top-level product.
     *
     * @param int $rootPartId
     * @return array
     * @throws Exception If a circular dependency is detected
     */
    public function getExplodedBom(int $rootPartId): array {
        $exploded = [];
        $this->explodeRecursive($rootPartId, 1.0, $exploded);
        return array_values($exploded);
    }

    /**
     * Recursive helper to calculate exploded quantities and detect cycles.
     */
    private function explodeRecursive(int $parentId, float $parentQty, array &$exploded, array $visited = []): void {
        if (in_array($parentId, $visited)) {
            throw new Exception("Cycle detected in BOM relationships involving part ID: " . $parentId);
        }
        $visited[] = $parentId;

        $stmt = $this->pdo->prepare("
            SELECT p.id, p.part_no, p.name, p.unit, r.quantity 
            FROM bom_relationships r
            JOIN Part p ON r.child_part_id = p.id
            WHERE r.parent_part_id = :parent_id
              AND p.status = 'active'
        ");
        $stmt->execute([':parent_id' => $parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($children as $child) {
            $childId = (int)$child['id'];
            $partNo = $child['part_no'];
            $name = $child['name'];
            $unit = $child['unit'];
            $qty = $parentQty * (float)$child['quantity'];

            if (isset($exploded[$partNo])) {
                $exploded[$partNo]['quantity'] += $qty;
            } else {
                $exploded[$partNo] = [
                    'part_no' => $partNo,
                    'name' => $name,
                    'unit' => $unit,
                    'quantity' => $qty
                ];
            }

            // Recurse down the branch
            $this->explodeRecursive($childId, $qty, $exploded, $visited);
        }
    }
}
