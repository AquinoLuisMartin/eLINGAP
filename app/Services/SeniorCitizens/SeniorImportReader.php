<?php

namespace App\Services\SeniorCitizens;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;
use ZipArchive;

class SeniorImportReader
{
    public const MAX_ROWS = 500;

    public const COLUMNS = ['first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'sex', 'civil_status', 'barangay', 'address', 'contact_number', 'osca_id_number', 'representative_name', 'representative_relationship', 'representative_contact'];

    public const REQUIRED = ['first_name', 'last_name', 'birth_date', 'sex', 'barangay', 'address', 'contact_number'];

    public function read(UploadedFile $file): array
    {
        $type = match (strtolower($file->getClientOriginalExtension())) {
            'csv' => 'Csv', 'xlsx' => 'Xlsx', 'xls' => 'Xls',
            default => $this->fail('Choose a CSV, XLSX or XLS file.'),
        };
        if ($file->getSize() > 5 * 1024 * 1024) {
            $this->fail('The import file must not exceed 5 MB.');
        }
        if ($type === 'Xlsx') {
            $this->checkArchive($file->getPathname());
        }
        $book = null;
        $calendar = Date::getExcelCalendar();
        try {
            IOFactory::identify($file->getPathname(), [$type]);
            $reader = IOFactory::createReader($type);
            $reader->setReadDataOnly(false)->setReadEmptyCells(false)->setIncludeCharts(false)->setAllowExternalImages(false);
            $sheets = $reader->listWorksheetInfo($file->getPathname());
            if (count($sheets) !== 1) {
                $this->fail('Use a workbook with one worksheet, or save the intended worksheet as CSV.');
            }
            if ($sheets[0]['totalRows'] > self::MAX_ROWS + 1 || $sheets[0]['totalColumns'] > count(self::COLUMNS)) {
                $this->fail('Use at most 500 records and the template columns. Remove unused rows and columns.');
            }
            $reader->setReadFilter(new class implements IReadFilter
            {
                public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
                {
                    return $row <= SeniorImportReader::MAX_ROWS + 1 && Coordinate::columnIndexFromString($columnAddress) <= count(SeniorImportReader::COLUMNS);
                }
            });
            $book = $reader->load($file->getPathname());
            $sheet = $book->getSheet(0);
            $headers = [];
            for ($column = 1; $column <= $sheets[0]['totalColumns']; $column++) {
                $heading = trim((string) $sheet->getCell([$column, 1])->getValue(), " \t\r\n\xEF\xBB\xBF");
                $heading = Str::slug(mb_strtolower($heading), '_');
                $heading = match ($heading) {
                    'barangay_code', 'barangay_name' => 'barangay',
                    'mobile_number' => 'contact_number', 'osca_id' => 'osca_id_number',
                    default => $heading,
                };
                if (! in_array($heading, self::COLUMNS, true) || in_array($heading, $headers, true)) {
                    $this->fail('Column headers must be unique and match the template. Unsupported columns cannot be imported.');
                }
                $headers[$column] = $heading;
            }
            if (array_diff(self::REQUIRED, $headers)) {
                $this->fail('Missing required columns: '.implode(', ', array_diff(self::REQUIRED, $headers)).'.');
            }
            $rows = [];
            for ($row = 2; $row <= $sheets[0]['totalRows']; $row++) {
                $values = [];
                foreach ($headers as $column => $heading) {
                    $cell = $sheet->getCell([$column, $row]);
                    $value = $cell->getValue();
                    if ($cell->getDataType() === DataType::TYPE_FORMULA || (is_string($value) && str_starts_with(ltrim($value), '='))) {
                        $this->fail("Row {$row} contains a formula. Paste values only before uploading.");
                    }
                    if ($heading === 'birth_date' && $type !== 'Csv' && is_numeric($value)) {
                        $value = Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
                    }
                    if ($heading === 'osca_id_number' && $type !== 'Csv' && is_numeric($value)) {
                        $value = $cell->getFormattedValue();
                    }
                    $values[$heading] = trim((string) ($value ?? ''));
                    if (strlen($values[$heading]) > 8192) {
                        $this->fail("Row {$row} contains an oversized cell. Check the template and field lengths.");
                    }
                    if (! mb_check_encoding($values[$heading], 'UTF-8') || str_contains($values[$heading], "\0")) {
                        $this->fail("Row {$row} contains unsupported text. Save CSV files as UTF-8.");
                    }
                }
                if (count(array_filter($values, fn ($value) => $value !== '')) > 0) {
                    $rows[] = ['row' => $row, 'values' => $values];
                }
            }
            if ($rows === []) {
                $this->fail('The file contains no records. Keep the header and add at least one senior.');
            }

            return $rows;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            $this->fail('The spreadsheet could not be read. Use an unencrypted CSV, XLSX or XLS file containing values only.');
        } finally {
            $book?->disconnectWorksheets();
            Date::setExcelCalendar($calendar);
        }
    }

    private function checkArchive(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            $this->fail('The XLSX file is not a readable Excel workbook.');
        }
        try {
            $size = 0;
            if ($zip->numFiles > 1000) {
                $this->fail('The workbook contains too many entries. Save a simpler workbook or use CSV.');
            }
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                $size += $entry['size'];
                if ($size > 20 * 1024 * 1024 || str_ends_with(strtolower($entry['name']), 'vbaproject.bin')) {
                    $this->fail('The workbook is too large when expanded or contains macros. Save a values-only workbook.');
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
