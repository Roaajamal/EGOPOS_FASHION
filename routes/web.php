<?php
use App\Http\Controllers\FAWJOFatoraController;
use App\Http\Controllers\FatoraController;

use App\Http\Controllers\BarcodeDesignController;
use App\Http\Controllers\QuantityEntryController;



use App\Http\Controllers\PrintBarcodeController;
use App\Http\Controllers\EgoReportController;
Route::middleware(['auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])
    ->prefix('print-barcode')
    ->name('barcode.')
    ->group(function () {
        Route::get('/', [PrintBarcodeController::class, 'index'])->name('print.page');
        Route::get('/search', [PrintBarcodeController::class, 'search'])->name('search');
        Route::get('/design', [PrintBarcodeController::class, 'getDesign'])->name('design.get');
        Route::get('/product-variations/{product_id}', [PrintBarcodeController::class, 'getProductVariations'])->name('product-variations');
        Route::post('/print-preview', [PrintBarcodeController::class, 'printPreview'])->name('print-preview');
        Route::post('/print', [PrintBarcodeController::class, 'printBarcodes'])->name('print');
        Route::post('/print-send', [PrintBarcodeController::class, 'sendToPrinter'])->name('print-send');
    });
        use App\Http\Controllers\EcrIntegrationController;
use App\Http\Controllers\MpsPaymentController;
Route::get('/direct-payment/{amount}', [MpsPaymentController::class, 'directPayment'])
    ->where('amount', '^\d+(\.\d{1,2})?$') // تأكد من أن المبلغ رقم
    ->name('mps.direct-payment');

// الطريق الجديد (V2) - بديل أكثر أماناً
// قم بتغيير المسار من /direct-payment إلى /direct-payment-v2
Route::post('/direct-payment', [MpsPaymentController::class, 'directPaymentV2'])
    ->name('mps.direct-payment-v2');


use App\Http\Controllers\QzController;

Route::get('/qz/sign-message', [QzController::class, 'sign'])->name('qz.sign');
 
use App\Http\Controllers\EgoInvoiceController;
Route::middleware(['auth'])->group(function () {

Route::group(['middleware' => ['auth']], function () {

    // --- رووتات الإعدادات (Settings) ---
    // لجلب الإعدادات عند فتح المودال
    Route::get('/ecr/get-settings/{location_id}', [EcrIntegrationController::class, 'getSettings'])
         ->name('ecr.get_settings');

    // لحفظ أو تحديث الإعدادات
    Route::post('/ecr/save-settings/{location_id}', [EcrIntegrationController::class, 'saveSettings'])
         ->name('ecr.save_settings');


    // --- رووتات الدفع (POS Payment) ---
    // لمعالجة عملية الدفع الفعلية من شاشة الـ POS
    Route::post('/ecr/process-payment', [EcrIntegrationController::class, 'processMpsPayment'])
         ->name('ecr.process_payment');

});


// routes لـ ECR Integration
Route::group(['middleware' => ['auth', 'SetSessionData', 'language', 'timezone']], function () {
    // حفظ إعدادات MPS
    Route::post('/ecr-integration/{location_id}/save', [EcrIntegrationController::class, 'saveSettings'])
        ->name('ecr-integration.save');
    
    // جلب إعدادات MPS
    Route::get('/ecr-integration/{location_id}/get', [EcrIntegrationController::class, 'getSettings'])
        ->name('ecr-integration.get');
    
    // اختبار اتصال MPS
    Route::post('/ecr-integration/test-connection', [EcrIntegrationController::class, 'testConnection'])
        ->name('ecr-integration.test');
    Route::post('/mps/check-status', [EcrIntegrationController::class, 'checkMpsStatus'])
    ->middleware('auth');


    // حذف إعدادات MPS
    Route::delete('/ecr-integration/{location_id}/delete', [EcrIntegrationController::class, 'deleteSettings'])
        ->name('ecr-integration.delete');
});
Route::middleware(['auth'])->group(function () {

    // صفحة كشف الفواتير
    Route::get(
        '/invoice-statement',
        [App\Http\Controllers\EgoInvoiceController::class, 'index']
    )->name('reports.invoice_statement');

});


    // صفحة كشف الفواتير
    Route::get(
        '/invoice-statement',
        [App\Http\Controllers\EgoInvoiceController::class, 'index']
    )->name('reports.invoice_statement');

});


// الطريق الجديد (V2) - بديل أكثر أماناً
// قم بتغيير المسار من /direct-payment إلى /direct-payment-v2

Route::group(['middleware' => ['auth', 'language', 'timezone'], 'prefix' => 'report'], function () {
    Route::get('/invoice-statement', [EgoInvoiceController::class, 'index'])
        ->name('invoice.statement');
    
    Route::get('/invoice-statement/export-pdf', [EgoInvoiceController::class, 'exportToPdf'])
        ->name('invoice.statement.export.pdf');
    
    Route::get('/invoice-statement/export-excel', [EgoInvoiceController::class, 'exportToExcel'])
        ->name('invoice.statement.export.excel');
});

///////////// quantity entry routes 001
Route::group([
    'middleware' => ['auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu']
], function () {

    Route::prefix('quantity-entry')->group(function () {

        // صفحة الإدخال
        Route::get('/', [QuantityEntryController::class, 'index'])
            ->name('quantity_entry.index');

        // البحث عن المنتجات
        Route::get('/get-products', [QuantityEntryController::class, 'getProducts'])
            ->name('quantity_entry.getProducts');

        // إضافة سطر منتج للجدول
        Route::get('/get-entry-row', [QuantityEntryController::class, 'getPurchaseEntryRow'])
            ->name('quantity_entry.getEntryRow');

        // حفظ الكميات
        Route::post('/purchases', [QuantityEntryController::class, 'store'])->name('purchases.store');

        Route::post('/import',[QuantityEntryController::class, 'import'])->name('quantity_entry.import');
        Route::post('/update-stock', [QuantityEntryController::class, 'updateProductStock']);



    });

});

//////////////////////// quantity entry routes 001

// Routes مصمم الباركود
Route::middleware(['auth'])->group(function () {
    
    // صفحة المصمم الرئيسية
    Route::get('/barcode-design', [App\Http\Controllers\BarcodeDesignController::class, 'index'])
         ->name('barcode.design');
    
    // APIs للحفظ والتحميل
    Route::post('/barcode-save', [App\Http\Controllers\BarcodeDesignController::class, 'saveDesign'])
         ->name('barcode.save');
    
    Route::get('/barcode-load', [App\Http\Controllers\BarcodeDesignController::class, 'loadDesign'])
         ->name('barcode.load');
    
    Route::delete('/barcode-delete', [App\Http\Controllers\BarcodeDesignController::class, 'deleteDesign'])
         ->name('barcode.delete');
    
    // اختبار الاتصال
    Route::get('/barcode-test', [App\Http\Controllers\BarcodeDesignController::class, 'testConnection'])
         ->name('barcode.test');

    // بحث منتجات وعرض توليفات اللون/المقاس (مجموعة حسب اللون)
    Route::get('/barcode-design/products-search', [App\Http\Controllers\BarcodeDesignController::class, 'searchProducts'])
         ->name('barcode.design.products_search');
    Route::get('/barcode-design/product-variations/{product_id}', [App\Http\Controllers\BarcodeDesignController::class, 'getProductVariations'])
         ->name('barcode.design.product_variations');

});
Route::get('/pos/get-product-by-barcode', [PosController::class, 'getProductByBarcode'])->name('pos.get-product-by-barcode');
Route::get('/invoice/print-direct/{id}', [InvoiceController::class, 'printDirect'])
    ->name('invoice.print.direct');


// داخل group middleware

// ... الروتات الموجودة ...

// ============================================
// 🎯 روتات عروض المنتجات (Product Offers)
// ============================================

Route::group(['middleware' => ['web', 'auth', 'language']], function () {
    
    // 1. الصفحة الرئيسية
    Route::get('product-offers', [\App\Http\Controllers\ProductOfferController::class, 'index'])
        ->name('product-offers.index');
    
    // 2. جلب البيانات للجدول (AJAX)
    Route::get('product-offers/get-data', [\App\Http\Controllers\ProductOfferController::class, 'getOffersData'])
        ->name('product-offers.get-data');
    
    // 3. إضافة عرض جديد
    Route::post('product-offers', [\App\Http\Controllers\ProductOfferController::class, 'store'])
        ->name('product-offers.store');
    
    // 4. استيراد من إكسل
    Route::post('product-offers/import-excel', [\App\Http\Controllers\ProductOfferController::class, 'importExcel'])
        ->name('product-offers.import-excel');
    
    // 5. تحميل قالب إكسل
    Route::get('product-offers/download-template', [\App\Http\Controllers\ProductOfferController::class, 'downloadTemplate'])
        ->name('product-offers.download-template');
    
    // 6. البحث عن المنتجات (AJAX)
    Route::get('product-offers/search-products', [\App\Http\Controllers\ProductOfferController::class, 'searchProducts'])
        ->name('product-offers.search-products');
    
    // 7. جلب عروض منتج معين
    Route::get('product-offers/get-offers', [\App\Http\Controllers\ProductOfferController::class, 'getProductOffers'])
        ->name('product-offers.get-offers');
    
    // 8. جلب سعر العرض بناء على الكمية
    Route::get('product-offers/get-offer-price', [\App\Http\Controllers\ProductOfferController::class, 'getOfferPrice'])
        ->name('product-offers.get-offer-price');
    
    // 9. جلب العروض النشطة للموقع
    Route::get('product-offers/active-offers', [\App\Http\Controllers\ProductOfferController::class, 'getActiveOffersForLocation'])
        ->name('product-offers.active-offers');
    
    // 10. تعديل عرض (AJAX)
    Route::get('product-offers/{id}/edit', [\App\Http\Controllers\ProductOfferController::class, 'edit'])
        ->name('product-offers.edit');
    
    // 11. تحديث عرض
    Route::put('product-offers/{id}', [\App\Http\Controllers\ProductOfferController::class, 'update'])
        ->name('product-offers.update');
    
    // 12. حذف عرض
    Route::delete('product-offers/{id}', [\App\Http\Controllers\ProductOfferController::class, 'destroy'])
        ->name('product-offers.destroy');
});

Route::get('/check-offer-product', [SellPosController::class, 'checkOfferForProduct'])
    ->name('check.offer.product');

Route::middleware(['auth'])->group(function () {
    Route::get('/fawjo-settings', [FAWJOFatoraController::class, 'index'])->name('fawjo.settings');
    Route::post('/fawjo-settings', [FAWJOFatoraController::class, 'store'])->name('fawjo.settings.store');
});
use App\Http\Controllers\EGOCUSController;

Route::get('/egocus', [EGOCUSController::class, 'index'])->name('egocus.index');
Route::get('/egocus/edit/{id}', [EGOCUSController::class, 'edit'])->name('egocus.edit');
Route::post('/egocus/update/{id}', [EGOCUSController::class, 'update'])->name('egocus.update');
Route::get('/get_products', [ProductController::class, 'getProducts']);
use App\Http\Controllers\PosController;

Route::get('/pos/get-product-by-barcode', [PosController::class, 'getProductByBarcode'])
    ->name('pos.get-product-by-barcode');

Route::post('/fatora/send-invoice', [FatoraController::class, 'sendInvoice']);



Route::get('/pos/get-product-by-barcode', [PosController::class, 'getProductByBarcode'])->name('pos.get-product-by-barcode');
Route::get('/invoice/print-direct/{id}', [InvoiceController::class, 'printDirect'])
    ->name('invoice.print.direct');


Route::prefix('fatora')->group(function () {
    // الإرسال النهائي
    Route::post('/send-invoice-final', [FatoraController::class, 'sendInvoic']);
    
   
});
// إرسال فاتورة
Route::post('/send-invoice', [FatoraController::class, 'sendInvoice']);
Route::post('/fatora/send-invoice', [FatoraController::class, 'sendInvoice']);
Route::post('/fatora/send-credit-invoice', [FatoraController::class, 'sendCreditInvoice']);
Route::get('/fatora/invoice-status', [FatoraController::class, 'getInvoiceStatus']);
Route::get('/fatora/test-credit-invoice', [FatoraController::class, 'testCreditInvoice']); // TEST

// New: Get invoices from JoFotara
Route::get('/fatora/get-invoice', [FatoraController::class, 'getInvoiceFromJoFotara']); // Get single invoice by UUID
Route::get('/fatora/get-invoices-list', [FatoraController::class, 'getInvoicesListFromJoFotara']); // Get list with filters
Route::post('/fatora/sync-status', [FatoraController::class, 'syncInvoiceStatus']); // Sync invoice status
Route::get('/fatora/local-invoices', [FatoraController::class, 'getLocalInvoices']); // Get local invoices with filters

// New: Import and sync missing invoices
Route::post('/fatora/import-invoice', [FatoraController::class, 'importInvoice']); // Import single invoice
Route::post('/fatora/bulk-sync', [FatoraController::class, 'bulkSyncInvoices']); // Bulk sync invoices
Route::get('/fatora/find-missing', [FatoraController::class, 'findMissingInvoices']); // Find missing invoices
Route::post('/fatora/send-credit-auto', [FatoraController::class, 'sendCreditInvoiceWithAutoImport']); // Send credit with auto-import
Route::get('/debug-key', [FatoraController::class, 'debugKey']);
Route::get('/check-income-tax-settings', [FatoraController::class, 'checkIncomeTaxSettings']);
// اختيار معالجة المفتاح
Route::get('/fix-secret-key', [FatoraController::class, 'fixSecretKey']);
// أضف هذا الراوت
Route::get('/debug-fatora-connection', [FatoraController::class, 'debugConnection']);
// أضف هذا الراوت
Route::get('/diagnose-auth', [FatoraController::class, 'diagnoseAuthIssue']);
// أضف هذا الراوت
Route::get('/test-new-key', [FatoraController::class, 'testNewKey']);
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountReportsController;
use App\Http\Controllers\AccountTypeController;
// use App\Http\Controllers\Auth;
use App\Http\Controllers\BackUpController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessLocationController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CombinedPurchaseReturnController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\DashboardConfiguratorController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\DocumentAndNoteController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupTaxController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportOpeningStockController;
use App\Http\Controllers\ImportProductsController;
use App\Http\Controllers\ImportSalesController;
use App\Http\Controllers\Install;
use App\Http\Controllers\InvoiceLayoutController;
use App\Http\Controllers\InvoiceSchemeController;
use App\Http\Controllers\LabelsController;
use App\Http\Controllers\LedgerDiscountController;
use App\Http\Controllers\LocationSettingsController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Restaurant;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesCommissionAgentController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SellingPriceGroupController;
use App\Http\Controllers\SellPosController;
use App\Http\Controllers\SellReturnController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TransactionPaymentController;
use App\Http\Controllers\TypesOfServiceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationTemplateController;
use App\Http\Controllers\WarrantyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

include_once 'install_r.php';
Route::post('/update-price-offer', [SellPosController::class, 'updatePriceWithOffer'])->name('update.price.offer');
Route::middleware(['setData'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Auth::routes();

    Route::get('/business/register', [BusinessController::class, 'getRegister'])->name('business.getRegister');
    Route::post('/business/register', [BusinessController::class, 'postRegister'])->name('business.postRegister');
    Route::post('/business/register/check-username', [BusinessController::class, 'postCheckUsername'])->name('business.postCheckUsername');
    Route::post('/business/register/check-email', [BusinessController::class, 'postCheckEmail'])->name('business.postCheckEmail');

    Route::get('/invoice/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_invoice');
    Route::get('/quote/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_quote');

    Route::get('/pay/{token}', [SellPosController::class, 'invoicePayment'])
        ->name('invoice_payment');
    Route::post('/confirm-payment/{id}', [SellPosController::class, 'confirmPayment'])
        ->name('confirm_payment');
});

//Routes for authenticated users only


Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])->group(function () {
    Route::get('pos/payment/{id}', [SellPosController::class, 'edit'])->name('edit-pos-payment');
    Route::get('service-staff-availability', [SellPosController::class, 'showServiceStaffAvailibility']);
    Route::get('pause-resume-service-staff-timer/{user_id}', [SellPosController::class, 'pauseResumeServiceStaffTimer']);
    Route::get('mark-as-available/{user_id}', [SellPosController::class, 'markAsAvailable']);

    Route::resource('purchase-requisition', PurchaseRequisitionController::class)->except(['edit', 'update']);
    Route::post('/get-requisition-products', [PurchaseRequisitionController::class, 'getRequisitionProducts'])->name('get-requisition-products');
    Route::get('get-purchase-requisitions/{location_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitions']);
    Route::get('get-purchase-requisition-lines/{purchase_requisition_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitionLines']);

    Route::get('/sign-in-as-user/{id}', [ManageUserController::class, 'signInAsUser'])->name('sign-in-as-user');

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/get-totals', [HomeController::class, 'getTotals']);
    Route::get('/home/product-stock-alert', [HomeController::class, 'getProductStockAlert']);
    Route::get('/home/purchase-payment-dues', [HomeController::class, 'getPurchasePaymentDues']);
    Route::get('/home/sales-payment-dues', [HomeController::class, 'getSalesPaymentDues']);
    Route::post('/attach-medias-to-model', [HomeController::class, 'attachMediasToGivenModel'])->name('attach.medias.to.model');
    Route::get('/calendar', [HomeController::class, 'getCalendar'])->name('calendar');

    Route::post('/test-email', [BusinessController::class, 'testEmailConfiguration']);
    Route::post('/test-sms', [BusinessController::class, 'testSmsConfiguration']);
    Route::get('/business/settings', [BusinessController::class, 'getBusinessSettings'])->name('business.getBusinessSettings');
    Route::post('/business/update', [BusinessController::class, 'postBusinessSettings'])->name('business.postBusinessSettings');
    Route::get('/user/profile', [UserController::class, 'getProfile'])->name('user.getProfile');
    Route::post('/user/update', [UserController::class, 'updateProfile'])->name('user.updateProfile');
    Route::post('/user/update-password', [UserController::class, 'updatePassword'])->name('user.updatePassword');

    Route::resource('brands', BrandController::class);

    Route::resource('payment-account', 'PaymentAccountController');

    Route::resource('tax-rates', TaxRateController::class);

    Route::resource('units', UnitController::class);

    Route::resource('ledger-discount', LedgerDiscountController::class)->only('edit', 'destroy', 'store', 'update');

    Route::post('check-mobile', [ContactController::class, 'checkMobile']);
    Route::get('/get-contact-due/{contact_id}', [ContactController::class, 'getContactDue']);
    Route::get('/contacts/payments/{contact_id}', [ContactController::class, 'getContactPayments']);
    Route::get('/contacts/map', [ContactController::class, 'contactMap']);
    Route::get('/contacts/update-status/{id}', [ContactController::class, 'updateStatus']);
    Route::get('/contacts/stock-report/{supplier_id}', [ContactController::class, 'getSupplierStockReport']);
    Route::get('/contacts/ledger', [ContactController::class, 'getLedger']);
    Route::post('/contacts/send-ledger', [ContactController::class, 'sendLedger']);
    Route::get('/contacts/import', [ContactController::class, 'getImportContacts'])->name('contacts.import');
    Route::post('/contacts/import', [ContactController::class, 'postImportContacts']);
    Route::post('/contacts/check-contacts-id', [ContactController::class, 'checkContactId']);

    Route::post('/contacts/check-tax-number', [ContactController::class, 'checkTaxNumber']);

    Route::get('/contacts/customers', [ContactController::class, 'getCustomers']);
    Route::resource('contacts', ContactController::class);

    Route::get('taxonomies-ajax-index-page', [TaxonomyController::class, 'getTaxonomyIndexPage']);
    Route::resource('taxonomies', TaxonomyController::class);

    Route::resource('variation-templates', VariationTemplateController::class);

    Route::get('/products/download-excel', [ProductController::class, 'downloadExcel']);

    Route::get('/products/stock-history/{id}', [ProductController::class, 'productStockHistory']);
    Route::get('/delete-media/{media_id}', [ProductController::class, 'deleteMedia']);
    Route::post('/products/mass-deactivate', [ProductController::class, 'massDeactivate']);
    Route::get('/products/activate/{id}', [ProductController::class, 'activate']);
    Route::get('/products/view-product-group-price/{id}', [ProductController::class, 'viewGroupPrice']);
    Route::get('/products/add-selling-prices/{id}', [ProductController::class, 'addSellingPrices']);
    Route::post('/products/save-selling-prices', [ProductController::class, 'saveSellingPrices']);
    Route::post('/products/mass-delete', [ProductController::class, 'massDestroy']);
    Route::get('/products/view/{id}', [ProductController::class, 'view']);
    Route::get('/products/list', [ProductController::class, 'getProducts']);
    Route::get('/products/list-no-variation', [ProductController::class, 'getProductsWithoutVariations']);
    Route::post('/products/bulk-edit', [ProductController::class, 'bulkEdit']);
    Route::post('/products/bulk-update', [ProductController::class, 'bulkUpdate']);
    Route::post('/products/bulk-update-location', [ProductController::class, 'updateProductLocation']);
    Route::get('/products/get-product-to-edit/{product_id}', [ProductController::class, 'getProductToEdit']);

    Route::post('/products/get_sub_categories', [ProductController::class, 'getSubCategories']);
    Route::get('/products/get_sub_units', [ProductController::class, 'getSubUnits']);
    Route::post('/products/product_form_part', [ProductController::class, 'getProductVariationFormPart']);
    Route::post('/products/get_product_variation_row', [ProductController::class, 'getProductVariationRow']);
    Route::post('/products/get_variation_template', [ProductController::class, 'getVariationTemplate']);
    Route::get('/products/get_variation_value_row', [ProductController::class, 'getVariationValueRow']);
    Route::post('/products/check_product_sku', [ProductController::class, 'checkProductSku']);
    Route::post('/products/check_product_name', [ProductController::class, 'checkProductName']);
    Route::post('/products/validate_variation_skus', [ProductController::class, 'validateVaritionSkus']); //validates multiple skus at once
    Route::get('/products/quick_add', [ProductController::class, 'quickAdd']);
    Route::post('/products/save_quick_product', [ProductController::class, 'saveQuickProduct']);
    Route::get('/products/get-combo-product-entry-row', [ProductController::class, 'getComboProductEntryRow']);
    Route::post('/products/toggle-woocommerce-sync', [ProductController::class, 'toggleWooCommerceSync']);

    Route::resource('products', ProductController::class);
    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');
    Route::post('/sells/pos/get-types-of-service-details', 'SellPosController@getTypesOfServiceDetails');
    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/drafts', 'SellController@getDrafts');
    Route::get('/sells/convert-to-draft/{id}', 'SellPosController@convertToInvoice');
    Route::get('/sells/convert-to-proforma/{id}', 'SellPosController@convertToProforma');
    Route::get('/sells/quotations', 'SellController@getQuotations');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');
    Route::resource('sells', 'SellController')->except(['show']);
    Route::get('/sells/copy-quotation/{id}', [SellPosController::class, 'copyQuotation']);

    Route::post('/import-purchase-products', [PurchaseController::class, 'importPurchaseProducts']);
    Route::post('/purchases/update-status', [PurchaseController::class, 'updateStatus']);
    Route::get('/purchases/get_products', [PurchaseController::class, 'getProducts']);
    Route::get('/purchases/get_suppliers', [PurchaseController::class, 'getSuppliers']);
    Route::post('/purchases/get_purchase_entry_row', [PurchaseController::class, 'getPurchaseEntryRow']);
    Route::post('/purchases/check_ref_number', [PurchaseController::class, 'checkRefNumber']);
    ////// 001
    Route::get('/purchases/get-detailed-report', [\App\Http\Controllers\PurchaseController::class, 'getDetailedPurchaseReport']);

    Route::resource('purchases', PurchaseController::class)->except(['show']);

    Route::get('/toggle-subscription/{id}', [SellPosController::class, 'toggleRecurringInvoices']);
    Route::post('/sells/pos/get-types-of-service-details', [SellPosController::class, 'getTypesOfServiceDetails']);
    Route::get('/sells/subscriptions', [SellPosController::class, 'listSubscriptions']);
    Route::get('/sells/duplicate/{id}', [SellController::class, 'duplicateSell']);
    Route::get('/sells/drafts', [SellController::class, 'getDrafts']);
    
    // NEW: Draft management with separate table
    Route::post('/drafts/store', [\App\Http\Controllers\DraftController::class, 'storeDraft'])->name('drafts.store');
    Route::get('/drafts/convert-to-invoice/{id}', [\App\Http\Controllers\DraftController::class, 'convertToInvoice'])->name('drafts.convert');
    Route::delete('/drafts/{id}', [\App\Http\Controllers\DraftController::class, 'destroy'])->name('drafts.destroy');
    Route::get('/drafts/{id}/show', [\App\Http\Controllers\DraftController::class, 'show'])->name('drafts.show');
    
    // Keep old routes for compatibility (redirect to new DraftController)
    Route::get('/sells/convert-to-draft/{id}', [SellPosController::class, 'convertToInvoice']);
    Route::get('/sells/convert-to-proforma/{id}', [SellPosController::class, 'convertToProforma']);
    Route::get('/sells/quotations', [SellController::class, 'getQuotations']);
    Route::get('/sells/draft-dt', [SellController::class, 'getDraftDatables']);
    Route::resource('sells', SellController::class)->except(['show']);

    Route::get('/import-sales', [ImportSalesController::class, 'index']);
    Route::post('/import-sales/preview', [ImportSalesController::class, 'preview']);
    Route::post('/import-sales', [ImportSalesController::class, 'import']);
    Route::get('/revert-sale-import/{batch}', [ImportSalesController::class, 'revertSaleImport']);

    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', [SellPosController::class, 'getProductRow']);
    Route::post('/sells/pos/get_payment_row', [SellPosController::class, 'getPaymentRow']);
    Route::post('/sells/pos/get-reward-details', [SellPosController::class, 'getRewardDetails']);
    Route::get('/sells/pos/get-recent-transactions', [SellPosController::class, 'getRecentTransactions']);
    Route::get('/sells/pos/get-product-suggestion', [SellPosController::class, 'getProductSuggestion']);
    Route::get('/sells/pos/get-featured-products/{location_id}', [SellPosController::class, 'getFeaturedProducts']);
    Route::get('/reset-mapping', [SellController::class, 'resetMapping']);
    // pos display screen route
    Route::get('/customer-display', [SellPosController::class, 'posDisplay'])->name('pos_display');
    Route::get('/pos/variation/{variation_id}/{location_id}', [\App\Http\Controllers\ProductController::class, 'getVarationDetail']);
    // end pos display screen route
    Route::resource('pos', SellPosController::class);

    Route::resource('roles', RoleController::class);

    Route::resource('users', ManageUserController::class);

    Route::resource('group-taxes', GroupTaxController::class);

    Route::get('/barcodes/set_default/{id}', [BarcodeController::class, 'setDefault']);
    Route::resource('barcodes', BarcodeController::class);

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', [InvoiceSchemeController::class, 'setDefault']);
    Route::resource('invoice-schemes', InvoiceSchemeController::class);

    //Print Labels
    Route::get('/labels/show', [LabelsController::class, 'show']);
    Route::get('/labels/add-product-row', [LabelsController::class, 'addProductRow']);
    Route::get('/labels/preview', [LabelsController::class, 'preview']);

    //Reports...
    Route::get('/reports/gst-purchase-report', [ReportController::class, 'gstPurchaseReport']);
    Route::get('/reports/gst-sales-report', [ReportController::class, 'gstSalesReport']);
    Route::get('/reports/get-stock-by-sell-price', [ReportController::class, 'getStockBySellingPrice']);
    Route::get('/reports/purchase-report', [ReportController::class, 'purchaseReport']);
    Route::get('/reports/sale-report', [ReportController::class, 'saleReport']);
    Route::get('/reports/service-staff-report', [ReportController::class, 'getServiceStaffReport']);
    Route::get('/reports/service-staff-line-orders', [ReportController::class, 'serviceStaffLineOrders']);
    Route::get('/reports/table-report', [ReportController::class, 'getTableReport']);
    Route::get('/reports/profit-loss', [ReportController::class, 'getProfitLoss']);
    Route::get('/reports/get-opening-stock', [ReportController::class, 'getOpeningStock']);
    Route::get('/reports/purchase-sell', [ReportController::class, 'getPurchaseSell']);
    Route::get('/reports/customer-supplier', [ReportController::class, 'getCustomerSuppliers']);
    Route::get('/reports/stock-report', [ReportController::class, 'getStockReport']);
    Route::get('/reports/stock-details', [ReportController::class, 'getStockDetails']);
    Route::get('/reports/tax-report', [ReportController::class, 'getTaxReport']);
    Route::get('/reports/tax-details', [ReportController::class, 'getTaxDetails']);
    Route::get('/reports/trending-products', [ReportController::class, 'getTrendingProducts']);
    Route::get('/reports/expense-report', [ReportController::class, 'getExpenseReport']);
    Route::get('/reports/stock-adjustment-report', [ReportController::class, 'getStockAdjustmentReport']);
    Route::get('/reports/register-report', [ReportController::class, 'getRegisterReport']);
    Route::get('/reports/sales-representative-report', [ReportController::class, 'getSalesRepresentativeReport']);
    Route::get('/reports/sales-representative-total-expense', [ReportController::class, 'getSalesRepresentativeTotalExpense']);
    Route::get('/reports/sales-representative-total-sell', [ReportController::class, 'getSalesRepresentativeTotalSell']);
    Route::get('/reports/sales-representative-total-commission', [ReportController::class, 'getSalesRepresentativeTotalCommission']);
    Route::get('/reports/stock-expiry', [ReportController::class, 'getStockExpiryReport']);
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', [ReportController::class, 'getStockExpiryReportEditModal']);
    Route::post('/reports/stock-expiry-update', [ReportController::class, 'updateStockExpiryReport'])->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', [ReportController::class, 'getCustomerGroup']);
    Route::get('/reports/product-purchase-report', [ReportController::class, 'getproductPurchaseReport']);
    Route::get('/reports/product-sell-grouped-by', [ReportController::class, 'productSellReportBy']);
    Route::get('/reports/product-sell-report', [ReportController::class, 'getproductSellReport']);
    Route::get('/reports/product-sell-report-with-purchase', [ReportController::class, 'getproductSellReportWithPurchase']);
    Route::get('/reports/product-sell-grouped-report', [ReportController::class, 'getproductSellGroupedReport']);
    Route::get('/reports/lot-report', [ReportController::class, 'getLotReport']);
    Route::get('/reports/purchase-payment-report', [ReportController::class, 'purchasePaymentReport']);
    Route::get('/reports/sell-payment-report', [ReportController::class, 'sellPaymentReport']);
    Route::get('/reports/product-stock-details', [ReportController::class, 'productStockDetails']);
    Route::get('/reports/adjust-product-stock', [ReportController::class, 'adjustProductStock']);
    Route::get('/reports/get-profit/{by?}', [ReportController::class, 'getProfit']);
    Route::get('/reports/items-report', [ReportController::class, 'itemsReport']);
    Route::get('/reports/get-stock-value', [ReportController::class, 'getStockValue']);
    ////////// 001 
    Route::get('/reports/payment-method-report', [ReportController::class, 'getPaymentMethodReport']);/////////// 001
    Route::get('/reports/sales-representative-summary', [\App\Http\Controllers\ReportController::class, 'getSalesRepresentativeSummary']);
    Route::get('/reports/quantity-entry-report', [ReportController::class, 'quantityEntryReport']);
    Route::get('/reports/daily-sales-report', [ReportController::class, 'dailySalesReport'])->name('dailySalesReport');
    Route::get('/reports/detailed-sales-report', [ReportController::class, 'detailedSalesReport'])->name('detailedSalesReport');
    Route::get('/reports/get-daily-sales-details-modal', [ReportController::class, 'getDailySalesDetailsModal']);

    Route::get('/reports/product-sales', [ReportController::class, 'productSalesIndex'])->name('productSalesIndex');
    Route::get('/reports/product-sales-detailed', [ReportController::class, 'productSalesDetailedReport'])->name('productSalesDetailedReport'); // تقرير المبيعات التفصيلي
    Route::get('/reports/product-sales-summary', [ReportController::class, 'productSalesSummaryReport'])->name('productSalesSummaryReport'); // تقرير المبيعات المجمل
    Route::get('/reports/product-returns-summary', [ReportController::class, 'productReturnsSummaryReport'])->name('productReturnsSummaryReport');
    Route::get('/reports/product-returns-detailed', [ReportController::class, 'productReturnsDetailedReport'])->name('productReturnsDetailedReport');
    Route::get('/reports/product-all-transactions', [ReportController::class, 'productAllTransactionsReport'])->name('productAllTransactionsReport');
    Route::get('/reports/product-all-summary', [ReportController::class, 'productAllSummaryReport'])->name('productAllSummaryReport');

    //////////// 001

    Route::get('business-location/activate-deactivate/{location_id}', [BusinessLocationController::class, 'activateDeactivateLocation']);

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', [LocationSettingsController::class, 'index'])->name('settings');
        Route::post('settings', [LocationSettingsController::class, 'updateSettings'])->name('settings_update');
    });

    //Business Locations...
    Route::post('business-location/check-location-id', [BusinessLocationController::class, 'checkLocationId']);
    Route::resource('business-location', BusinessLocationController::class);

    //Invoice layouts..
    Route::resource('invoice-layouts', InvoiceLayoutController::class);

    Route::post('get-expense-sub-categories', [ExpenseCategoryController::class, 'getSubCategories']);

    //Expense Categories...
    Route::resource('expense-categories', ExpenseCategoryController::class);

    //Expenses...
    Route::resource('expenses', ExpenseController::class);
    Route::get('import-expense', [ExpenseController::class, 'importExpense']);
    Route::post('store-import-expense', [ExpenseController::class, 'storeExpenseImport']);

    //Transaction payments...
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', [TransactionPaymentController::class, 'showChildPayments']);
    Route::get('/payments/view-payment/{payment_id}', [TransactionPaymentController::class, 'viewPayment']);
    Route::get('/payments/add_payment/{transaction_id}', [TransactionPaymentController::class, 'addPayment']);
    Route::get('/payments/pay-contact-due/{contact_id}', [TransactionPaymentController::class, 'getPayContactDue']);
    Route::post('/payments/pay-contact-due', [TransactionPaymentController::class, 'postPayContactDue']);
    Route::resource('payments', TransactionPaymentController::class);

    //Printers...
    Route::resource('printers', PrinterController::class);
