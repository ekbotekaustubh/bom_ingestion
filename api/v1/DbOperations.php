<?php

/**
 * Class DbOperations
 * Handles database persistence and upsert operations for BOM products and parts.
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
     * Upsert a Bill of Materials (Parts only) in a transaction.
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
                  AND parent_part_id = :parent_part_id
                LIMIT 1
            ");

            $stmtInsertPart = $this->pdo->prepare("
                INSERT INTO Part (part_no, name, unit, parent_part_id, required_quantity, ref, status) 
                VALUES (:part_no, :name, :unit, :parent_part_id, :required_quantity, :ref, 'active')
            ");

            $stmtUpdatePart = $this->pdo->prepare("
                UPDATE Part 
                SET name = :name, 
                    unit = :unit, 
                    required_quantity = :required_quantity, 
                    ref = :ref, 
                    status = 'active'
                WHERE id = :id
            ");

            $partNoToIdMap = [];
            foreach ($parsedData as $part) {
                $parentPartNo = $part['parent_part_no'];
                $parentPartId = 0;
                if ($parentPartNo !== null && isset($partNoToIdMap[$parentPartNo])) {
                    $parentPartId = $partNoToIdMap[$parentPartNo];
                }

                // Check if the part already exists
                $stmtSelectPart->execute([
                    ':part_no' => $part['part_no'],
                    ':parent_part_id' => $parentPartId
                ]);
                $existingPart = $stmtSelectPart->fetch();

                $unitVal = $part['unit'] !== '' ? $part['unit'] : 'ea';

                if ($existingPart) {
                    $partId = (int)$existingPart['id'];
                    $stmtUpdatePart->execute([
                        ':name' => $part['name'],
                        ':unit' => $unitVal,
                        ':required_quantity' => $part['required_quantity'],
                        ':ref' => $part['ref'],
                        ':id' => $partId
                    ]);
                } else {
                    $stmtInsertPart->execute([
                        ':part_no' => $part['part_no'],
                        ':name' => $part['name'],
                        ':unit' => $unitVal,
                        ':parent_part_id' => $parentPartId,
                        ':required_quantity' => $part['required_quantity'],
                        ':ref' => $part['ref']
                    ]);
                    $partId = (int)$this->pdo->lastInsertId();
                }

                // Store resolved ID for children reference
                $partNoToIdMap[$part['part_no']] = $partId;
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
