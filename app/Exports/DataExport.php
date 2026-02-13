<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DataExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * إرجاع مجموعة البيانات التي سيتم كتابتها في الملف
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * تحديد عناوين الأعمدة في ملف الإكسل
     */
    public function headings(): array
    {
        return [
            'SKU',
            'Quantity',
            'Reason (سبب الرفض)'
        ];
    }
}