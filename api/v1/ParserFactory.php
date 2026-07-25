<?php

require_once __DIR__ . '/ImporterInterface.php';
require_once __DIR__ . '/CsvImporter.php';
require_once __DIR__ . '/ExcelImporter.php';
require_once __DIR__ . '/JsonImporter.php';

/**
 * Class ParserFactory
 * Responsible for instantiating the correct importer based on the file extension.
 */
class ParserFactory {
    /**
     * Factory method to create and return the appropriate importer class.
     *
     * @param string $extension The file extension (e.g., csv, json, xls, xlsx)
     * @return BomImporterInterface
     * @throws Exception If the file extension is not supported.
     */
    public static function create(string $extension): BomImporterInterface {
        $extension = strtolower(trim($extension));

        switch ($extension) {
            case 'csv':
                return new CsvImporter();
            case 'json':
                return new JsonImporter();
            case 'xls':
            case 'xlsx':
                return new ExcelImporter();
            default:
                throw new Exception("Unsupported file extension: .{$extension}");
        }
    }
}
