<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class AdminCollectionExport implements FromArray, WithDrawings, WithEvents, ShouldAutoSize
{
    public function __construct(
        protected array $headings,
        protected Collection $rows,
        protected array $template = [],
        protected array $meta = [],
    ) {
    }

    public function array(): array
    {
        $rows = [];

        if ($this->template !== []) {
            $rows[] = $this->headerRow($this->template['content']['title'] ?? 'Export');
            $rows[] = $this->headerRow($this->template['content']['subtitle'] ?? '');
            $rows[] = $this->headerRow(collect(['Generated' => $this->template['generated_at'] ?? now()->format('M d, Y g:i A')])
                ->merge($this->meta)
                ->map(fn ($value, $label) => $label.': '.$value)
                ->join('   |   '));
            $rows[] = [];
        }

        $rows[] = $this->headings;

        foreach ($this->rows as $row) {
            $rows[] = is_array($row) ? $row : $row->toArray();
        }

        return $rows;
    }

    public function drawings(): Drawing|array
    {
        $logoPath = $this->template['logo_path'] ?? null;

        if (! $logoPath || ! is_file($logoPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName($this->template['project_title'] ?? 'Project Logo');
        $drawing->setDescription($this->template['project_title'] ?? 'Project Logo');
        $drawing->setPath($logoPath);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(8);
        $drawing->setHeight(42);

        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings));
                $sheet = $event->sheet->getDelegate();
                $headingRow = $this->template === [] ? 1 : 5;
                $lastRow = $sheet->getHighestRow();
                $hasLogo = ! empty($this->template['logo_path']) && count($this->headings) > 1;
                $headerStartColumn = $hasLogo ? 'B' : 'A';

                if ($this->template !== []) {
                    foreach ([1, 2, 3] as $row) {
                        if ($headerStartColumn !== $lastColumn) {
                            $sheet->mergeCells("{$headerStartColumn}{$row}:{$lastColumn}{$row}");
                        }
                    }

                    $sheet->getRowDimension(1)->setRowHeight(34);
                    $sheet->getRowDimension(2)->setRowHeight(22);
                    $sheet->getRowDimension(3)->setRowHeight(20);

                    if ($hasLogo) {
                        $sheet->getColumnDimension('A')->setWidth(12);
                    }

                    $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => ltrim($this->template['content']['accent'] ?? '9D3948', '#')],
                        ],
                        'font' => [
                            'bold' => true,
                            'size' => 16,
                            'color' => ['rgb' => ltrim($this->template['content']['title_text'] ?? 'FFFFFF', '#')],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $sheet->getStyle("{$headerStartColumn}1:{$lastColumn}1")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'indent' => $hasLogo ? 1 : 0,
                        ],
                    ]);

                    $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                        'font' => [
                            'italic' => true,
                            'size' => 11,
                            'color' => ['rgb' => ltrim($this->template['content']['heading_text'] ?? '455468', '#')],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => ltrim($this->template['content']['meta_background'] ?? 'F7EEF0', '#')],
                        ],
                        'font' => [
                            'size' => 10,
                            'color' => ['rgb' => ltrim($this->template['content']['meta_text'] ?? '6D4850', '#')],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }

                $sheet->getStyle("A{$headingRow}:{$lastColumn}{$headingRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => ltrim($this->template['content']['heading_background'] ?? 'EEF2F7', '#')],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => ltrim($this->template['content']['heading_text'] ?? '455468', '#')],
                    ],
                ]);

                $sheet->getStyle("A{$headingRow}:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => ltrim($this->template['content']['body_border'] ?? 'D7DCE5', '#')],
                        ],
                    ],
                ]);
            },
        ];
    }

    protected function headerRow(string $value): array
    {
        $columnCount = max(count($this->headings), 1);
        $hasLogo = ! empty($this->template['logo_path']) && $columnCount > 1;
        $row = $hasLogo ? ['', $value] : [$value];

        return array_pad($row, $columnCount, '');
    }
}