////////////////// 001 
Route::post('/stock-adjustments/import', [App\Http\Controllers\StockAdjustmentController::class, 'import'])->name('stock_adjustment.export');
    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', [StockAdjustmentController::class, 'removeExpiredStock']);
    Route::post('/stock-adjustments/get_product_row', [StockAdjustmentController::class, 'getProductRow']);
    Route::resource('stock-adjustments', StockAdjustmentController::class);

    Route::get('/cash-register/register-details', [CashRegisterController::class, 'getRegisterDetails']);
    Route::get('/cash-register/close-register/{id?}', [CashRegisterController::class, 'getCloseRegister']);
    Route::post('/cash-register/close-register', [CashRegisterController::class, 'postCloseRegister']);
    Route::resource('cash-register', CashRegisterController::class);

    //Import products
    Route::get('/import-products', [ImportProductsController::class, 'index']);
    Route::post('/import-products/store', [ImportProductsController::class, 'store']);

    //Sales Commission Agent
    Route::resource('sales-commission-agents', SalesCommissionAgentController::class);

    //Stock Transfer
    Route::get('stock-transfers/print/{id}', [StockTransferController::class, 'printInvoice']);
    Route::post('stock-transfers/update-status/{id}', [StockTransferController::class, 'updateStatus']);
    Route::resource('stock-transfers', StockTransferController::class);

    Route::get('/opening-stock/add/{product_id}', [OpeningStockController::class, 'add']);
    Route::post('/opening-stock/save', [OpeningStockController::class, 'save']);

    //Customer Groups
    Route::resource('customer-group', CustomerGroupController::class);

    //Import opening stock
    Route::get('/import-opening-stock', [ImportOpeningStockController::class, 'index']);
    Route::post('/import-opening-stock/store', [ImportOpeningStockController::class, 'store']);
    //// return by barcode 001
    // رابط البحث عن الفواتير بواسطة SKU المنتج
    Route::get('/search-invoices-by-product/{sku}', [ SellReturnController::class, 'searchInvoicesByProduct']);
    //Sell return
    Route::get('validate-invoice-to-return/{invoice_no}', [SellReturnController::class, 'validateInvoiceToReturn']);
    // service staff replacement
    Route::get('validate-invoice-to-service-staff-replacement/{invoice_no}', [SellPosController::class, 'validateInvoiceToServiceStaffReplacement']);
    Route::put('change-service-staff/{id}', [SellPosController::class, 'change_service_staff'])->name('change_service_staff');

    Route::resource('sell-return', SellReturnController::class);
    Route::get('sell-return/get-product-row', [SellReturnController::class, 'getProductRow']);
    Route::get('/sell-return/print/{id}', [SellReturnController::class, 'printInvoice']);
    Route::get('/sell-return/add/{id}', [SellReturnController::class, 'add']);

    //Backup
    Route::get('backup/download/{file_name}', [BackUpController::class, 'download']);
    Route::get('backup/{id}/delete', [BackUpController::class, 'delete'])->name('delete_backup');
    Route::resource('backup', BackUpController::class)->only('index', 'create', 'store');

    Route::get('selling-price-group/activate-deactivate/{id}', [SellingPriceGroupController::class, 'activateDeactivate']);
    Route::get('update-product-price', [SellingPriceGroupController::class, 'updateProductPrice'])->name('update-product-price');
    Route::get('export-product-price', [SellingPriceGroupController::class, 'export']);
    Route::post('import-product-price', [SellingPriceGroupController::class, 'import']);

    Route::resource('selling-price-group', SellingPriceGroupController::class);

    Route::resource('notification-templates', NotificationTemplateController::class)->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', [NotificationController::class, 'getTemplate']);
    Route::post('notification/send', [NotificationController::class, 'send']);

    Route::post('/purchase-return/update', [CombinedPurchaseReturnController::class, 'update']);
    Route::get('/purchase-return/edit/{id}', [CombinedPurchaseReturnController::class, 'edit']);
    Route::post('/purchase-return/save', [CombinedPurchaseReturnController::class, 'save']);
    Route::post('/purchase-return/get_product_row', [CombinedPurchaseReturnController::class, 'getProductRow']);
    Route::get('/purchase-return/create', [CombinedPurchaseReturnController::class, 'create']);
    Route::get('/purchase-return/add/{id}', [PurchaseReturnController::class, 'add']);
    Route::resource('/purchase-return', PurchaseReturnController::class)->except('create');

    Route::get('/discount/activate/{id}', [DiscountController::class, 'activate']);
    Route::post('/discount/mass-deactivate', [DiscountController::class, 'massDeactivate']);
    Route::resource('discount', DiscountController::class);

    Route::prefix('account')->group(function () {
        Route::resource('/account', AccountController::class);
        Route::get('/fund-transfer/{id}', [AccountController::class, 'getFundTransfer']);
        Route::post('/fund-transfer', [AccountController::class, 'postFundTransfer']);
        Route::get('/deposit/{id}', [AccountController::class, 'getDeposit']);
        Route::post('/deposit', [AccountController::class, 'postDeposit']);
        Route::get('/close/{id}', [AccountController::class, 'close']);
        Route::get('/activate/{id}', [AccountController::class, 'activate']);
        Route::get('/delete-account-transaction/{id}', [AccountController::class, 'destroyAccountTransaction']);
        Route::get('/edit-account-transaction/{id}', [AccountController::class, 'editAccountTransaction']);
        Route::post('/update-account-transaction/{id}', [AccountController::class, 'updateAccountTransaction']);
        Route::get('/get-account-balance/{id}', [AccountController::class, 'getAccountBalance']);
        Route::get('/balance-sheet', [AccountReportsController::class, 'balanceSheet']);
        Route::get('/trial-balance', [AccountReportsController::class, 'trialBalance']);
        Route::get('/payment-account-report', [AccountReportsController::class, 'paymentAccountReport']);
        Route::get('/link-account/{id}', [AccountReportsController::class, 'getLinkAccount']);
        Route::post('/link-account', [AccountReportsController::class, 'postLinkAccount']);
        Route::get('/cash-flow', [AccountController::class, 'cashFlow']);
    });

    Route::resource('account-types', AccountTypeController::class);

    //Restaurant module
    Route::prefix('modules')->group(function () {
        Route::resource('tables', Restaurant\TableController::class);
        Route::resource('modifiers', Restaurant\ModifierSetsController::class);

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', [Restaurant\ProductModifierSetController::class, 'edit']);
        Route::post('/product-modifiers/{id}/update', [Restaurant\ProductModifierSetController::class, 'update']);
        Route::get('/product-modifiers/product-row/{product_id}', [Restaurant\ProductModifierSetController::class, 'product_row']);

        Route::get('/add-selected-modifiers', [Restaurant\ProductModifierSetController::class, 'add_selected_modifiers']);

        Route::get('/kitchen', [Restaurant\KitchenController::class, 'index']);
        Route::get('/kitchen/mark-as-cooked/{id}', [Restaurant\KitchenController::class, 'markAsCooked']);
        Route::post('/refresh-orders-list', [Restaurant\KitchenController::class, 'refreshOrdersList']);
        Route::post('/refresh-line-orders-list', [Restaurant\KitchenController::class, 'refreshLineOrdersList']);

        Route::get('/orders', [Restaurant\OrderController::class, 'index']);
        Route::get('/orders/mark-as-served/{id}', [Restaurant\OrderController::class, 'markAsServed']);
        Route::get('/data/get-pos-details', [Restaurant\DataController::class, 'getPosDetails']);
        Route::get('/data/check-staff-pin', [Restaurant\DataController::class, 'checkStaffPin']);
        Route::get('/orders/mark-line-order-as-served/{id}', [Restaurant\OrderController::class, 'markLineOrderAsServed']);
        Route::get('/print-line-order', [Restaurant\OrderController::class, 'printLineOrder']);
    });

    Route::get('bookings/get-todays-bookings', [Restaurant\BookingController::class, 'getTodaysBookings']);
    Route::resource('bookings', Restaurant\BookingController::class);

    Route::resource('types-of-service', TypesOfServiceController::class);
    Route::get('sells/edit-shipping/{id}', [SellController::class, 'editShipping']);
    Route::put('sells/update-shipping/{id}', [SellController::class, 'updateShipping']);
    Route::get('shipments', [SellController::class, 'shipments']);

    Route::post('upload-module', [Install\ModulesController::class, 'uploadModule']);
    Route::delete('manage-modules/destroy/{module_name}', [Install\ModulesController::class, 'destroy']);
    Route::resource('manage-modules', Install\ModulesController::class)
        ->only(['index', 'update']);
    Route::get('regenerate', [Install\ModulesController::class, 'regenerate']);

    Route::resource('warranties', WarrantyController::class);

    Route::resource('dashboard-configurator', DashboardConfiguratorController::class)
    ->only(['edit', 'update']);

    Route::get('view-media/{model_id}', [SellController::class, 'viewMedia']);

    //common controller for document & note
    Route::get('get-document-note-page', [DocumentAndNoteController::class, 'getDocAndNoteIndexPage']);
    Route::post('post-document-upload', [DocumentAndNoteController::class, 'postMedia']);
    Route::resource('note-documents', DocumentAndNoteController::class);
    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::get('get-purchase-orders/{contact_id}', [PurchaseOrderController::class, 'getPurchaseOrders']);
    Route::get('get-purchase-order-lines/{purchase_order_id}', [PurchaseController::class, 'getPurchaseOrderLines']);
    Route::get('edit-purchase-orders/{id}/status', [PurchaseOrderController::class, 'getEditPurchaseOrderStatus']);
    Route::put('update-purchase-orders/{id}/status', [PurchaseOrderController::class, 'postEditPurchaseOrderStatus']);
    Route::resource('sales-order', SalesOrderController::class)->only(['index']);
    Route::get('get-sales-orders/{customer_id}', [SalesOrderController::class, 'getSalesOrders']);
    Route::get('get-sales-order-lines', [SellPosController::class, 'getSalesOrderLines']);
    Route::get('edit-sales-orders/{id}/status', [SalesOrderController::class, 'getEditSalesOrderStatus']);
    Route::put('update-sales-orders/{id}/status', [SalesOrderController::class, 'postEditSalesOrderStatus']);
    Route::get('reports/activity-log', [ReportController::class, 'activityLog']);
    Route::get('user-location/{latlng}', [HomeController::class, 'getUserLocation']);

    
});

