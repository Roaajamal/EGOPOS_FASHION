<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DataExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $data;

    /**
     * استلام البيانات المرفوضة من الكنترولر
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * تحويل المصفوفة إلى Collection لكي تفهمها الحزمة
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * تعريف عناوين الأعمدة في ملف الإكسل الناتج
     */
    public function headings(): array
    {
        return [
            'SKU',
            'الكمية المطلوبة',
            'سبب عدم الإضافة'
        ];
    }
}