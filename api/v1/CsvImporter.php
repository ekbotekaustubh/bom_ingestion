<?php

require_once __DIR__ . '/ImporterInterface.php';

/**
 * Class CsvImporter
 * Implements BomImporterInterface for parsing CSV files.
 */
class CsvImporter implements BomImporterInterface {
    /**
     * Parse the given CSV file.
     *
     * @param string $filePath
     * @return array Mapped rows containing part details.
     * @throws Exception If the file cannot be opened or required headers are missing.
     */
    public function parse(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("File does not exist or is not readable: " . basename($filePath));
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new Exception("Unable to open file: " . basename($filePath));
        }

        // Try to detect the delimiter (comma or semicolon)
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new Exception("The uploaded CSV file is empty.");
        }
        
        $delimiter = ',';
        if (strpos($firstLine, ';') !== false && strpos($firstLine, ',') === false) {
            $delimiter = ';';
        }
        
        // Rewind and parse the header row
        rewind($handle);
        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false || empty($headers)) {
            fclose($handle);
            throw new Exception("Failed to parse headers in CSV file.");
        }

        // Normalize headers
        $headers = array_map(function($header) {
            return strtolower(trim($header));
        }, $headers);

        // Define column mappings
        // CSV Column Name => Target key in returned array
        $requiredMappings = [
            'part_no' => 'part_no',
            'description' => 'name',
            'qty' => 'required_quantity',
            'uom' => 'unit'
        ];
        
        $optionalMappings = [
            'parent_part' => 'parent_part_no',
            'ref' => 'ref'
        ];

        // Find the index of each required column
        $colIndexes = [];
        foreach ($requiredMappings as $csvHeader => $dbKey) {
            $index = array_search($csvHeader, $headers);
            if ($index === false) {
                fclose($handle);
                throw new Exception("Required CSV column missing: '{$csvHeader}'. Found headers: " . implode(', ', $headers));
            }
            $colIndexes[$dbKey] = $index;
        }

        // Find optional columns
        foreach ($optionalMappings as $csvHeader => $dbKey) {
            $index = array_search($csvHeader, $headers);
            $colIndexes[$dbKey] = $index; // Will be false if not found
        }

        $parsedParts = [];

        // Parse rows
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Skip empty rows
            if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                continue;
            }

            // Extract fields based on mapped column indexes
            $partNo = isset($row[$colIndexes['part_no']]) ? trim($row[$colIndexes['part_no']]) : '';
            $name = isset($row[$colIndexes['name']]) ? trim($row[$colIndexes['name']]) : '';
            $qtyVal = isset($row[$colIndexes['required_quantity']]) ? trim($row[$colIndexes['required_quantity']]) : '0';
            $unit = isset($row[$colIndexes['unit']]) ? trim($row[$colIndexes['unit']]) : '';

            // Clean quantity (convert to float)
            $requiredQuantity = (float)$qtyVal;

            // Handle parent part (optional field)
            $parentPartNo = null;
            if ($colIndexes['parent_part_no'] !== false && isset($row[$colIndexes['parent_part_no']])) {
                $val = trim($row[$colIndexes['parent_part_no']]);
                if ($val !== '') {
                    $parentPartNo = $val;
                }
            }

            // Handle ref (optional field)
            $ref = null;
            if ($colIndexes['ref'] !== false && isset($row[$colIndexes['ref']])) {
                $val = trim($row[$colIndexes['ref']]);
                if ($val !== '') {
                    $ref = $val;
                }
            }

            // Skip rows without a part number
            if ($partNo === '') {
                continue;
            }

            $parsedParts[] = [
                'part_no' => $partNo,
                'name' => $name,
                'required_quantity' => $requiredQuantity,
                'unit' => $unit,
                'parent_part_no' => $parentPartNo,
                'ref' => $ref
            ];
        }

        fclose($handle);
        return $parsedParts;
    }
}
