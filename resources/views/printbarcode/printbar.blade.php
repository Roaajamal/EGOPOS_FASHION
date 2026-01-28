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
        }

        .selected-item:last-child {
            border-bottom: none;
        }

        .selected-item-info {
            display: flex;
            flex-direction: column;
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
                                        $barcode = $variation->sub_sku ?: $p->sku;
                                        $price = $variation->sell_price_inc_tax ?? $variation->default_sell_price ?? 0;
                                    @endphp
                                    <div class="product-item" 
                                         data-id="{{ $p->id }}" 
                                         data-sku="{{ $p->sku }}" 
                                         data-barcode="{{ $barcode }}"
                                         data-name="{{ $p->name }}" 
                                         data-price="{{ $price }}" 
                                         data-brand="{{ optional($p->brand)->name ?? '' }}">
                                        <div class="product-info">
                                            <h4>{{ $p->name }}</h4>
                                            <div class="product-meta">
                                                <span>SKU: {{ $p->sku }}</span>
                                                <span>السعر: {{ number_format($price, 2) }}</span>
                                            </div>
                                            <div class="quantity-control" style="display:none;">
                                                <label>الكمية:</label>
                                                <input type="number" class="quantity-input" value="1" min="1" max="100">
                                            </div>
                                        </div>
                                        <div class="product-badge">
                                            <i>🏷️</i>
                                        </div>
                                    </div>
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
// --- بداية كود الأمان المضاف ---

// 1. تحديد الخوارزمية لتطابق ملف PHP (هام جداً)
qz.security.setSignatureAlgorithm("SHA256");

// 2. وضع الشهادة (تأكد أنها الشهادة الجديدة التي ولّدتها للـ Wildcard)
// 1. استدعاء الشهادة من الـ Config
const myCertificate = `{!! config('qz.certificate') !!}`;

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
            brand: 'علامة تجارية',
            price: '0.00',
            barcode: '123456789012'
        };

        let selectedProducts = new Map();

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
                    margin: 0
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
            renderLabelPreview();
            initQZ();
            
            // الأحداث
            $('#refreshPrinters').on('click', listPrinters);
            $('#printSingleBtn').on('click', onPrintSingle);
            $('#printSelectedBtn').on('click', onPrintSelected);
            $('#searchBtn').on('click', performSearch);
            $('#clearSelection').on('click', clearSelection);
            $('#searchInput').on('keypress', function(e){ 
                if(e.key === 'Enter') performSearch(); 
            });

            // اختيار المنتجات
            $('#productsContainer').on('click', '.product-item', function(e){
                if ($(e.target).is('input')) return;
                
                const $item = $(this);
                const productId = $item.data('id');
                
                if ($item.hasClass('selected')) {
                    $item.removeClass('selected');
                    $item.find('.quantity-control').hide();
                    selectedProducts.delete(productId);
                } else {
                    $item.addClass('selected');
                    $item.find('.quantity-control').show();
                    const quantity = parseInt($item.find('.quantity-input').val()) || 1;
                    selectedProducts.set(productId, {
                        id: productId,
                        sku: $item.data('sku'),
                        barcode: $item.data('barcode'),
                        name: $item.data('name'),
                        price: $item.data('price'),
                        brand: $item.data('brand'),
                        quantity: quantity
                    });
                }
                
                updateSelectedProductsList();
                updateSelectionUI();
                
                if (!$(e.target).is('.quantity-input')) {
                    currentProduct.id = $item.data('id');
                    currentProduct.sku = $item.data('barcode') || $item.data('sku');
                    currentProduct.name = $item.data('name');
                    currentProduct.brand = $item.data('brand') || currentProduct.brand;
                    currentProduct.price = $item.data('price') ?? currentProduct.price;
                    currentProduct.barcode = $item.data('barcode') || $item.data('sku');
                    renderLabelPreview();
                }
            });

            $('#productsContainer').on('change', '.quantity-input', function(){
                const $item = $(this).closest('.product-item');
                const productId = $item.data('id');
                const quantity = parseInt($(this).val()) || 1;
                
                if (selectedProducts.has(productId)) {
                    selectedProducts.get(productId).quantity = quantity;
                    updateSelectedProductsList();
                }
            });
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
                $('#printerStatus').removeClass('status-disconnected').addClass('status-connected');
            } catch (err) {
                console.error('خطأ في جلب الطابعات:', err);
                $('#printers').empty().append($('<option/>').text('فشل جلب الطابعات'));
                $('#printerStatus').removeClass('status-connected').addClass('status-disconnected');
            }
        }

        // رسم المعاينة
        function renderLabelPreview() {
            const preview = $('#labelPreview');
            preview.empty();

            $('#currentProductName').text(currentProduct.name || 'لم يتم اختيار منتج');

            const elements = designData.elements || {};
            for (const key in elements) {
                const el = elements[key];
                if (!el || el.visible === false) continue;

                const left = parsePosition(el.left);
                const top = parsePosition(el.top);
                const fontSize = parseInt(el.fontSize) || 12;
                const text = substituteElementText(key, el);

                if (key === 'barcode-container' || /barcode/i.test(key)) {
                    const barcodeSvg = generateBarcode(currentProduct.barcode, designData.barcode_settings);
                    
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

            // العناصر الإضافية
            const extras = designData.extra_elements || {};
            for (const k in extras) {
                const el = extras[k];
                if (!el || el.visible === false) continue;
                
                const left = parsePosition(el.left);
                const top = parsePosition(el.top);
                const fontSize = parseInt(el.fontSize) || 12;
                const text = el.text || '';
                
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

        function substituteElementText(key, el){
            const txt = (el.text || '').toString();
            
            let result = txt
                .replace(/اسم المنتج/gi, currentProduct.name || '')
                .replace(/0\.00/gi, currentProduct.price || '0.00')
                .replace(/Brand/gi, currentProduct.brand || '')
                .replace(/123456789012/gi, currentProduct.barcode || '');
                
            return result || (key === 'barcode-container' ? currentProduct.barcode : txt);
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

            let totalPrinted = 0;
            
            for (const [productId, product] of selectedProducts) {
                for (let i = 0; i < product.quantity; i++) {
                    await printProduct(product, 1, printer);
                    totalPrinted++;
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
            }

            alert('تم الطباعة بنجاح: ' + totalPrinted + ' ملصق');
        }

        async function printProduct(product, quantity, printer = null) {
            printer = printer || $('#printers').val();
            
            if (!printer) {
                alert('اختر طابعة صحيحة');
                return false;
            }

            const originalProduct = {...currentProduct};
            currentProduct = {...product};
            
            const previewHTML = generateLabelHTML();
            
            try {
                const cfg = qz.configs.create(printer, {});
                const data = [{ type:'html', format:'plain', data: previewHTML }];
                await qz.print(cfg, data);
                
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

        function generateLabelHTML() {
            const tempDiv = $('<div/>').addClass('label-content').css({
                width: '100%',
                height: '100%',
                position: 'relative'
            });

            const elements = designData.elements || {};
            for (const key in elements) {
                const el = elements[key];
                if (!el || el.visible === false) continue;

                const left = parsePosition(el.left);
                const top = parsePosition(el.top);
                const fontSize = parseInt(el.fontSize) || 12;
                const text = substituteElementText(key, el);

                if (key === 'barcode-container' || /barcode/i.test(key)) {
                    const barcodeSvg = generateBarcode(currentProduct.barcode, designData.barcode_settings);
                    
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

                const dom = $('<div/>').addClass('element').css({
                    left:left, 
                    top:top, 
                    'font-size': fontSize + 'px', 
                    'font-family': el.fontFamily || 'Arial', 
                    color: el.color || '#000'
                }).text(text);
                tempDiv.append(dom);
            }

            const extras = designData.extra_elements || {};
            for (const k in extras) {
                const el = extras[k];
                if (!el || el.visible === false) continue;
                
                const left = parsePosition(el.left);
                const top = parsePosition(el.top);
                const fontSize = parseInt(el.fontSize) || 12;
                const text = el.text || '';
                
                const dom = $('<div/>').addClass('element').css({
                    left: left,
                    top: top,
                    'font-size': fontSize + 'px',
                    'font-family': el.fontFamily || 'Tahoma',
                    color: el.color || '#000'
                }).text(text);
                
                tempDiv.append(dom);
            }

            const html = `
                <!DOCTYPE html>
                <html>
                    <head>
                        <meta charset="utf-8">
                        <style>
                            body { 
                                font-family: Tahoma, Arial; 
                                margin: 0; 
                                padding: 0; 
                                width: ${designData.label_size?.width || 50}mm; 
                                height: ${designData.label_size?.height || 25}mm;
                            }
                            .label-content { 
                                width: 100%; 
                                height: 100%; 
                                position: relative;
                            }
                            .element { 
                                position: absolute; 
                                white-space: nowrap; 
                            }
                            svg {
                                display: block;
                            }
                        </style>
                    </head>
                    <body>
                        ${tempDiv.html()}
                    </body>
                </html>
            `;

            return html;
        }

        // إدارة المنتجات المختارة
        function updateSelectedProductsList() {
            const container = $('#selectedProductsList');
            container.empty();
            
            let totalLabels = 0;
            
            selectedProducts.forEach((product, productId) => {
                totalLabels += product.quantity;
                const item = $(`
                    <div class="selected-item">
                        <div class="selected-item-info">
                            <div class="selected-item-name">${product.name}</div>
                            <div class="selected-item-meta">الكمية: ${product.quantity}</div>
                        </div>
                        <button class="btn-remove" data-id="${productId}">✕</button>
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

        $('#selectedProductsList').on('click', '.btn-remove', function() {
            const productId = $(this).data('id');
            selectedProducts.delete(productId);
            $(`.product-item[data-id="${productId}"]`).removeClass('selected').find('.quantity-control').hide();
            updateSelectedProductsList();
            updateSelectionUI();
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