<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Contact;
use App\User;

class AccountingVoucher extends Model
{


// داخل كلاس AccountingVoucher

public function getAmountInWordsAttribute()
{
    return $this->tafqeetArabic($this->amount);
}

private function tafqeetArabic($number)
{
    $hyphen      = ' ';
    $conjunction = ' و ';
    $separator   = ' ';
    $negative    = 'سالب ';
    $decimal     = ' فاصلة ';
    $dictionary  = array(
        0                   => 'صفر',
        1                   => 'واحد',
        2                   => 'اثنان',
        3                   => 'ثلاثة',
        4                   => 'أربعة',
        5                   => 'خمسة',
        6                   => 'ستة',
        7                   => 'سبعة',
        8                   => 'ثمانية',
        9                   => 'تسعة',
        10                  => 'عشرة',
        11                  => 'أحد عشر',
        12                  => 'اثنا عشر',
        13                  => 'ثلاثة عشر',
        14                  => 'أربعة عشر',
        15                  => 'خمسة عشر',
        16                  => 'ستة عشر',
        17                  => 'سبعة عشر',
        18                  => 'ثمانية عشر',
        19                  => 'تسعة عشر',
        20                  => 'عشرون',
        30                  => 'ثلاثون',
        40                  => 'أربعون',
        50                  => 'خمسون',
        60                  => 'ستون',
        70                  => 'سبعون',
        80                  => 'ثمانون',
        90                  => 'تسعون',
        100                 => 'مائة',
        200                 => 'مائتان',
        300                 => 'ثلاثمائة',
        400                 => 'أربعمائة',
        500                 => 'خمسمائة',
        600                 => 'ستمائة',
        700                 => 'سبعمائة',
        800                 => 'ثمانمائة',
        900                 => 'تسعمائة',
        1000                => 'ألف',
        2000                => 'ألفين',
        1000000             => 'مليون',
        1000000000          => 'مليار'
    );

    if (!is_numeric($number)) return false;

    if ($number < 0) return $negative . $this->tafqeetArabic(abs($number));

    $string = $fraction = null;
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) $string = $dictionary[$units] . $conjunction . $string;
            break;
        case $number < 1000:
            $hundreds  = ((int) ($number / 100)) * 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds];
            if ($remainder) $string .= $conjunction . $this->tafqeetArabic($remainder);
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = $this->tafqeetArabic($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) $string .= $remainder < 100 ? $conjunction : $separator;
            if ($remainder) $string .= $this->tafqeetArabic($remainder);
            break;
    }

    return $string . " دينار"; // يمكنك تغيير العملة هنا
}
    protected $guarded = ['id'];

    // علاقة السند مع الشخص (عميل/مورد)
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    // علاقة السند مع الحساب المالي (الشجرة)
    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }

    // علاقة السند مع المستخدم الذي أنشأه
    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}