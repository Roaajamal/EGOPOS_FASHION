<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\FatoraInvoice;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\BusinessLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportFatoraService
{
    protected $businessId;
    protected $currentUser;
    
    public function __construct($businessId = null, $userId = null)
    {
        $this->businessId = $businessId;
        $this->currentUser = $userId;
        $this->business_id = $businessId;
    }
    
    /**
     * الحصول على إحصائيات الفواتير
     */
    public function getFatoraStats(array $filters = []): array
    {
        try {
            $query = Transaction::query()
                ->where('transactions.business_id', $this->businessId)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final');
            
            // تطبيق الفلاتر
            $this->applyFilters($query, $filters);
            
            // الحصول على الإحصائيات
            $stats = $query->leftJoin('fatora_invoices', 'transactions.id', '=', 'fatora_invoices.transaction_id')
                ->select([
                    DB::raw("COUNT(*) as total_invoices"),
                    DB::raw("SUM(CASE WHEN fatora_invoices.status = 'sent' THEN 1 ELSE 0 END) as sent_count"),
                    DB::raw("SUM(CASE WHEN fatora_invoices.status = 'failed' THEN 1 ELSE 0 END) as failed_count"),
                    DB::raw("SUM(CASE WHEN fatora_invoices.status IS NULL THEN 1 ELSE 0 END) as not_sent_count"),
                    DB::raw("SUM(CASE WHEN fatora_invoices.is_credit_invoice = 1 THEN 1 ELSE 0 END) as credit_invoices_count"),
                    DB::raw("SUM(CASE WHEN transactions.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                    DB::raw("SUM(CASE WHEN transactions.payment_status = 'due' THEN 1 ELSE 0 END) as due_count"),
                    DB::raw("SUM(CASE WHEN transactions.payment_status = 'partial' THEN 1 ELSE 0 END) as partial_count"),
                    DB::raw("SUM(transactions.final_total) as total_amount"),
                    DB::raw("SUM(transactions.tax_amount) as total_tax_amount")
                ])
                ->first();
            
            return [
                'success' => true,
                'data' => $stats ?? $this->getEmptyStats()
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getFatoraStats: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب الإحصائيات',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
    
    /**
     * الحصول على إحصائيات الضرائب
     */
    public function getTaxStats(array $filters = []): array
    {
        try {
            $query = Transaction::query()
                ->where('business_id', $this->businessId)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereNotNull('tax_amount')
                ->where('tax_amount', '>', 0);
            
            // تطبيق الفلاتر
            $this->applyFilters($query, $filters);
            
            $stats = $query->select([
                    DB::raw("COUNT(*) as total_tax_invoices"),
                    DB::raw("SUM(tax_amount) as total_tax_collected"),
                    DB::raw("SUM(final_total) as total_sales_amount"),
                    DB::raw("SUM(total_before_tax) as total_before_tax"),
                    DB::raw("AVG(tax_amount) as average_tax_amount"),
                    DB::raw("MAX(tax_amount) as max_tax_amount"),
                    DB::raw("MIN(tax_amount) as min_tax_amount")
                ])
                ->first();
            
            return [
                'success' => true,
                'data' => $stats ?? $this->getEmptyTaxStats()
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getTaxStats: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب إحصائيات الضرائب',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
    
    /**
     * الحصول على الإحصائيات الشهرية
     */
    public function getMonthlyStats(int $months = 6): array
    {
        try {
            $stats = [];
            $currentDate = Carbon::now();
            
            for ($i = $months - 1; $i >= 0; $i--) {
                $date = $currentDate->copy()->subMonths($i);
                $monthName = $date->translatedFormat('M');
                $yearMonth = $date->format('Y-m');
                
                $monthStats = Transaction::where('transactions.business_id', $this->businessId)
                    ->where('transactions.type', 'sell')
                    ->where('transactions.status', 'final')
                    ->whereYear('transactions.transaction_date', $date->year)
                    ->whereMonth('transactions.transaction_date', $date->month)
                    ->leftJoin('fatora_invoices', 'transactions.id', '=', 'fatora_invoices.transaction_id')
                    ->select([
                        DB::raw("COUNT(*) as total"),
                        DB::raw("SUM(CASE WHEN fatora_invoices.status = 'sent' THEN 1 ELSE 0 END) as sent"),
                        DB::raw("SUM(CASE WHEN fatora_invoices.status = 'failed' THEN 1 ELSE 0 END) as failed"),
                        DB::raw("SUM(transactions.final_total) as total_amount"),
                        DB::raw("SUM(transactions.tax_amount) as total_tax"),
                        DB::raw("AVG(transactions.tax_amount) as avg_tax")
                    ])
                    ->first();
                
                $stats[$monthName] = [
                    'total' => $monthStats->total ?? 0,
                    'sent' => $monthStats->sent ?? 0,
                    'failed' => $monthStats->failed ?? 0,
                    'total_amount' => $monthStats->total_amount ?? 0,
                    'total_tax' => $monthStats->total_tax ?? 0,
                    'avg_tax' => $monthStats->avg_tax ?? 0,
                    'month' => $yearMonth,
                    'year' => $date->year,
                    'month_number' => $date->month
                ];
            }
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getMonthlyStats: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب الإحصائيات الشهرية'
            ];
        }
    }
    
    /**
     * الحصول على إحصائيات اليوم
     */
    public function getDailyStats(): array
    {
        try {
            $stats = Transaction::where('business_id', $this->businessId)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereDate('transaction_date', Carbon::today())
                ->leftJoin('fatora_invoices', 'transactions.id', '=', 'fatora_invoices.transaction_id')
                ->select([
                    DB::raw("COUNT(*) as today_total"),
                    DB::raw("SUM(CASE WHEN fatora_invoices.status = 'sent' THEN 1 ELSE 0 END) as today_sent"),
                    DB::raw("SUM(CASE WHEN fatora_invoices.status = 'failed' THEN 1 ELSE 0 END) as today_failed"),
                    DB::raw("SUM(transactions.final_total) as today_amount"),
                    DB::raw("SUM(transactions.tax_amount) as today_tax"),
                    DB::raw("AVG(transactions.tax_amount) as today_avg_tax")
                ])
                ->first();
            
            return [
                'success' => true,
                'data' => $stats ?? $this->getEmptyDailyStats()
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getDailyStats: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب إحصائيات اليوم'
            ];
        }
    }
    
    /**
     * الحصول على إحصائيات المواقع
     */
    public function getLocationStats(): array
    {
        try {
            $stats = Transaction::where('transactions.business_id', $this->businessId)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->join('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->groupBy('business_locations.id', 'business_locations.name')
                ->select([
                    'business_locations.id',
                    'business_locations.name',
                    DB::raw("COUNT(*) as invoice_count"),
                    DB::raw("SUM(transactions.final_total) as total_amount"),
                    DB::raw("SUM(transactions.tax_amount) as total_tax"),
                    DB::raw("AVG(transactions.tax_amount) as avg_tax"),
                    DB::raw("SUM(CASE WHEN fatora_invoices.status = 'sent' THEN 1 ELSE 0 END) as sent_count"),
                    DB::raw("SUM(CASE WHEN fatora_invoices.status = 'failed' THEN 1 ELSE 0 END) as failed_count")
                ])
                ->leftJoin('fatora_invoices', 'transactions.id', '=', 'fatora_invoices.transaction_id')
                ->orderBy('invoice_count', 'desc')
                ->get();
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getLocationStats: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب إحصائيات المواقع'
            ];
        }
    }
    
    /**
     * الحصول على إحصائيات العملاء
     */
    public function getCustomerStats(int $limit = 10): array
    {
        try {
            $stats = Transaction::where('transactions.business_id', $this->businessId)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->join('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->groupBy('contacts.id', 'contacts.name', 'contacts.supplier_business_name')
                ->select([
                    'contacts.id',
                    'contacts.name',
                    'contacts.supplier_business_name',
                    DB::raw("COUNT(*) as invoice_count"),
                    DB::raw("SUM(transactions.final_total) as total_amount"),
                    DB::raw("SUM(transactions.tax_amount) as total_tax"),
                    DB::raw("AVG(transactions.tax_amount) as avg_tax"),
                    DB::raw("MAX(transactions.final_total) as max_invoice"),
                    DB::raw("MIN(transactions.final_total) as min_invoice")
                ])
                ->orderBy('total_amount', 'desc')
                ->limit($limit)
                ->get();
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getCustomerStats: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب إحصائيات العملاء'
            ];
        }
    }
    
    /**
     * الحصول على توزيع نسب الضرائب
     */
    public function getTaxDistribution(): array
    {
        try {
            $distribution = Transaction::where('business_id', $this->businessId)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereNotNull('tax_amount')
                ->where('tax_amount', '>', 0)
                ->select([
                    DB::raw("COUNT(*) as count"),
                    DB::raw("CASE 
                        WHEN tax_amount < 10 THEN 'أقل من 10'
                        WHEN tax_amount BETWEEN 10 AND 50 THEN '10-50'
                        WHEN tax_amount BETWEEN 51 AND 100 THEN '51-100'
                        WHEN tax_amount BETWEEN 101 AND 500 THEN '101-500'
                        WHEN tax_amount BETWEEN 501 AND 1000 THEN '501-1000'
                        ELSE 'أكثر من 1000'
                    END as range"),
                    DB::raw("SUM(tax_amount) as total_tax"),
                    DB::raw("AVG(tax_amount) as avg_tax")
                ])
                ->groupBy('range')
                ->orderByRaw("
                    CASE range
                        WHEN 'أقل من 10' THEN 1
                        WHEN '10-50' THEN 2
                        WHEN '51-100' THEN 3
                        WHEN '101-500' THEN 4
                        WHEN '501-1000' THEN 5
                        ELSE 6
                    END
                ")
                ->get();
            
            return [
                'success' => true,
                'data' => $distribution
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getTaxDistribution: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب توزيع الضرائب'
            ];
        }
    }
    
    /**
     * الحصول على أداء الإرسال
     */
    public function getSendingPerformance(): array
    {
        try {
            $performance = FatoraInvoice::where('business_id', $this->businessId)
                ->whereNotNull('sent_at')
                ->select([
                    DB::raw("DATE(sent_at) as date"),
                    DB::raw("COUNT(*) as total_sent"),
                    DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as successful"),
                    DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"),
                    DB::raw("AVG(TIMESTAMPDIFF(MINUTE, created_at, sent_at)) as avg_processing_time")
                ])
                ->groupBy(DB::raw("DATE(sent_at)"))
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();
            
            return [
                'success' => true,
                'data' => $performance
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getSendingPerformance: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب أداء الإرسال'
            ];
        }
    }
    
    /**
     * توليد تقرير مفصل
     */
    public function generateDetailedReport(array $filters = []): array
    {
        try {
            $report = [
                'summary' => $this->getFatoraStats($filters)['data'],
                'tax_summary' => $this->getTaxStats($filters)['data'],
                'monthly_stats' => $this->getMonthlyStats(),
                'daily_stats' => $this->getDailyStats()['data'],
                'location_stats' => $this->getLocationStats()['data'],
                'top_customers' => $this->getCustomerStats(5)['data'],
                'tax_distribution' => $this->getTaxDistribution()['data'],
                'sending_performance' => $this->getSendingPerformance()['data'],
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'business_id' => $this->businessId,
                'filters_applied' => $filters
            ];
            
            return [
                'success' => true,
                'data' => $report
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - generateDetailedReport: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء توليد التقرير'
            ];
        }
    }
    
    /**
     * تصدير البيانات إلى صيغ مختلفة
     */
    public function exportData(string $format, array $filters = [], string $type = 'tax'): array
    {
        try {
            $query = Transaction::query()
                ->with(['contact', 'location', 'fatora_invoice'])
                ->where('transactions.business_id', $this->businessId)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final');
            
            if ($type === 'tax') {
                $query->whereNotNull('transactions.tax_amount')
                      ->where('transactions.tax_amount', '>', 0);
            } elseif ($type === 'fatora') {
                $query->whereHas('fatora_invoice');
            }
            
            // تطبيق الفلاتر
            $this->applyFilters($query, $filters);
            
            $data = $query->get();
            
            // تحضير البيانات للتصدير
            $exportData = $this->prepareExportData($data, $type);
            
            // إنشاء الملف حسب الصيغة
            $filename = $this->generateExportFile($exportData, $format, $type);
            
            return [
                'success' => true,
                'filename' => $filename,
                'count' => $data->count(),
                'download_url' => url('/storage/exports/' . $filename)
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - exportData: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء التصدير'
            ];
        }
    }
    
    /**
     * الحصول على بيانات لمخططات الداشبورد
     */
    public function getDashboardChartsData(): array
    {
        try {
            $chartsData = [
                'monthly_invoices' => $this->getMonthlyInvoicesChartData(),
                'tax_collection' => $this->getTaxCollectionChartData(),
                'status_distribution' => $this->getStatusDistributionChartData(),
                'top_locations' => $this->getTopLocationsChartData(),
                'sending_trend' => $this->getSendingTrendChartData()
            ];
            
            return [
                'success' => true,
                'data' => $chartsData
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getDashboardChartsData: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل بيانات المخططات'
            ];
        }
    }
    
    /**
     * الحصول على تحليلات متقدمة
     */
    public function getAdvancedAnalytics(): array
    {
        try {
            $analytics = [
                'growth_rate' => $this->calculateGrowthRate(),
                'seasonality' => $this->analyzeSeasonality(),
                'predictions' => $this->generatePredictions(),
                'anomalies' => $this->detectAnomalies(),
                'efficiency_metrics' => $this->calculateEfficiencyMetrics()
            ];
            
            return [
                'success' => true,
                'data' => $analytics
            ];
            
        } catch (\Exception $e) {
            Log::error('ReportFatoraService Error - getAdvancedAnalytics: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب التحليلات المتقدمة'
            ];
        }
    }
    
    /**
     * دوال مساعدة خاصة بالـ Service
     */
    private function applyFilters($query, array $filters): void
    {
        // فلترة حسب التاريخ
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            $endDate = Carbon::parse($filters['end_date'])->endOfDay();
            $query->whereBetween('transactions.transaction_date', [$startDate, $endDate]);
        }
        
        // فلترة حسب الموقع
        if (isset($filters['location_id']) && $filters['location_id']) {
            $query->where('transactions.location_id', $filters['location_id']);
        }
        
        // فلترة حسب حالة الدفع
        if (isset($filters['payment_status']) && $filters['payment_status']) {
            $query->where('transactions.payment_status', $filters['payment_status']);
        }
        
        // فلترة حسب حالة الفوترة
        if (isset($filters['fatora_status']) && $filters['fatora_status']) {
            if ($filters['fatora_status'] == 'sent') {
                $query->whereHas('fatora_invoice', function($q) {
                    $q->where('status', 'sent');
                });
            } elseif ($filters['fatora_status'] == 'failed') {
                $query->whereHas('fatora_invoice', function($q) {
                    $q->where('status', 'failed');
                });
            } elseif ($filters['fatora_status'] == 'not_sent') {
                $query->whereDoesntHave('fatora_invoice');
            }
        }
        
        // فلترة حسب العميل
        if (isset($filters['customer_id']) && $filters['customer_id']) {
            $query->where('transactions.contact_id', $filters['customer_id']);
        }
    }
    
    private function prepareExportData(Collection $data, string $type): array
    {
        return $data->map(function ($item) use ($type) {
            $row = [
                'رقم الفاتورة' => $item->invoice_no,
                'التاريخ' => $item->transaction_date->format('d/m/Y H:i'),
                'العميل' => $item->contact ? $item->contact->name : 'عميل نقدي',
                'اسم العميل التجاري' => $item->contact ? ($item->contact->supplier_business_name ?? '-') : '-',
                'الموقع' => $item->location ? $item->location->name : '-',
                'الإجمالي' => number_format($item->final_total, 2),
                'قبل الضريبة' => number_format($item->total_before_tax, 2),
                'مبلغ الضريبة' => number_format($item->tax_amount, 2),
                'نسبة الضريبة' => $item->total_before_tax > 0 ? 
                    number_format(($item->tax_amount / $item->total_before_tax) * 100, 2) . '%' : '0%',
                'حالة الدفع' => $this->translatePaymentStatus($item->payment_status),
                'طريقة الدفع' => $item->payment_status
            ];
            
            if ($type === 'fatora' && $item->fatora_invoice) {
                $row['رقم النظام'] = $item->fatora_invoice->system_invoice_number ?? '-';
                $row['حالة الفوترة'] = $this->translateFatoraStatus($item->fatora_invoice->status);
                $row['تاريخ الإرسال'] = $item->fatora_invoice->sent_at ? 
                    $item->fatora_invoice->sent_at->format('d/m/Y H:i') : '-';
                $row['رقم المرجع'] = $item->fatora_invoice->invoice_uuid ?? '-';
                $row['نوع الفاتورة'] = $item->fatora_invoice->is_credit_invoice ? 'إرجاع' : 'عادية';
            }
            
            return $row;
        })->toArray();
    }
    
    private function generateExportFile(array $data, string $format, string $type): string
    {
        $timestamp = now()->format('Ymd_His');
        $filename = "{$type}_report_{$timestamp}.{$format}";
        
        // هنا يتم إنشاء الملف حسب الصيغة
        // يمكن استخدام مكتبات مثل PhpSpreadsheet أو FastExcel
        
        return $filename;
    }
    
    private function getMonthlyInvoicesChartData(): array
    {
        $monthly = $this->getMonthlyStats(6)['data'];
        
        return [
            'labels' => array_keys($monthly),
            'datasets' => [
                [
                    'label' => 'إجمالي الفواتير',
                    'data' => array_column($monthly, 'total'),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)'
                ],
                [
                    'label' => 'المرسلة',
                    'data' => array_column($monthly, 'sent'),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)'
                ]
            ]
        ];
    }
    
    private function getTaxCollectionChartData(): array
    {
        $monthly = $this->getMonthlyStats(6)['data'];
        
        return [
            'labels' => array_keys($monthly),
            'datasets' => [
                [
                    'label' => 'الضرائب المحصلة',
                    'data' => array_column($monthly, 'total_tax'),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)'
                ]
            ]
        ];
    }
    
    private function getStatusDistributionChartData(): array
    {
        $stats = $this->getFatoraStats([])['data'];
        
        return [
            'labels' => ['مرسلة', 'غير مرسلة', 'فاشلة'],
            'datasets' => [
                [
                    'data' => [
                        $stats->sent_count,
                        $stats->not_sent_count,
                        $stats->failed_count
                    ],
                    'backgroundColor' => [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ]
                ]
            ]
        ];
    }
    
    private function getTopLocationsChartData(): array
    {
        $locations = $this->getLocationStats()['data']->take(5);
        
        return [
            'labels' => $locations->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'عدد الفواتير',
                    'data' => $locations->pluck('invoice_count')->toArray(),
                    'backgroundColor' => 'rgba(153, 102, 255, 0.6)'
                ]
            ]
        ];
    }
    
    private function getSendingTrendChartData(): array
    {
        $performance = $this->getSendingPerformance()['data']->take(15);
        
        return [
            'labels' => $performance->pluck('date')->toArray(),
            'datasets' => [
                [
                    'label' => 'ناجحة',
                    'data' => $performance->pluck('successful')->toArray(),
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'fill' => false
                ],
                [
                    'label' => 'فاشلة',
                    'data' => $performance->pluck('failed')->toArray(),
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'fill' => false
                ]
            ]
        ];
    }
    
    private function calculateGrowthRate(): array
    {
        $monthly = $this->getMonthlyStats(12)['data'];
        $months = array_values($monthly);
        
        if (count($months) < 2) {
            return ['rate' => 0, 'trend' => 'stable'];
        }
        
        $current = $months[count($months)-1]['total'] ?? 0;
        $previous = $months[count($months)-2]['total'] ?? 0;
        
        $growthRate = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        
        return [
            'rate' => round($growthRate, 2),
            'trend' => $growthRate > 0 ? 'up' : ($growthRate < 0 ? 'down' : 'stable'),
            'current' => $current,
            'previous' => $previous
        ];
    }
    
    private function analyzeSeasonality(): array
    {
        // تحليل الموسمية بناءً على بيانات 12 شهر
        return [
            'has_seasonality' => false,
            'peak_months' => [],
            'low_months' => [],
            'seasonal_pattern' => 'غير محدد'
        ];
    }
    
    private function generatePredictions(): array
    {
        // توليد تنبؤات للشهور القادمة
        return [
            'next_month_invoices' => 0,
            'next_month_tax' => 0,
            'confidence_level' => 'منخفض'
        ];
    }
    
    private function detectAnomalies(): array
    {
        // اكتشاف الحالات الشاذة
        return [
            'has_anomalies' => false,
            'anomalies' => []
        ];
    }
    
    private function calculateEfficiencyMetrics(): array
    {
        $stats = $this->getFatoraStats([])['data'];
        
        $totalInvoices = $stats->total_invoices;
        $sentInvoices = $stats->sent_count;
        
        $sendingEfficiency = $totalInvoices > 0 ? ($sentInvoices / $totalInvoices) * 100 : 0;
        
        return [
            'sending_efficiency' => round($sendingEfficiency, 2),
            'success_rate' => $sentInvoices > 0 ? 100 : 0, // نسبة نجاح الإرسال
            'avg_processing_time' => 0, // متوسط وقت المعالجة
            'compliance_rate' => round($sendingEfficiency, 2) // نسبة الالتزام
        ];
    }
    
    private function translatePaymentStatus(string $status): string
    {
        $translations = [
            'paid' => 'مدفوعة',
            'due' => 'مستحقة',
            'partial' => 'جزئية',
            'pending' => 'قيد الانتظار'
        ];
        
        return $translations[$status] ?? $status;
    }
    
    private function translateFatoraStatus(string $status): string
    {
        $translations = [
            'sent' => 'مرسلة',
            'failed' => 'فاشلة',
            'pending' => 'قيد الانتظار',
            'processing' => 'قيد المعالجة'
        ];
        
        return $translations[$status] ?? $status;
    }
    
    private function getEmptyStats(): object
    {
        return (object) [
            'total_invoices' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'not_sent_count' => 0,
            'credit_invoices_count' => 0,
            'paid_count' => 0,
            'due_count' => 0,
            'partial_count' => 0,
            'total_amount' => 0,
            'total_tax_amount' => 0
        ];
    }
    
    private function getEmptyTaxStats(): object
    {
        return (object) [
            'total_tax_invoices' => 0,
            'total_tax_collected' => 0,
            'total_sales_amount' => 0,
            'total_before_tax' => 0,
            'average_tax_amount' => 0,
            'max_tax_amount' => 0,
            'min_tax_amount' => 0
        ];
    }
    
    private function getEmptyDailyStats(): object
    {
        return (object) [
            'today_total' => 0,
            'today_sent' => 0,
            'today_failed' => 0,
            'today_amount' => 0,
            'today_tax' => 0,
            'today_avg_tax' => 0
        ];
    }
}