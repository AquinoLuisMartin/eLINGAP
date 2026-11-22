<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StaffReportSpreadsheet
{
    public function write(array $data): void
    {
        $book = new Spreadsheet;
        try {
            $book->getDefaultStyle()->getFont()->setName('Inter')->setSize(11)->getColor()->setRGB('000000');
            $records = $book->getActiveSheet()->setTitle('Records');
            $this->row($records, 1, $data['headings']);
            $line = 2;
            foreach ($data['records'] as $record) {
                $this->row($records, $line++, $data['report']->row($data['type'], $record));
            }
            $records->freezePane('A2');
            $records->setAutoFilter([1, 1, count($data['headings']), max(1, $line - 1)]);
            $headingRange = 'A1:'.$records->getHighestColumn().'1';
            $records->getStyle($headingRange)->getFont()->setBold(true);
            $records->getStyle($headingRange)->getFill()->setFillType('solid')->getStartColor()->setRGB('EEEEEE');
            foreach ($data['headings'] as $index => $heading) {
                $records->getColumnDimensionByColumn($index + 1)->setWidth(match ($heading) {
                    'Age', 'Suffix' => 9, 'Program' => 35, default => 24,
                });
            }
            if ($data['headings'][0] === 'OSCA ID') {
                $records->getStyle('A2:A'.max(2, $line - 1))->getNumberFormat()->setFormatCode('@');
            }

            $details = $book->createSheet()->setTitle('Report details');
            $line = 1;
            foreach (['Report' => $data['title'], 'Generated at' => $data['generatedAt']->format('Y-m-d H:i:s e'), 'Matching records' => $records->getHighestDataRow() - 1, ...$data['filterLabels'], ...($data['summary'] ?? [])] as $label => $value) {
                $this->row($details, $line++, [$label, $value]);
            }
            $this->row($details, ++$line, ['Population scope', 'Verified seniors across all barangays; excludes deceased, archived and transferred records.']);
            $this->row($details, ++$line, ['Barangay', 'Verified senior population']);
            $details->getStyle('A'.$line.':B'.$line)->getFont()->setBold(true);
            foreach ($data['population'] as $barangay) {
                $this->row($details, ++$line, [$barangay->name, (int) $barangay->population]);
            }
            $this->row($details, ++$line, ['Total verified senior population', (int) $data['population']->sum('population')]);
            $details->getStyle('A'.$line.':B'.$line)->getFont()->setBold(true);
            $details->getColumnDimension('A')->setWidth(35);
            $details->getColumnDimension('B')->setWidth(90);
            $details->getStyle('A1:B'.$line)->getAlignment()->setWrapText(true);
            $details->freezePane('A2');
            $book->setActiveSheetIndex(0);
            (new Xlsx($book))->save('php://output');
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private function row(Worksheet $sheet, int $line, array $values): void
    {
        foreach ($values as $index => $value) {
            $sheet->setCellValueExplicit([$index + 1, $line], $value, is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
        }
    }
}
