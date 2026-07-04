<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// 🆕 تفعيل النظام للبزنس (الأحدث حسب end_date = التفعيل الحالي)
class EgoActivation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public static function current($business_id)
    {
        return self::where('business_id', $business_id)->orderByDesc('end_date')->orderByDesc('id')->first();
    }

    // الأيام المتبقية حتى الانتهاء — مقارنة بالتاريخ فقط وبتوقيت محلي ثابت (يبقى الاشتراك صالحاً طوال يوم الانتهاء ضمناً)
    public static function daysLeft($business_id)
    {
        $cur = self::current($business_id);
        if (! $cur) {
            return null;
        }
        $tz = 'Asia/Amman'; // نفس التوقيت المستخدم في بقية النظام (لتفادي اختلاف Europe/London)
        $today = \Carbon\Carbon::now($tz)->startOfDay();
        $end = \Carbon\Carbon::parse($cur->end_date)->startOfDay();
        // 0 = ينتهي اليوم (صالح)، موجب = باقٍ، سالب = انتهى
        return (int) $today->diffInDays($end, false);
    }
}
