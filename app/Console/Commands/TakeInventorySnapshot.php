<?php

namespace App\Console\Commands;

use App\Models\DailyInventorySnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TakeInventorySnapshot extends Command
{
    protected $signature   = 'inventory:snapshot {--date= : تاريخ محدد بصيغة Y-m-d (اختياري)}';
    protected $description = 'يحفظ snapshot للمخزون نهاية كل يوم';

    public function handle(): int
    {
        // دعم تاريخ يدوي للاختبار أو التصحيح
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $this->info("📦 بدء حفظ المخزون ليوم: {$date}");

        // حذف snapshot القديم لنفس اليوم إن وجد
        $deleted = DailyInventorySnapshot::where('snapshot_date', $date)->delete();
        if ($deleted) {
            $this->warn("🗑️  تم حذف snapshot قديم لنفس اليوم");
        }

        // جلب المخزون الحالي
       $stocks = DB::table('variation_location_details as pvl')
    ->join('variations as v', 'v.id', '=', 'pvl.variation_id')
    ->join('products as p', 'p.id', '=', 'v.product_id')
    ->select(
        'p.id as product_id',
        'v.id as variation_id',
        'pvl.location_id',
        'p.name as product_name',
        'v.sub_sku as sku',
        'pvl.qty_available'
    )
  //  ->where('p.status', 'active')
    ->get();
            

        if ($stocks->isEmpty()) {
            $this->warn('⚠️  لا يوجد مخزون للحفظ');
            return self::SUCCESS;
        }

        // إدراج على دفعات لأداء أفضل
        $rows = $stocks->map(fn($s) => [
            'snapshot_date' => $date,
            'product_id'    => $s->product_id,
            'variation_id'  => $s->variation_id,
            'location_id'   => $s->location_id,
            'product_name'  => $s->product_name,
            'sku'           => $s->sku,
            'qty_available' => $s->qty_available,
            'created_at'    => now(),
            'updated_at'    => now()
        ])->toArray();

        collect($rows)->chunk(500)->each(
            fn($chunk) => DailyInventorySnapshot::insert($chunk->toArray())
        );

        $this->info("✅ تم بنجاح: " . count($rows) . " منتج محفوظ");

        return self::SUCCESS;
    }
}