// Route::middleware(['EcomApi'])->prefix('api/ecom')->group(function () {
//     Route::get('products/{id?}', [ProductController::class, 'getProductsApi']);
//     Route::get('categories', [CategoryController::class, 'getCategoriesApi']);
//     Route::get('brands', [BrandController::class, 'getBrandsApi']);
//     Route::post('customers', [ContactController::class, 'postCustomersApi']);
//     Route::get('settings', [BusinessController::class, 'getEcomSettings']);
//     Route::get('variations', [ProductController::class, 'getVariationsApi']);
//     Route::post('orders', [SellPosController::class, 'placeOrdersApi']);\});

//common route
Route::middleware(['auth'])->group(function () {
    Route::get('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
});

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/load-more-notifications', [HomeController::class, 'loadMoreNotifications']);
    Route::get('/get-total-unread', [HomeController::class, 'getTotalUnreadNotifications']);
    Route::get('/purchases/print/{id}', [PurchaseController::class, 'printInvoice']);
    Route::get('/purchases/{id}', [PurchaseController::class, 'show']);
    Route::get('/download-purchase-order/{id}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('purchaseOrder.downloadPdf');
    Route::get('/sells/{id}', [SellController::class, 'show']);
    Route::get('/sells/{transaction_id}/print', [SellPosController::class, 'printInvoice'])->name('sell.printInvoice');
    Route::get('/download-sells/{transaction_id}/pdf', [SellPosController::class, 'downloadPdf'])->name('sell.downloadPdf');
    Route::get('/download-quotation/{id}/pdf', [SellPosController::class, 'downloadQuotationPdf'])
        ->name('quotation.downloadPdf');
    Route::get('/download-packing-list/{id}/pdf', [SellPosController::class, 'downloadPackingListPdf'])
        ->name('packing.downloadPdf');
    Route::get('/sells/invoice-url/{id}', [SellPosController::class, 'showInvoiceUrl']);
    Route::get('/show-notification/{id}', [HomeController::class, 'showNotification']);
    Route::post('/sell/check-invoice-number', [SellController::class, 'checkInvoiceNumber']);
});