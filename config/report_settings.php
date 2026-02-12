<?php

return [
    'missing_products' => [
    'label' => 'تقرير النواقص',
    'columns' => [
        'image'        => 'الصورة',
        'brand'        => 'العلامة التجارية',
        'category'     => 'الصنف (Category)',
        'sub_category' => 'الصنف الفرعي',
        'unit'         => 'الوحدة',
        'product_name' => 'اسم المنتج',
        'sku'          => 'SKU',
         'product_type' => 'نوع المنتج',
        'unit'         => 'الوحدة',
       'sub_category' => 'الصنف الفرعي',
      'status'       => 'الحالة',
        'tax'          => 'نوع الضريبة',
        'status'       => 'الحالة (نشط/غير نشط)',
        'qty_source'   => 'الكمية في المصدر',
        'qty_target'   => 'الكمية في المستهدف',
        // الحقول المخصصة إذا أردت التحكم بها أيضاً
        'custom_field1' => 'حقل مخصص 1',
        'custom_field2' => 'حقل مخصص 2',
        'custom_field3' => 'حقل مخصص 3',
        'custom_field4' => 'حقل مخصص 4',
    ]
],

'daily_sales_grouped' => [
    'label' => 'تقرير المبيعات اليومية المجمل',
    'columns' => [
        'date'              => 'التاريخ',
        'location'          => 'الموقع',
        'number_of_sales'   => 'عدد المبيعات',
        'total_sales'       => 'إجمالي المبيعات',
        'number_of_returns' => 'عدد المرتجعات',
        'total_returns'     => 'إجمالي المرتجعات',
        'net_sales'         => 'صافي المبيعات',
        'total_before_tax'  => 'الإجمالي قبل الضريبة',
        'tax'               => 'الضريبة',
        'tax_type'          => 'نوع الضريبة',
        'return_not_paid'   => 'مرتجع غير مدفوع',
        'action'            => 'الإجراءات (Action)',
    ]
],
];