@extends('layouts.app')

@section('content')


    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>نظام طباعة الباركود</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="{{ asset('js/qz/qz-tray.js') }}"></script>
    <script src="{{ asset('js/qz/rsvp.min.js') }}"></script>
    <script src="{{ asset('js/qz/sha256.min.js') }}"></script>

    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #06d6a0;
            --danger: #ef476f;
            --warning: #ffd166;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border: #e0e0e0;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: var(--radius);
            padding: 20px 30px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-right: 5px solid var(--primary);
        }

        .header h1 {
            color: var(--dark);
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-info {
            display: flex;
            gap: 20px;
        }

        .info-card {
            background: var(--light);
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
        }

        .panel {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .panel-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .panel-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-body {
            padding: 20px;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .control-group label {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
        }

        .control-group input, 
        .control-group select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
        }

        .control-group input:focus, 
        .control-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #05c290;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--dark);
        }

        .btn-outline:hover {
            background: var(--light);
        }

        .products-container {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .product-item {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-item:hover {
            background: #f8f9ff;
        }

        .product-item.selected {
            background: #eef2ff;
            border-right: 4px solid var(--primary);
        }

        .product-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .product-variation-label {
            display: block;
            font-size: 12px;
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .product-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: var(--gray);
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .quantity-control input {
            width: 60px;
            text-align: center;
            padding: 5px;
            border: 1px solid var(--border);
            border-radius: 4px;
        }

        .preview-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .label-preview {
            width: calc(50mm + 20px);
            height: calc(25mm + 20px);
            border: 1px solid var(--border);
            background: white;
            position: relative;
            padding: 10px;
            box-sizing: border-box;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
        }

        .label-content {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .element {
            position: absolute;
            white-space: nowrap;
            overflow: visible;
        }

        .preview-info {
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
            width: 100%;
        }

        .preview-info h4 {
            margin-bottom: 10px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .current-product {
            font-weight: 600;
            color: var(--primary);
        }

        .selected-products-panel {
            margin-top: 20px;
        }

        .selected-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-top: 10px;
        }

        .selected-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            gap: 10px;
        }

        .selected-item:last-child {
            border-bottom: none;
        }

        .selected-item-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

        .selected-item-qty {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .selected-item-qty input {
            width: 52px;
            padding: 4px 6px;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 14px;
        }

        .selected-item-name {
            font-weight: 600;
        }

        .selected-item-meta {
            font-size: 12px;
            color: var(--gray);
        }

        .btn-remove {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 4px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-remove:hover {
            background: #e0355f;
        }

        .system-status {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .status-connected {
            background: var(--success);
        }

        .status-disconnected {
            background: var(--danger);
        }

        /* لوحة اللون والمقاسات */
        .combos-panel { margin-top: 15px; }
        .combos-color-group { margin-bottom: 12px; background: var(--light); border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }
        .combos-color-head { padding: 8px 12px; background: linear-gradient(to right, var(--primary), var(--secondary)); color: white; font-weight: 700; font-size: 14px; }
        .combos-list { max-height: 220px; overflow-y: auto; }
        .combo-row { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-bottom: 1px solid var(--border); }
        .combo-row:last-child { border-bottom: none; }
        .combo-row input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .combo-row label { flex: 1; cursor: pointer; margin: 0; font-size: 14px; }
        .combo-row .combo-sku { font-size: 12px; color: var(--gray); }
        .combo-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
        .combo-actions .btn { flex: 1; min-width: 120px; }

        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .controls-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(67, 97, 238, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(67, 97, 238, 0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>


    <div class="container">
        <div class="header fade-in">
            <h1>
                <i>📊</i> نظام طباعة الباركود
            </h1>
            <div class="header-info">
                <div class="info-card">
                    <i>🏪</i> {{ Auth::user()->business->name ?? 'المحل' }}
                </div>
                <div class="info-card">
                    <i>👤</i> {{ Auth::user()->name ?? 'المستخدم' }}
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="left-column">
                <div class="panel fade-in">
                    <div class="panel-header">
                        <h3><i>⚙️</i> إعدادات الطباعة</h3>
                    </div>
                    <div class="panel-body">
                        <div class="controls-grid">
                            <div class="control-group">
                                <label for="searchInput">🔍 بحث عن المنتجات</label>
                                <input id="searchInput" type="text" placeholder="اكتب اسم المنتج أو SKU للبحث..." />
                            </div>
                            <div class="control-group">
                                <label for="printers">🖨️ اختيار الطابعة</label>
                                <select id="printers">
                                    <option value="">جاري تحميل الطابعات...</option>
                                </select>
                            </div>
                            <div class="control-group" style="display: none;">
                                <label>📏 أبعاد الملصق على الطابعة (مم)</label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                    <div>
                                        <label for="labelWidthMm" style="font-size: 12px;">العرض</label>
                                        <input type="number" id="labelWidthMm" class="form-control" min="10" max="200" step="1" placeholder="50" title="عرض الملصق بالمليمتر" style="padding: 8px;" />
                                    </div>
                                    <div>
                                        <label for="labelHeightMm" style="font-size: 12px;">الارتفاع</label>
                                        <input type="number" id="labelHeightMm" class="form-control" min="10" max="200" step="1" placeholder="25" title="ارتفاع الملصق بالمليمتر" style="padding: 8px;" />
                                    </div>
                                </div>
                              
                                <button type="button" id="btnXPrinterPreset" class="btn btn-sm btn-outline" style="margin-top: 6px;" title="طابعة XPrinter — أبعاد شائعة للملصقات الحرارية">
                                    🖨️ XPrinter (50×25 مم)
                                </button>
                            </div>
                            <div class="control-group" style="display: none;">
                                <label>📤 طريقة الإرسال للطابعة</label>
                                <select id="printSendMode" class="form-control" style="padding: 10px 12px;">
                                    <option value="all_at_once" selected>طباعة مباشرة للكل — إرسال واحد لكل الملصقات</option>
                                    <option value="one_by_one">وحدة وحدة — إرسال كل ملصق على حدة</option>
                                </select>
                               
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button id="searchBtn" class="btn btn-primary">
                                <i>🔍</i> بحث
                            </button>
                            <button id="refreshPrinters" class="btn btn-outline">
                                <i>🔄</i> تحديث
                            </button>
                            <button id="printSingleBtn" class="btn btn-success">
                                <i>🖨️</i> طباعة واحدة
                            </button>
                            <button id="printSelectedBtn" class="btn btn-primary">
                                <i>🖨️</i> طباعة المحدد (<span id="selectedCount">0</span>)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="panel fade-in" style="margin-top: 25px;">
                    <div class="panel-header">
                        <h3><i>👁️</i> معاينة الملصق</h3>
                    </div>
                    <div class="panel-body">
                        <div class="preview-container">
                            <div class="label-preview pulse">
                                <div class="label-content" id="labelPreview">
                                    <!-- المعاينة تظهر هنا -->
                                </div>
                            </div>
                            <div class="preview-info">
                                <h4><i>📦</i> المنتج الحالي</h4>
                                <div class="current-product" id="currentProductName">لم يتم اختيار منتج</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-column">
                <div class="panel fade-in">
                    <div class="panel-header">
                        <h3><i>📦</i> قائمة المنتجات</h3>
                    </div>
                    <div class="panel-body">
                        <div class="products-container" id="productsContainer">
                            @if(isset($products) && $products->count())
                                @foreach($products as $p)
                                    @php
                                        $variation = $p->variations->first();
                                    @endphp
                                    @if($variation)
                                    @php
                                        $barcode = $variation->sub_sku ?: $p->sku;
                                        $price = $variation->sell_price_inc_tax ?? $variation->default_sell_price ?? 0;
                                        $cf1 = trim($p->product_custom_field1 ?? '');
                                        $cf2 = trim($p->product_custom_field2 ?? '');
                                        $variationName = trim($variation->name ?? '');
                                        $colorSizeLabel = '';
                                        if ($cf1 !== '' || $cf2 !== '') {
                                            $parts = [];
                                            if ($cf1 !== '') $parts[] = 'اللون: ' . $cf1;
                                            if ($cf2 !== '') $parts[] = 'المقاس: ' . $cf2;
                                            $colorSizeLabel = implode(' — ', $parts);
                                        } elseif ($variationName !== '' && $variationName !== 'DUMMY') {
                                            $colorSizeLabel = 'اللون والمقاس: ' . $variationName;
                                        }
                                    @endphp
                                    <div class="product-item" 
                                         data-id="{{ $p->id }}" 
                                         data-sku="{{ $p->sku }}" 
                                         data-barcode="{{ $barcode }}"
                                         data-name="{{ $p->name }}" 
                                         data-price="{{ $price }}" 
                                         data-brand="{{ optional($p->brand)->name ?? '' }}"
                                         data-custom-field-1="{{ $p->product_custom_field1 ?? '' }}"
                                         data-custom-field-2="{{ $p->product_custom_field2 ?? '' }}">
                                        <div class="product-info">
                                            <h4>{{ $p->name }}</h4>
                                            @if($colorSizeLabel !== '')
                                            <span class="product-variation-label">{{ $colorSizeLabel }}</span>
                                            @endif
                                            <div class="product-meta">
                                                <span>SKU: {{ $p->sku }}</span>
                                                <span>السعر: {{ number_format($price, 2) }}</span>
                                            </div>
                                            <div class="quantity-control" style="display:none;">
                                                <label>عدد الطباعة:</label>
                                                <input type="number" class="quantity-input" value="1" min="1" max="999" title="كم مرة يُرسل هذا المنتج للطابعة">
                                            </div>
                                        </div>
                                        <div class="product-badge">
                                            <i>🏷️</i>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            @else
                                <div style="text-align: center; padding: 20px; color: var(--gray);">
                                    <i>📦</i>
                                    <p>لا توجد منتجات</p>
                                    <p style="font-size: 14px; margin-top: 10px;">استخدم البحث لعرض المنتجات</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="panel fade-in combos-panel" id="combosPanel" style="display: none;">
                    <div class="panel-header">
                        <h3><i>🏷️</i> اللون والمقاسات — اختر ما تريد طباعته</h3>
                    </div>
                    <div class="panel-body">
                        <div id="combosProductName" style="font-weight: 600; margin-bottom: 10px; color: var(--primary);"></div>
                        <div id="combosListContainer" class="combos-list">
                            <!-- التوليفات تظهر هنا -->
                        </div>
                        <div class="combo-actions">
                            <button type="button" class="btn btn-outline" id="combosSelectAll"><i>☑️</i> تحديد الكل</button>
                            <button type="button" class="btn btn-outline" id="combosDeselectAll"><i>⬜</i> إلغاء التحديد</button>
                            <button type="button" class="btn btn-success" id="combosAddSelected"><i>🖨️</i> إضافة المحدد للطباعة</button>
                            <button type="button" class="btn btn-primary" id="combosAddAllAndPrint"><i>🖨️</i> إضافة الكل وطباعة مباشرة</button>
                        </div>
                    </div>
                </div>

                <div class="panel fade-in selected-products-panel" id="selectedProductsPanel" style="display:none;">
                    <div class="panel-header">
                        <h3><i>✅</i> المنتجات المحددة</h3>
                    </div>
                    <div class="panel-body">
                        <div class="selected-list" id="selectedProductsList">
                            <!-- المنتجات المحددة تظهر هنا -->
                        </div>
                        <div style="margin-top: 15px;">
                            <button id="clearSelection" class="btn btn-outline" style="width: 100%;">
                                <i>🗑️</i> مسح الكل
                            </button>
                        </div>
                    </div>
                </div>

                <div class="panel fade-in" style="margin-top: 25px;">
                    <div class="panel-header">
                        <h3><i>📡</i> حالة النظام</h3>
                    </div>
                    <div class="panel-body">
                        <div class="system-status">
                            <div class="status-item">
                                <div class="status-indicator status-disconnected" id="qzStatus"></div>
                                <span>اتصال QZ Tray</span>
                            </div>
                            <div class="status-item">
                                <div class="status-indicator status-disconnected" id="printerStatus"></div>
                                <span>الاتصال بالطابعة</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // بيانات التصميم من PHP
        const designData = @json($designData ?? []);
        const shopName = "{{ Auth::user()->business->name ?? 'المحل' }}";
        const productVariationsUrl = "{{ url('/print-barcode/product-variations') }}";
        const zplUrl = "{{ url('/print-barcode/zpl') }}";
        const _token = "{{ csrf_token() }}";
        const printAfterSaveProductId = {{ $print_after_save_product_id ?? 'null' }};
        const printAfterSaveAll = {{ $print_after_save_all ?? 0 }};
        const printAfterSaveProductIds = @json(isset($print_after_save_product_ids) ? $print_after_save_product_ids : []);
        const printCopiesFromCreate = {{ (int)($print_copies ?? 1) }};
        const printSendModeFromCreate = "{{ $print_send_mode ?? 'one_by_one' }}";
        const autoPrintMode = {{ isset($auto_print) && $auto_print ? 'true' : 'false' }};
        const defaultPrinterName = @json($default_printer ?? '');
// --- بداية كود الأمان المضاف ---

// 1. تحديد الخوارزمية لتطابق ملف PHP (هام جداً)
qz.security.setSignatureAlgorithm("SHA256");

// 2. وضع الشهادة — من خدمة الطباعة المركزية (نفس مصدر config/qz)
const myCertificate = `{!! \App\Services\PrintService::getQzCertificate() !!}`;

qz.security.setSignatureAlgorithm("SHA256");

qz.security.setCertificatePromise(function(resolve, reject) {
    resolve(myCertificate);
});

// 2. تحديث رابط التوقيع (يفضل استخدام Route بدلاً من ملف php مباشر)
qz.security.setSignaturePromise(function(toSign) {
    return function(resolve, reject) {
        $.ajax({
            // استخدام اسم الـ Route الذي عرفناه في Laravel
            url: "{{ route('qz.sign') }}?request=" + toSign,
            type: 'GET',
            success: function(data) {
                resolve(data);
            },
            error: function(xhr, status, error) {
                console.error("Signature Error:", error);
                reject(error);
            }
        });
    };
});
// --- نهاية كود الأمان المضاف ---
        let currentProduct = {
            id: null,
            sku: '123456789012',
            name: 'اسم المنتج',
            name_main: 'اسم المنتج',
            brand: 'علامة تجارية',
            price: '0.00',
            barcode: '123456789012',
            custom_field_1: '',
            custom_field_2: ''
        };

        let selectedProducts = new Map();

        function cleanVariationLabel(label) {
            if (!label || typeof label !== 'string') return label || '';
            return label
                .replace(/\s*اللون\s*[-–]\s*المقاس\s*[-–]?\s*/gi, '')
                .replace(/\s*المقاس\s*[-–]\s*اللون\s*[-–]?\s*/gi, '')
                .replace(/^\s*اللون\s*[-–]?\s*/gi, '')
                .replace(/\s*المقاس\s*[-–]?\s*$/gi, '')
                .replace(/\s*-\s*-\s*/g, ' - ')
                .trim();
        }
        let currentCombosProduct = null;
        let currentCombosList = [];

        // دالة تحويل المليمتر إلى بكسل
        function mmToPx(mm) {
            return mm * 3.7795275591;
        }

        // دالة إنشاء الباركود
        function generateBarcode(code, barcodeSettings) {
            if (!code) return null;
            
            const widthMm = parseFloat(barcodeSettings?.width) || 40;
            const heightMm = parseFloat(barcodeSettings?.height) || 20;
            const color = barcodeSettings?.color || '#000000';
            const fontSize = parseInt(barcodeSettings?.font_size) || 12;
            const showText = barcodeSettings?.show_text !== false;
            const type = barcodeSettings?.type || 'CODE128';
            
            const heightPx = mmToPx(heightMm);
            
            const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            
            try {
                JsBarcode(svg, code, {
                    format: type,
                    lineColor: color,
                    width: (widthMm / 40) * 1.2,
                    height: heightPx,
                    displayValue: showText,
                    font: "Arial",
                    fontSize: fontSize,
                    textMargin: 2,
                    margin: 0,
                    textAlign: 'right',
                    textPosition: 'bottom'
                });
                
                svg.style.width = mmToPx(widthMm) + 'px';
                svg.style.height = heightPx + 'px';
                
                return svg;
                
            } catch (error) {
                console.error('خطأ في إنشاء الباركود:', error);
                return null;
            }
        }

        // تهيئة الصفحة
        $(function(){
            var defW = designData.label_size && designData.label_size.width != null ? designData.label_size.width : 50;
            var defH = designData.label_size && designData.label_size.height != null ? designData.label_size.height : 25;
            $('#labelWidthMm').attr('placeholder', defW).val(defW);
            $('#labelHeightMm').attr('placeholder', defH).val(defH);
            $('#labelWidthMm, #labelHeightMm').on('input change', function(){ renderLabelPreview(); });
            $('#btnXPrinterPreset').on('click', function(){
                $('#labelWidthMm').val(50);
                $('#labelHeightMm').val(25);
                renderLabelPreview();
            });
            renderLabelPreview();
            initQZ();
            
            // الأحداث
            $('#refreshPrinters').on('click', listPrinters);
            $('#printers').on('change', function() {
                var name = $(this).val();
                if (name) {
                    $.post('{{ url("/print-barcode/save-default-printer") }}', { printer_name: name, _token: '{{ csrf_token() }}' });
                }
            });
            $('#printSingleBtn').on('click', onPrintSingle);
            $('#printSelectedBtn').on('click', onPrintSelected);
            $('#searchBtn').on('click', performSearch);
            $('#clearSelection').on('click', clearSelection);
            $('#searchInput').on('keypress', function(e){ 
                if(e.key === 'Enter') performSearch(); 
            });

            // اختيار المنتجات — إن وُجدت ألوان/مقاسات نعرضها للاختيار
            $('#productsContainer').on('click', '.product-item', function(e){
                if ($(e.target).is('input')) return;
                
                const $item = $(this);
                const productId = $item.data('id');
                const productName = $item.data('name');
                const productBrand = $item.data('brand') || '';
                const defaultBarcode = $item.data('barcode') || $item.data('sku');
                const defaultPrice = $item.data('price') || '0';

                $.get(productVariationsUrl + '/' + productId)
                    .done(function(res){
                        if (!res.success) {
                            var cf1 = ($item.data('custom-field-1') != null && $item.data('custom-field-1') !== '') ? String($item.data('custom-field-1')) : '';
                            var cf2 = ($item.data('custom-field-2') != null && $item.data('custom-field-2') !== '') ? String($item.data('custom-field-2')) : '';
                            addProductDirect($item, productId, defaultBarcode, productName, defaultPrice, productBrand, undefined, undefined, cf1, cf2);
                            return;
                        }
                        const byColor = res.by_color || [];
                        const combinations = res.combinations || [];
                        let flatList = [];
                        if (byColor && byColor.length > 0) {
                            byColor.forEach(function(g){
                                (g.sizes || []).forEach(function(s){
                                    var raw = s.label || (g.color + ' - ' + s.size);
                                    flatList.push({
                                        sub_sku: s.sub_sku,
                                        label: cleanVariationLabel(raw) || raw,
                                        sell_price_inc_tax: s.sell_price_inc_tax,
                                        variation_id: s.variation_id,
                                        custom_field_1: (g.color || '').toString().trim(),
                                        custom_field_2: (s.size || '').toString().trim()
                                    });
                                });
                            });
                        } else if (combinations && combinations.length > 0) {
                            combinations.forEach(function(c){
                                var raw = c.label || c.value || '';
                                var parts = (raw + '').split(/\s*[-–]\s*/).map(function(p){ return p.trim(); }).filter(Boolean);
                                var cf1 = (c.custom_field_1 != null && c.custom_field_1 !== '') ? String(c.custom_field_1) : '';
                                var cf2 = (c.custom_field_2 != null && c.custom_field_2 !== '') ? String(c.custom_field_2) : '';
                                if (!cf1 || !cf2) {
                                    if (parts.length >= 3) {
                                        cf1 = cf1 || (parts[parts.length - 2] || '');
                                        cf2 = cf2 || (parts[parts.length - 1] || '');
                                    } else if (parts.length === 2) {
                                        cf1 = cf1 || (parts[0] || '');
                                        cf2 = cf2 || (parts[1] || '');
                                    } else if (parts.length === 1) {
                                        cf2 = cf2 || (parts[0] || '');
                                    }
                                }
                                flatList.push({
                                    sub_sku: c.sub_sku,
                                    label: cleanVariationLabel(raw) || raw,
                                    sell_price_inc_tax: c.sell_price_inc_tax,
                                    variation_id: c.variation_id,
                                    custom_field_1: cf1,
                                    custom_field_2: cf2
                                });
                            });
                        }

                        if (flatList.length <= 1) {
                            var cf1 = flatList[0] ? (flatList[0].custom_field_1 || '').toString() : '';
                            var cf2 = flatList[0] ? (flatList[0].custom_field_2 || '').toString() : '';
                            if (!cf1 && ($item.data('custom-field-1') != null && $item.data('custom-field-1') !== '')) cf1 = String($item.data('custom-field-1'));
                            if (!cf2 && ($item.data('custom-field-2') != null && $item.data('custom-field-2') !== '')) cf2 = String($item.data('custom-field-2'));
                            if (flatList.length === 1) {
                                var lbl = (flatList[0].label || '').trim();
                                currentProduct.id = productId;
                                currentProduct.barcode = flatList[0].sub_sku;
                                currentProduct.name = lbl ? (productName + ' - ' + lbl) : productName;
                                currentProduct.name_main = productName;
                                currentProduct.custom_field_1 = cf1;
                                currentProduct.custom_field_2 = cf2;
                                currentProduct.price = (flatList[0].sell_price_inc_tax != null ? parseFloat(flatList[0].sell_price_inc_tax).toFixed(2) : '0.00');
                                currentProduct.brand = productBrand;
                                renderLabelPreview();
                            }
                            var addName = productName + (flatList[0] && flatList[0].label ? ' - ' + flatList[0].label : '');
                            addProductDirect($item, productId, flatList[0] ? flatList[0].sub_sku : defaultBarcode, addName, flatList[0] ? (flatList[0].sell_price_inc_tax != null ? parseFloat(flatList[0].sell_price_inc_tax).toFixed(2) : '0') : defaultPrice, productBrand, flatList[0] ? productId + '_' + (flatList[0].variation_id || flatList[0].sub_sku) : productId, productName, cf1, cf2);
                            return;
                        }

                        currentCombosProduct = { id: productId, name: productName, brand: productBrand };
                        currentCombosList = flatList;
                        $('#combosProductName').text(productName);
                        let html = '';
                        if (byColor && byColor.length > 0) {
                            byColor.forEach(function(g, gi){
                                html += '<div class="combos-color-group"><div class="combos-color-head">' + (g.color || '') + '</div>';
                                (g.sizes || []).forEach(function(s, si){
                                    const idx = currentCombosList.findIndex(function(x){ return x.sub_sku === s.sub_sku; });
                                    html += '<div class="combo-row" data-idx="' + idx + '"><input type="checkbox" class="combo-cb" id="cb_' + idx + '"><label for="cb_' + idx + '">' + (s.size || s.label) + ' <span class="combo-sku">' + (s.sub_sku || '') + '</span> ' + (s.sell_price_inc_tax != null ? parseFloat(s.sell_price_inc_tax).toFixed(2) + ' د.أ' : '') + '</label></div>';
                                });
                                html += '</div>';
                            });
                        } else {
                            flatList.forEach(function(s, idx){
                                html += '<div class="combo-row" data-idx="' + idx + '"><input type="checkbox" class="combo-cb" id="cb_' + idx + '"><label for="cb_' + idx + '">' + (s.label || s.sub_sku) + ' <span class="combo-sku">' + (s.sub_sku || '') + '</span> ' + (s.sell_price_inc_tax != null ? parseFloat(s.sell_price_inc_tax).toFixed(2) + ' د.أ' : '') + '</label></div>';
                            });
                        }
                        $('#combosListContainer').html(html);
                        $('#combosPanel').show();
                        // تحديث المعاينة بأول توليفة حتى يظهر الاسم واللون والمقاس فور اختيار المنتج
                        if (flatList.length > 0) {
                            var first = flatList[0];
                            currentProduct.id = productId;
                            currentProduct.barcode = first.sub_sku;
                            currentProduct.name = productName + ' - ' + (first.label || first.sub_sku);
                            currentProduct.name_main = productName;
                            currentProduct.custom_field_1 = (first.custom_field_1 || '').toString();
                            currentProduct.custom_field_2 = (first.custom_field_2 || '').toString();
                            currentProduct.price = (first.sell_price_inc_tax != null ? parseFloat(first.sell_price_inc_tax).toFixed(2) : '0.00');
                            currentProduct.brand = productBrand;
                            renderLabelPreview();
                        }
                    })
                    .fail(function(){
                        var cf1 = ($item.data('custom-field-1') != null && $item.data('custom-field-1') !== '') ? String($item.data('custom-field-1')) : '';
                        var cf2 = ($item.data('custom-field-2') != null && $item.data('custom-field-2') !== '') ? String($item.data('custom-field-2')) : '';
                        addProductDirect($item, productId, defaultBarcode, productName, defaultPrice, productBrand, undefined, undefined, cf1, cf2);
                    });
            });

            function addProductDirect($item, productId, barcode, name, price, brand, key, name_main, custom_field_1, custom_field_2) {
                key = key || productId;
                name_main = name_main != null ? name_main : name;
                custom_field_1 = custom_field_1 != null ? custom_field_1 : '';
                custom_field_2 = custom_field_2 != null ? custom_field_2 : '';
                if (selectedProducts.has(key)) {
                    $item.removeClass('selected');
                    $item.find('.quantity-control').hide();
                    selectedProducts.delete(key);
                } else {
                    $item.addClass('selected');
                    $item.find('.quantity-control').show();
                    const quantity = parseInt($item.find('.quantity-input').val()) || 1;
                    selectedProducts.set(key, {
                        id: productId,
                        sku: $item.data('sku'),
                        barcode: barcode,
                        name: name,
                        name_main: name_main,
                        price: price,
                        brand: brand || '',
                        quantity: quantity,
                        custom_field_1: custom_field_1,
                        custom_field_2: custom_field_2
                    });
                }
                updateSelectedProductsList();
                updateSelectionUI();
                currentProduct.id = productId;
                currentProduct.sku = barcode;
                currentProduct.name = name;
                currentProduct.name_main = name_main;
                currentProduct.brand = brand || currentProduct.brand;
                currentProduct.price = price;
                currentProduct.barcode = barcode;
                currentProduct.custom_field_1 = custom_field_1;
                currentProduct.custom_field_2 = custom_field_2;
                renderLabelPreview();
            }

            $('#combosSelectAll').on('click', function(){ $('#combosListContainer .combo-cb').prop('checked', true); updateCombosPreview(); });
            $('#combosDeselectAll').on('click', function(){ $('#combosListContainer .combo-cb').prop('checked', false); updateCombosPreview(); });
            $('#combosListContainer').on('change', '.combo-cb', function(){ updateCombosPreview(); });
            function updateCombosPreview() {
                if (!currentCombosProduct || !currentCombosList.length) return;
                var firstChecked = null;
                $('#combosListContainer .combo-cb:checked').each(function(){
                    const idx = parseInt($(this).closest('.combo-row').data('idx'), 10);
                    const s = currentCombosList[idx];
                    if (s && !firstChecked) firstChecked = s;
                });
                var s = firstChecked || currentCombosList[0];
                currentProduct.id = currentCombosProduct.id;
                currentProduct.barcode = s.sub_sku;
                currentProduct.name = currentCombosProduct.name + ' - ' + (s.label || s.sub_sku);
                currentProduct.name_main = currentCombosProduct.name;
                currentProduct.custom_field_1 = (s.custom_field_1 || '').toString();
                currentProduct.custom_field_2 = (s.custom_field_2 || '').toString();
                currentProduct.price = (s.sell_price_inc_tax != null ? parseFloat(s.sell_price_inc_tax).toFixed(2) : '0.00');
                currentProduct.brand = currentCombosProduct.brand || '';
                renderLabelPreview();
            }

            function addSelectedCombosToPrint(andThenPrint) {
                if (!currentCombosProduct || !currentCombosList.length) return;
                const productName = currentCombosProduct.name;
                const productBrand = currentCombosProduct.brand || '';
                const productId = currentCombosProduct.id;
                var firstAdded = null;
                var toAdd = andThenPrint ? currentCombosList : [];
                if (!andThenPrint) {
                    $('#combosListContainer .combo-cb:checked').each(function(){
                        const idx = parseInt($(this).closest('.combo-row').data('idx'), 10);
                        const s = currentCombosList[idx];
                        if (!s) return;
                        toAdd.push(s);
                    });
                }
                var copies = (typeof printCopiesFromCreate !== 'undefined' && printCopiesFromCreate > 0) ? printCopiesFromCreate : 1;
                toAdd.forEach(function(s){
                    const key = productId + '_' + (s.variation_id || s.sub_sku);
                    const item = {
                        id: productId,
                        sku: s.sub_sku,
                        barcode: s.sub_sku,
                        name: productName + ' - ' + (s.label || s.sub_sku),
                        name_main: productName,
                        price: (s.sell_price_inc_tax != null ? parseFloat(s.sell_price_inc_tax).toFixed(2) : '0.00'),
                        brand: productBrand,
                        quantity: copies,
                        custom_field_1: (s.custom_field_1 || '').toString(),
                        custom_field_2: (s.custom_field_2 || '').toString()
                    };
                    selectedProducts.set(key, item);
                    if (!firstAdded) firstAdded = item;
                });
                updateSelectedProductsList();
                updateSelectionUI();
                if (firstAdded) {
                    currentProduct.id = firstAdded.id;
                    currentProduct.sku = firstAdded.barcode;
                    currentProduct.name = firstAdded.name;
                    currentProduct.brand = firstAdded.brand || '';
                    currentProduct.price = firstAdded.price;
                    currentProduct.barcode = firstAdded.barcode;
                    currentProduct.custom_field_1 = (firstAdded.custom_field_1 != null ? firstAdded.custom_field_1 : '').toString();
                    currentProduct.custom_field_2 = (firstAdded.custom_field_2 != null ? firstAdded.custom_field_2 : '').toString();
                    renderLabelPreview();
                }
                $('#combosPanel').hide();
                $('#combosListContainer .combo-cb').prop('checked', false);
                if (andThenPrint && selectedProducts.size > 0) {
                    if (typeof printSendModeFromCreate !== 'undefined' && printSendModeFromCreate) {
                        $('#printSendMode').val(printSendModeFromCreate);
                    }
                    onPrintSelected();
                }
            }

            $('#combosAddSelected').on('click', function(){
                addSelectedCombosToPrint(false);
            });
            $('#combosAddAllAndPrint').on('click', function(){
                addSelectedCombosToPrint(true);
            });

            $('#productsContainer').on('change', '.quantity-input', function(){
                const $item = $(this).closest('.product-item');
                const productId = parseInt($item.data('id'), 10);
                const quantity = Math.max(1, Math.min(999, parseInt($(this).val(), 10) || 1));
                $(this).val(quantity);
                // تحديث الكمية لجميع العناصر المحددة التابعة لهذا المنتج (المفتاح قد يكون productId أو productId_variationId)
                selectedProducts.forEach(function(item, key) {
                    if (item.id == productId) {
                        item.quantity = quantity;
                    }
                });
                updateSelectedProductsList();
            });

            // بعد «حفظ وطباعة»: انتظار تحميل الطابعات ثم اختيار الطابعة الافتراضية وطباعة كل المقاسات
            if ((printAfterSaveProductId && printAfterSaveAll) || (printAfterSaveProductIds && printAfterSaveProductIds.length > 0)) {
                function waitForPrinters(maxMs, intervalMs) {
                    maxMs = maxMs || 6000;
                    intervalMs = intervalMs || 150;
                    return new Promise(function(resolve) {
                        var elapsed = 0;
                        var t = setInterval(function() {
                            var opts = $('#printers option');
                            if (opts.length > 1 && opts.eq(1).val()) {
                                clearInterval(t);
                                resolve(true);
                                return;
                            }
                            elapsed += intervalMs;
                            if (elapsed >= maxMs) {
                                clearInterval(t);
                                resolve(false);
                            }
                        }, intervalMs);
                    });
                }
                function setDefaultPrinter() {
                    var $sel = $('#printers');
                    if ($sel.find('option').length > 1) {
                        var hasDefault = false;
                        if (typeof defaultPrinterName === 'string' && defaultPrinterName) {
                            var defLower = defaultPrinterName.toLowerCase();
                            $sel.find('option').each(function() {
                                var v = $(this).val();
                                if (v && v.toLowerCase() === defLower) {
                                    $sel.val(v);
                                    hasDefault = true;
                                    return false;
                                }
                            });
                        }
                        if (!hasDefault) {
                            var firstVal = $sel.find('option').eq(1).val();
                            if (firstVal) $sel.val(firstVal);
                        }
                    }
                }
                function addProductToSelection(res, productId) {
                    if (!res.success) return;
                    var byColor = res.by_color || [];
                    var combinations = res.combinations || [];
                    var flatList = [];
                    if (byColor && byColor.length > 0) {
                        byColor.forEach(function(g){
                            (g.sizes || []).forEach(function(s){
                                var raw = s.label || (g.color + ' - ' + s.size);
                                flatList.push({
                                    sub_sku: s.sub_sku,
                                    label: cleanVariationLabel(raw) || raw,
                                    sell_price_inc_tax: s.sell_price_inc_tax,
                                    variation_id: s.variation_id,
                                    custom_field_1: (g.color || '').toString().trim(),
                                    custom_field_2: (s.size || '').toString().trim()
                                });
                            });
                        });
                    } else if (combinations && combinations.length > 0) {
                        combinations.forEach(function(c){
                            var raw = c.label || c.value || '';
                            var parts = (raw + '').split(/\s*[-–]\s*/).map(function(p){ return p.trim(); });
                            flatList.push({
                                sub_sku: c.sub_sku,
                                label: cleanVariationLabel(raw) || raw,
                                sell_price_inc_tax: c.sell_price_inc_tax,
                                variation_id: c.variation_id,
                                custom_field_1: parts[0] || '',
                                custom_field_2: parts[1] || ''
                            });
                        });
                    }
                    if (flatList.length === 0 && res.product) {
                        flatList.push({
                            sub_sku: res.product.sku || '',
                            label: res.product.name || '',
                            sell_price_inc_tax: 0,
                            variation_id: null,
                            custom_field_1: (res.product.custom_field_1 != null ? res.product.custom_field_1 : '').toString(),
                            custom_field_2: (res.product.custom_field_2 != null ? res.product.custom_field_2 : '').toString()
                        });
                    }
                    if (flatList.length === 0) return;
                    var productName = (res.product && res.product.name) ? res.product.name : '';
                    var productBrand = (res.product && res.product.brand) ? res.product.brand : '';
                    var pid = res.product && res.product.id ? res.product.id : productId;
                    var copies = (typeof printCopiesFromCreate !== 'undefined' && printCopiesFromCreate > 0) ? printCopiesFromCreate : 1;
                    flatList.forEach(function(s){
                        var key = pid + '_' + (s.variation_id || s.sub_sku);
                        var name = productName + (s.label ? ' - ' + s.label : '');
                        selectedProducts.set(key, {
                            id: pid,
                            sku: s.sub_sku,
                            barcode: s.sub_sku,
                            name: name,
                            name_main: productName,
                            price: (s.sell_price_inc_tax != null ? parseFloat(s.sell_price_inc_tax).toFixed(2) : '0.00'),
                            brand: productBrand,
                            quantity: copies,
                            custom_field_1: (s.custom_field_1 != null ? s.custom_field_1 : '').toString(),
                            custom_field_2: (s.custom_field_2 != null ? s.custom_field_2 : '').toString()
                        });
                    });
                }
                function runAutoPrint() {
                    // تشغيل انتظار الطابعات وجلب بيانات المنتجات بالتوازي لأقصى سرعة
                    var printersReady = waitForPrinters(5000);
                    var dataReady;
                    if (printAfterSaveProductIds && printAfterSaveProductIds.length > 0) {
                        // جلب كل المنتجات بالتوازي (طلب واحد لكل منتج في نفس الوقت)
                        dataReady = Promise.all(printAfterSaveProductIds.map(function(pid) {
                            return $.get(productVariationsUrl + '/' + pid);
                        })).then(function(results) {
                            results.forEach(function(res, i) {
                                addProductToSelection(res, printAfterSaveProductIds[i]);
                            });
                        });
                    } else {
                        dataReady = $.get(productVariationsUrl + '/' + printAfterSaveProductId);
                    }
                    Promise.all([printersReady, dataReady]).then(function(arr) {
                        setDefaultPrinter();
                        if (printAfterSaveProductIds && printAfterSaveProductIds.length > 0) {
                            if (selectedProducts.size > 0) onPrintSelected();
                            return;
                        }
                        // منتج واحد (تباينات متعددة)
                        var res = arr[1];
                        if (!res || !res.success) return;
                        var byColor = res.by_color || [];
                        var combinations = res.combinations || [];
                        var flatList = [];
                        if (byColor && byColor.length > 0) {
                            byColor.forEach(function(g){
                                (g.sizes || []).forEach(function(s){
                                    var raw = s.label || (g.color + ' - ' + s.size);
                                    flatList.push({
                                        sub_sku: s.sub_sku,
                                        label: cleanVariationLabel(raw) || raw,
                                        sell_price_inc_tax: s.sell_price_inc_tax,
                                        variation_id: s.variation_id,
                                        custom_field_1: (g.color || '').toString().trim(),
                                        custom_field_2: (s.size || '').toString().trim()
                                    });
                                });
                            });
                        } else if (combinations && combinations.length > 0) {
                            combinations.forEach(function(c){
                                var raw = c.label || c.value || '';
                                var parts = (raw + '').split(/\s*[-–]\s*/).map(function(p){ return p.trim(); });
                                flatList.push({
                                    sub_sku: c.sub_sku,
                                    label: cleanVariationLabel(raw) || raw,
                                    sell_price_inc_tax: c.sell_price_inc_tax,
                                    variation_id: c.variation_id,
                                    custom_field_1: parts[0] || '',
                                    custom_field_2: parts[1] || ''
                                });
                            });
                        }
                        if (flatList.length === 0 && res.product) {
                            flatList.push({
                                sub_sku: res.product.sku || '',
                                label: res.product.name || '',
                                sell_price_inc_tax: 0,
                                variation_id: null,
                                custom_field_1: (res.product.custom_field_1 != null ? res.product.custom_field_1 : '').toString(),
                                custom_field_2: (res.product.custom_field_2 != null ? res.product.custom_field_2 : '').toString()
                            });
                        }
                        if (flatList.length === 0) return;
                        var productName = (res.product && res.product.name) ? res.product.name : '';
                        var productBrand = '';
                        var productId = res.product && res.product.id ? res.product.id : printAfterSaveProductId;
                        currentCombosProduct = { id: productId, name: productName, brand: productBrand };
                        currentCombosList = flatList;
                        addSelectedCombosToPrint(true);
                    });
                }
                setTimeout(runAutoPrint, 80);
            }
        });

        // QZ Tray
        async function initQZ(){
            try {
                qz.api.setPromiseType(function (resolver) { return new Promise(resolver); });
                await qz.websocket.connect();
                console.log('QZ Tray متصل');
                $('#qzStatus').removeClass('status-disconnected').addClass('status-connected');
                listPrinters();
            } catch (e) {
                console.warn('لا يمكن الاتصال بـ QZ Tray:', e);
                $('#qzStatus').removeClass('status-connected').addClass('status-disconnected');
            }
        }

        async function listPrinters() {
            try {
                const printers = await qz.printers.find();
                const sel = $('#printers');
                sel.empty();
                if (!printers || printers.length === 0) {
                    sel.append($('<option/>').text('لا توجد طابعات'));
                    $('#printerStatus').removeClass('status-connected').addClass('status-disconnected');
                    return;
                }
                printers.forEach(p => sel.append($('<option/>').val(p).text(p)));
                // اختيار الطابعة الافتراضية — مطابقة بدون مراعاة حجم الحروف
                var chosen = null;
                if (typeof defaultPrinterName === 'string' && defaultPrinterName) {
                    var defLower = defaultPrinterName.toLowerCase();
                    for (var i = 0; i < printers.length; i++) {
                        if (printers[i].toLowerCase() === defLower) {
                            chosen = printers[i];
                            break;
                        }
                    }
                    if (!chosen && printers.length > 0) chosen = printers[0];
                }
                if (chosen) sel.val(chosen);
                $('#printerStatus').removeClass('status-disconnected').addClass('status-connected');
            } catch (err) {
                console.error('خطأ في جلب الطابعات:', err);
                $('#printers').empty().append($('<option/>').text('فشل جلب الطابعات'));
                $('#printerStatus').removeClass('status-connected').addClass('status-disconnected');
            }
        }

        // رسم المعاينة — تعرض فقط الاسم والباركود للعنصر المختار (ما تختاره = ما يظهر)
        function renderLabelPreview() {
            const preview = $('#labelPreview');
            preview.empty();

            const wMm = parseFloat($('#labelWidthMm').val()) || designData.label_size?.width || 50;
            const hMm = parseFloat($('#labelHeightMm').val()) || designData.label_size?.height || 25;
            preview.closest('.label-preview').css({ width: wMm + 'mm', height: hMm + 'mm' });

            const selectedName = (currentProduct.name || '').toString();
            const selectedBarcode = (currentProduct.barcode || currentProduct.sku || '').toString();

            $('#currentProductName').text(selectedName || 'لم يتم اختيار منتج');

            const elements = designData.elements || {};
            for (const key in elements) {
                const el = elements[key];
                if (!el || el.visible === false) continue;

                const left = parsePosition(el.left);
                const top = parsePosition(el.top);
                let fontSize = parseInt(el.fontSize) || 12;
                if (/name|اسم|lblName|product_name/i.test(key)) fontSize = Math.max(fontSize, 16);
                const text = substituteElementText(key, el);

                if (key === 'barcode-container' || /barcode/i.test(key)) {
                    const barcodeSvg = selectedBarcode ? generateBarcode(selectedBarcode, designData.barcode_settings) : null;
                    
                    if (barcodeSvg) {
                        const wrapper = $('<div/>').css({ 
                            position:'absolute', 
                            left:left, 
                            top:top,
                            display: 'flex',
                            justifyContent: 'center',
                            alignItems: 'center'
                        });
                        wrapper.append(barcodeSvg);
                        preview.append(wrapper);
                    }
                    continue;
                }

                const dom = $('<div/>').addClass('element').css({
                    left: left,
                    top: top,
                    'font-size': fontSize + 'px',
                    'font-family': el.fontFamily || 'Arial',
                    color: el.color || '#000'
                }).text(text);

                preview.append(dom);
            }

            // إذا التصميم ما فيه عنصر cf1/cf2 أصلاً (غير معرّف) وعندنا لون ومقاس — نعرضهم بموضع افتراضي. لو العنصر معرّف ومخفي (visible: false) لا نعرضه أبداً.
            const cf1 = (currentProduct.custom_field_1 != null ? currentProduct.custom_field_1 : '').toString().trim();
            const cf2 = (currentProduct.custom_field_2 != null ? currentProduct.custom_field_2 : '').toString().trim();
            const hasCf1Element = !!elements.cf1;
            const hasCf2Element = !!elements.cf2;
            const defLeft = '19px';
            const defFont = '13px';
            const defFamily = 'Arial';
            if (cf1 && !hasCf1Element) {
                preview.append($('<div/>').addClass('element').css({
                    left: defLeft,
                    top: '34px',
                    'font-size': defFont,
                    'font-family': defFamily,
                    color: '#000'
                }).text(cf1));
            }
            if (cf2 && !hasCf2Element) {
                preview.append($('<div/>').addClass('element').css({
                    left: defLeft,
                    top: '45px',
                    'font-size': defFont,
                    'font-family': defFamily,
                    color: '#000'
                }).text(cf2));
            }

            // العناصر الإضافية — بنفس الاسم والباركود المختار
            const extras = designData.extra_elements || {};
            for (const k in extras) {
                const el = extras[k];
                if (!el || el.visible === false) continue;
                
                const left = parsePosition(el.left);
                const top = parsePosition(el.top);
                const fontSize = parseInt(el.fontSize) || 12;
                const text = substituteElementText('extra', el);
                
                const dom = $('<div/>').addClass('element').css({
                    left: left,
                    top: top,
                    'font-size': fontSize + 'px',
                    'font-family': el.fontFamily || 'Tahoma',
                    color: el.color || '#000'
                }).text(text);
                
                preview.append(dom);
            }
        }

        function parsePosition(value){
            if (!value) return '0px';
            if (value.toString().endsWith('mm')) return value;
            if (value.toString().endsWith('px')) return value;
            if (!isNaN(parseFloat(value))) return value + 'px';
            return value;
        }

        // استبدال بيانات المنتج الحالي في النص (الاسم الرئيسي، اللون، المقاس، السعر، العلامة، الباركود)
        function substituteElementText(key, el){
            const txt = (el.text || '').toString();
            const name = (currentProduct.name || '').toString();
            const name_main = (currentProduct.name_main != null && currentProduct.name_main !== '') ? (currentProduct.name_main + '').toString() : name;
            const price = (currentProduct.price != null ? currentProduct.price : '0.00').toString();
            const brand = (currentProduct.brand || '').toString();
            const barcode = (currentProduct.barcode || currentProduct.sku || '').toString();
            const cf1 = (currentProduct.custom_field_1 != null ? currentProduct.custom_field_1 : '').toString();
            const cf2 = (currentProduct.custom_field_2 != null ? currentProduct.custom_field_2 : '').toString();

            let result = txt
                .replace(/\{\{\s*product_name\s*\}\}/gi, name_main)
                .replace(/\{\{\s*name_main\s*\}\}/gi, name_main)
                .replace(/\{\{\s*price\s*\}\}/gi, price)
                .replace(/\{\{\s*brand\s*\}\}/gi, brand)
                .replace(/\{\{\s*sku\s*\}\}/gi, barcode)
                .replace(/\{\{\s*custom_field_1\s*\}\}/gi, cf1)
                .replace(/\{\{\s*custom_field_2\s*\}\}/gi, cf2)
                .replace(/اسم المنتج/gi, name_main)
                .replace(/0\.00/gi, price)
                .replace(/Brand/gi, brand)
                .replace(/123456789012/gi, barcode);

            if (key === 'barcode-container' || /barcode/i.test(key)) return barcode || result || '';
            // عناصر اللون والمقاس: بالـ key (cf1/cf2) أو بنص العنصر (اللون، مقاس، color، size) — نعرض القيمة فقط ولا نعرض اسم المنتج أبداً
            var isColorLabel = /^cf1$/.test(key) || /لون|color/.test(txt) || /لون|color/.test(key);
            var isSizeLabel = /^cf2$/.test(key) || /مقاس|size/.test(txt) || /مقاس|size/.test(key);
            if (isColorLabel) return (cf1 && cf1.trim() !== '') ? cf1 : (txt.replace(/اسم المنتج/gi, '').replace(/\{\{\s*product_name\s*\}\}/gi, '').trim() || '');
            if (isSizeLabel) return (cf2 && cf2.trim() !== '') ? cf2 : (txt.replace(/اسم المنتج/gi, '').replace(/\{\{\s*product_name\s*\}\}/gi, '').trim() || '');
            if (/^cf(\d+)$/.test(key)) { var n = parseInt(RegExp.$1, 10); var v = (currentProduct['custom_field_' + n] != null ? currentProduct['custom_field_' + n] : '').toString().trim(); return v !== '' ? v : txt; }
            if (key === 'lblName' || (/lblName|product_name|name/i.test(key) && key !== 'lblPrice' && !/^cf\d+$/.test(key))) return name_main || result || name;
            return result || txt;
        }

        // الطباعة
        async function onPrintSingle() {
            if (!currentProduct.id) {
                alert('لم تقم باختيار منتج للطباعة');
                return;
            }
            await printProduct(currentProduct, 1);
        }

        async function onPrintSelected() {
            if (selectedProducts.size === 0) {
                alert('لم تقم باختيار أي منتجات للطباعة');
                return;
            }

            const printer = $('#printers').val();
            if (!printer) {
                alert('اختر طابعة صحيحة');
                return;
            }

            const sendMode = $('#printSendMode').val() || 'one_by_one';

            if (sendMode === 'all_at_once') {
                // طباعة مباشرة للكل — بناء HTML لكل ملصق وإرسالها في أمر واحد (تعريف الويندوز يطبعها)
                const originalProduct = {...currentProduct};
                const data = [];
                selectedProducts.forEach((product, productId) => {
                    const qty = Math.max(1, parseInt(product.quantity, 10) || 1);
                    for (let i = 0; i < qty; i++) {
                        currentProduct = {
                            id: product.id,
                            sku: product.sku || product.barcode,
                            barcode: product.barcode || product.sku || '',
                            name: (product.name || '').toString(),
                            name_main: (product.name_main != null ? product.name_main : product.name || '').toString(),
                            price: (product.price != null ? product.price : '0.00').toString(),
                            brand: (product.brand || '').toString(),
                            custom_field_1: (product.custom_field_1 != null ? product.custom_field_1 : '').toString(),
                            custom_field_2: (product.custom_field_2 != null ? product.custom_field_2 : '').toString()
                        };
                        data.push({ type: 'html', format: 'plain', data: generateLabelHTML() });
                    }
                });
                currentProduct = originalProduct;
                renderLabelPreview();
                if (data.length === 0) return;
                try {
                    const cfg = qz.configs.create(printer, {});
                    await qz.print(cfg, data);
                    if (typeof printAfterSaveProductId !== 'undefined' && printAfterSaveProductId && printAfterSaveAll) {
                        if (typeof window.close === 'function') window.close();
                    } else {
                        alert('تم الطباعة بنجاح: ' + data.length + ' ملصق (إرسال واحد)');
                    }
                } catch (err) {
                    console.error('خطأ في الطباعة:', err);
                    alert('فشل الطباعة: ' + (err?.toString?.() || err));
                }
                return;
            }

            // وحدة وحدة — كل ملصق على حدة
            let totalPrinted = 0;
            for (const [productId, product] of selectedProducts) {
                const qty = Math.max(1, parseInt(product.quantity, 10) || 1);
                for (let i = 0; i < qty; i++) {
                    await printProduct(product, 1, printer);
                    totalPrinted++;
                    await new Promise(resolve => setTimeout(resolve, 40));
                }
            }

            if (typeof printAfterSaveProductId !== 'undefined' && printAfterSaveProductId && printAfterSaveAll) {
                if (typeof window.close === 'function') window.close();
            } else {
                alert('تم الطباعة بنجاح: ' + totalPrinted + ' ملصق');
            }
        }

        async function printProduct(product, quantity, printer = null) {
            printer = printer || $('#printers').val();
            if (!printer || printer.toString().trim() === '') {
                alert('اختر طابعة صحيحة');
                return false;
            }
            if (typeof qz === 'undefined' || !qz.websocket || (qz.websocket.isConnected && !qz.websocket.isConnected())) {
                alert('غير متصل بـ QZ Tray. شغّل QZ Tray وتأكد من الاتصال.');
                return false;
            }

            const originalProduct = {...currentProduct};
            currentProduct = {
                id: product.id,
                sku: product.sku || product.barcode,
                barcode: product.barcode || product.sku || '',
                name: (product.name || '').toString(),
                name_main: (product.name_main != null ? product.name_main : product.name || '').toString(),
                price: (product.price != null ? product.price : '0.00').toString(),
                brand: (product.brand || '').toString(),
                custom_field_1: (product.custom_field_1 != null ? product.custom_field_1 : '').toString(),
                custom_field_2: (product.custom_field_2 != null ? product.custom_field_2 : '').toString()
            };

            const previewHTML = generateLabelHTML();

            try {
                const cfg = qz.configs.create(printer, {});
                await qz.print(cfg, [{ type: 'html', format: 'plain', data: previewHTML }]);
                currentProduct = originalProduct;
                renderLabelPreview();
                return true;
            } catch (err) {
                console.error('خطأ في الطباعة:', err);
                alert('فشل الطباعة: ' + (err?.toString?.() || err));
                currentProduct = originalProduct;
                renderLabelPreview();
                return false;
            }
        }

        // تحويل px إلى pt للطابعة — الطابعات الخارجية تتعامل أفضل مع pt فيظهر الاسم والسعر
        function pxToPt(px) {
            const n = parseInt(px, 10) || 12;
            return Math.max(9, Math.round(n * 72 / 96)) + 'pt';
        }

        // الملصق يعرض الاسم الرئيسي واللون والمقاس والباركود حسب التصميم
        function generateLabelHTML() {
            const selectedName = (currentProduct.name || '').toString();
            const selectedNameMain = (currentProduct.name_main != null && currentProduct.name_main !== '' ? currentProduct.name_main : selectedName).toString();
            const selectedPrice = (currentProduct.price != null ? currentProduct.price : '0.00').toString();
            const selectedBarcode = (currentProduct.barcode || currentProduct.sku || '').toString();

            const tempDiv = $('<div/>').addClass('label-content').css({
                width: '100%',
                height: '100%',
                position: 'relative'
            });

            let hasNameElement = false;
            let hasPriceElement = false;
            const elements = designData.elements || {};
            for (const key in elements) {
                const el = elements[key];
                if (!el || el.visible === false) continue;

                const left = parsePosition(el.left);
                const top = parsePosition(el.top);
                let fontSizePx = parseInt(el.fontSize) || 12;
                const text = substituteElementText(key, el);

                if (key === 'barcode-container' || /barcode/i.test(key)) {
                    const barcodeSvg = selectedBarcode ? generateBarcode(selectedBarcode, designData.barcode_settings) : null;
                    
                    if (barcodeSvg) {
                        const wrapper = $('<div/>').css({ 
                            position:'absolute', 
                            left:left, 
                            top:top,
                            display: 'flex',
                            justifyContent: 'center',
                            alignItems: 'center'
                        });
                        wrapper.append(barcodeSvg);
                        tempDiv.append(wrapper);
                    }
                    continue;
                }

                if ((key === 'lblName' || (/name|اسم|product_name/i.test(key) && !/^cf\d+$/.test(key)))) {
                    hasNameElement = true;
                    fontSizePx = Math.max(fontSizePx, 18);
                }
                if (/price|سعر|lblPrice/i.test(key)) {
                    hasPriceElement = true;
                    fontSizePx = Math.max(fontSizePx, 14);
                }
                let finalText = text;
                if ((key === 'lblName' || (/name|اسم|product_name/i.test(key) && !/^cf\d+$/.test(key))) && !finalText) finalText = selectedNameMain;
                else if ((key === 'lblPrice' || /price|سعر/i.test(key)) && !finalText) finalText = selectedPrice;
                else if (/^cf\d+$/.test(key)) finalText = text;
                if (!finalText) finalText = '';
                const fontSizePt = pxToPt(fontSizePx);
                const dom = $('<div/>').addClass('element label-text').attr('data-print', '1').css({
                    left: left,
                    top: top,
                    'font-size': fontSizePt,
                    'font-weight': (key === 'lblName' || (/name|اسم|product_name/i.test(key) && !/^cf\d+$/.test(key))) ? 'bold' : 'normal',
                    'font-family': (el.fontFamily || 'Arial').split(',')[0].trim(),
                    color: el.color || '#000000'
                }).text(finalText || (key === 'lblName' || (/name|اسم|product_name/i.test(key) && !/^cf\d+$/.test(key)) ? selectedNameMain : (key === 'lblPrice' || /price|سعر/i.test(key) ? selectedPrice : (/^cf\d+$/.test(key) ? text : ''))));
                tempDiv.append(dom);
            }

            if (!hasNameElement && selectedNameMain) {
                tempDiv.prepend($('<div/>').addClass('element label-text label-name').attr('data-print', '1').css({
                    left: '5px',
                    top: '5px',
                    'font-size': '14pt',
                    'font-weight': 'bold',
                    'font-family': 'Arial',
                    color: '#000000'
                }).text(selectedNameMain));
            }
            if (!hasPriceElement && selectedPrice) {
                tempDiv.append($('<div/>').addClass('element label-text label-price').attr('data-print', '1').css({
                    left: '5px',
                    top: '45px',
                    'font-size': '12pt',
                    'font-weight': 'bold',
                    'font-family': 'Arial',
                    color: '#000000'
                }).text(selectedPrice));
            }
            var printCf1 = (currentProduct.custom_field_1 != null ? currentProduct.custom_field_1 : '').toString().trim();
            var printCf2 = (currentProduct.custom_field_2 != null ? currentProduct.custom_field_2 : '').toString().trim();
            var hasCf1Element = !!elements.cf1;
            var hasCf2Element = !!elements.cf2;
            if (printCf1 && !hasCf1Element) {
                tempDiv.append($('<div/>').addClass('element label-text').attr('data-print', '1').css({
                    left: '19px', top: '34px', 'font-size': '10pt', 'font-family': 'Arial', color: '#000000'
                }).text(printCf1));
            }
            if (printCf2 && !hasCf2Element) {
                tempDiv.append($('<div/>').addClass('element label-text').attr('data-print', '1').css({
                    left: '19px', top: '45px', 'font-size': '10pt', 'font-family': 'Arial', color: '#000000'
                }).text(printCf2));
            }

            const extras = designData.extra_elements || {};
            for (const k in extras) {
                const el = extras[k];
                if (!el || el.visible === false) continue;

                const left = parsePosition(el.left);
                const top = parsePosition(el.top);
                const fontSizePx = Math.max(parseInt(el.fontSize) || 12, 11);
                const text = substituteElementText('extra', el);
                const fontSizePt = pxToPt(fontSizePx);
                const dom = $('<div/>').addClass('element label-text').attr('data-print', '1').css({
                    left: left,
                    top: top,
                    'font-size': fontSizePt,
                    'font-family': (el.fontFamily || 'Tahoma').split(',')[0].trim(),
                    color: el.color || '#000000'
                }).text(text);
                tempDiv.append(dom);
            }

            const wMm = parseFloat($('#labelWidthMm').val()) || designData.label_size?.width || 50;
            const hMm = parseFloat($('#labelHeightMm').val()) || designData.label_size?.height || 25;
            const html = `
                <!DOCTYPE html>
                <html>
                    <head>
                        <meta charset="utf-8">
                        <style>
                            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                            body { 
                                font-family: Arial, Tahoma, sans-serif; 
                                margin: 0; 
                                padding: 0; 
                                width: ${wMm}mm; 
                                height: ${hMm}mm;
                                font-size: 12pt;
                                color: #000000;
                            }
                            .label-content { 
                                width: 100%; 
                                height: 100%; 
                                position: relative;
                                overflow: visible;
                            }
                            .element { 
                                position: absolute; 
                                white-space: nowrap; 
                                overflow: visible;
                                font-size: 12pt;
                                color: #000000;
                            }
                            .label-text, .label-name, .label-price {
                                font-size: 12pt !important;
                                color: #000000 !important;
                            }
                            .label-name { font-size: 14pt !important; font-weight: bold !important; }
                            .label-price { font-size: 12pt !important; font-weight: bold !important; }
                            svg { display: block !important; }
                        </style>
                    </head>
                    <body>
                        <div class="label-content">
                            ${tempDiv.html()}
                        </div>
                    </body>
                </html>
            `;

            return html;
        }

        // إدارة المنتجات المختارة — النقر على عنصر يحدّث المعاينة؛ حقل الكمية يحدد كم مرة يُرسل للطابعة
        function updateSelectedProductsList() {
            const container = $('#selectedProductsList');
            container.empty();
            
            let totalLabels = 0;
            
            selectedProducts.forEach((product, productId) => {
                const qty = Math.max(1, parseInt(product.quantity, 10) || 1);
                product.quantity = qty;
                totalLabels += qty;
                const keyEsc = (productId + '').replace(/"/g, '&quot;');
                const item = $(`
                    <div class="selected-item" data-key="${keyEsc}" style="cursor: pointer;" title="اضغط لمعاينة الملصق">
                        <div class="selected-item-info">
                            <div class="selected-item-name">${(product.name || '').replace(/</g, '&lt;')}</div>
                            <div class="selected-item-qty">
                                <label style="font-size:12px; margin:0;">عدد الطباعة:</label>
                                <input type="number" class="selected-qty-input" data-key="${keyEsc}" value="${qty}" min="1" max="999" title="كم مرة يُرسل هذا الملصق للطابعة">
                            </div>
                        </div>
                        <button class="btn-remove" data-id="${keyEsc}">✕</button>
                    </div>
                `);
                container.append(item);
            });
            
            $('#selectedCount').text(totalLabels);
        }

        function updateSelectionUI() {
            const hasSelection = selectedProducts.size > 0;
            $('#selectedProductsPanel').toggle(hasSelection);
        }

        function clearSelection() {
            selectedProducts.clear();
            $('.product-item').removeClass('selected').find('.quantity-control').hide();
            updateSelectedProductsList();
            updateSelectionUI();
        }

        $('#selectedProductsList').on('change', '.selected-qty-input', function(e) {
            e.stopPropagation();
            const key = $(this).data('key');
            const product = selectedProducts.get(key);
            if (product) {
                const val = Math.max(1, Math.min(999, parseInt($(this).val(), 10) || 1));
                product.quantity = val;
                $(this).val(val);
                let total = 0;
                selectedProducts.forEach(function(p){ total += p.quantity; });
                $('#selectedCount').text(total);
            }
        });
        $('#selectedProductsList').on('click', '.selected-item', function(e) {
            if ($(e.target).hasClass('btn-remove') || $(e.target).closest('.btn-remove').length) return;
            if ($(e.target).hasClass('selected-qty-input') || $(e.target).closest('.selected-qty-input').length) return;
            const key = $(this).data('key');
            const product = selectedProducts.get(key);
            if (product) {
                currentProduct.id = product.id;
                currentProduct.sku = product.barcode;
                currentProduct.name = product.name;
                currentProduct.name_main = product.name_main != null ? product.name_main : product.name;
                currentProduct.brand = product.brand || '';
                currentProduct.price = product.price;
                currentProduct.barcode = product.barcode;
                currentProduct.custom_field_1 = (product.custom_field_1 != null ? product.custom_field_1 : '').toString();
                currentProduct.custom_field_2 = (product.custom_field_2 != null ? product.custom_field_2 : '').toString();
                renderLabelPreview();
            }
        });
        $('#selectedProductsList').on('click', '.btn-remove', function(e) {
            e.stopPropagation();
            const productId = $(this).data('id');
            selectedProducts.delete(productId);
            const numId = (productId + '').split('_')[0];
            $(`.product-item[data-id="${numId}"]`).removeClass('selected').find('.quantity-control').hide();
            updateSelectedProductsList();
            updateSelectionUI();
            if (selectedProducts.size > 0) {
                const first = selectedProducts.values().next().value;
                if (first) {
                    currentProduct.id = first.id;
                    currentProduct.sku = first.barcode;
                    currentProduct.name = first.name;
                    currentProduct.brand = first.brand || '';
                    currentProduct.price = first.price;
                    currentProduct.barcode = first.barcode;
                    renderLabelPreview();
                }
            } else {
                currentProduct.name = 'لم يتم اختيار منتج';
                renderLabelPreview();
            }
        });

        // البحث
        function performSearch() {
            const q = $('#searchInput').val().trim();
            
            if (q.length < 2) {
                $('#productsContainer').html('<div style="text-align: center; padding: 20px; color: var(--gray);">أدخل 2 حروف على الأقل</div>');
                return;
            }
            
            $.ajax({
                url: "{{ route('barcode.search') }}",
                method: 'GET',
                data: { 
                    search: q,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    $('#productsContainer').html('<div style="text-align: center; padding: 20px; color: var(--gray);"><i>⏳</i><p>جاري البحث...</p></div>');
                },
                success: function(response) {
                    $('#productsContainer').html($(response).find('#productsContainer').html());
                    // إعادة تطبيق التحديد على المنتجات
                    selectedProducts.forEach((product, productId) => {
                        $(`.product-item[data-id="${productId}"]`).addClass('selected').find('.quantity-control').show();
                    });
                },
                error: function(err) { 
                    console.error('خطأ في البحث:', err); 
                    $('#productsContainer').html('<div style="text-align: center; padding: 20px; color: var(--gray);"><i>❌</i><p>حدث خطأ في البحث</p></div>');
                }
            });
        }
    </script>


@endsection