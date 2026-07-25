<?php

require_once __DIR__ . '/ImporterInterface.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Class ExcelImporter
 * Implements BomImporterInterface for parsing Excel (XLS, XLSX) files.
 */
class ExcelImporter implements BomImporterInterface {
    /**
     * Parse the given Excel file.
     *
     * @param string $filePath
     * @return array Mapped rows containing part details.
     * @throws Exception If loading the sheet fails or required headers are missing.
     */
    public function parse(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("File does not exist or is not readable: " . basename($filePath));
        }

        try {
            // Load spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
        } catch (\Exception $e) {
            throw new Exception("Error loading Excel file: " . $e->getMessage());
        }

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // Find header row (usually row 1)
        $headerRowIndex = 1;
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cellValue = trim((string)$this->getCellByColumnAndRow($sheet, $col, $headerRowIndex)->getValue());
            $headers[$col] = strtolower($cellValue);
        }

        // Map column indices
        $colMap = [
            'find_no' => null,
            'component' => null,
            'qty' => null,
            'unit' => null,
            'ref' => null
        ];

        // Search in headers
        foreach ($headers as $colIndex => $headerName) {
            if ($headerName === '') continue;

            // Header matching logic (lenient to handle minor format variations)
            if (strpos($headerName, 'find') !== false) {
                $colMap['find_no'] = $colIndex;
            } elseif (strpos($headerName, 'component') !== false || strpos($headerName, 'componer') !== false) {
                $colMap['component'] = $colIndex;
            } elseif (strpos($headerName, 'qty') !== false || strpos($headerName, 'quantity') !== false) {
                $colMap['qty'] = $colIndex;
            } elseif (strpos($headerName, 'unit') !== false || strpos($headerName, 'uom') !== false) {
                $colMap['unit'] = $colIndex;
            } elseif (strpos($headerName, 'ref') !== false) {
                $colMap['ref'] = $colIndex;
            }
        }

        // Validate required columns
        if (!$colMap['component']) {
            throw new Exception("Required column 'Component' was not found in the spreadsheet headers. Found headers: " . implode(', ', array_filter($headers)));
        }

        $parsedParts = [];
        $stack = [];

        // Loop rows (starting from row after header)
        for ($row = $headerRowIndex + 1; $row <= $highestRow; $row++) {
            // Get Component cell
            $componentCell = $colMap['component'] ? $this->getCellByColumnAndRow($sheet, $colMap['component'], $row) : null;
            if (!$componentCell) continue;

            // PhpSpreadsheet's getValue returns formula results if pre-calculated, or we can use getFormattedValue
            $componentRaw = (string)$componentCell->getValue();
            $componentClean = trim($componentRaw);

            // Skip empty rows
            if ($componentClean === '') {
                continue;
            }

            // Get find number
            $findNoVal = '';
            if ($colMap['find_no']) {
                $findNoVal = trim((string)$this->getCellByColumnAndRow($sheet, $colMap['find_no'], $row)->getValue());
            }

            // Get qty
            $qtyVal = '1';
            if ($colMap['qty']) {
                $qtyVal = trim((string)$this->getCellByColumnAndRow($sheet, $colMap['qty'], $row)->getValue());
            }
            $requiredQuantity = (float)$qtyVal;

            // Get unit
            $unitVal = 'ea';
            if ($colMap['unit']) {
                $unitVal = trim((string)$this->getCellByColumnAndRow($sheet, $colMap['unit'], $row)->getValue());
            }

            // Get ref
            $refVal = null;
            if ($colMap['ref']) {
                $refVal = trim((string)$this->getCellByColumnAndRow($sheet, $colMap['ref'], $row)->getValue());
                if ($refVal === '') {
                    $refVal = null;
                }
            }

            // Determine hierarchy level by indentation
            $level = 0;
            // 1. Try cell alignment indentation (native Excel alignment format)
            $indentStyle = $componentCell->getStyle()->getAlignment()->getIndent();
            if ($indentStyle > 0) {
                $level = $indentStyle;
            } else {
                // 2. Fallback to space-based indentation (common in text files converted to spreadsheet)
                // Count leading spaces
                $leadingSpaces = strlen($componentRaw) - strlen(ltrim($componentRaw));
                if ($leadingSpaces > 0) {
                    // Standard indentation is typically 2 or 4 spaces per level
                    $level = (int)($leadingSpaces / 2);
                }
            }

            // Generate part number: component name UPPERCASE, spaces to underscore, find number attached with a dash
            // Example: "Battery 4S" + Find No "2" -> BATTERY_4S-2
            $partNoComponent = strtoupper(str_replace(' ', '_', $componentClean));
            $partNo = $partNoComponent;
            if ($findNoVal !== '') {
                $partNo .= '-' . $findNoVal;
            }

            // Determine parent part number from stack
            $parentPartNo = null;
            if ($level > 0 && isset($stack[$level - 1])) {
                $parentPartNo = $stack[$level - 1];
            }

            // Update hierarchy stack
            $stack[$level] = $partNo;
            // Clear deeper nesting levels to prevent cross-contamination from adjacent branches
            $stack = array_slice($stack, 0, $level + 1, true);

            $parsedParts[] = [
                'part_no' => $partNo,
                'name' => $componentClean,
                'required_quantity' => $requiredQuantity,
                'unit' => $unitVal,
                'parent_part_no' => $parentPartNo,
                'ref' => $refVal
            ];
        }

        return $parsedParts;
    }

    /**
     * Helper method to get a cell by column index and row index in a PhpSpreadsheet-compatible way.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $col Column index (1-based)
     * @param int $row Row index (1-based)
     * @return \PhpOffice\PhpSpreadsheet\Cell\Cell
     */
    private function getCellByColumnAndRow($sheet, int $col, int $row) {
        $coordinate = Coordinate::stringFromColumnIndex($col) . $row;
        return $sheet->getCell($coordinate);
    }
}
