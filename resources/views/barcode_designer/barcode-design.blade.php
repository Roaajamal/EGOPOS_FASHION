@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* تحسينات التصميم */
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    user-select: none; 
    margin: 0;
    padding: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    overflow-x: hidden;
}

.editor-container { 
    display: flex; 
    gap: 20px; 
    padding: 15px;
    flex-wrap: nowrap;
    max-width: 100%;
    margin: 0 auto;
    height: calc(100vh - 80px);
    overflow: hidden;
}

.control-panel {
    background: rgba(255,255,255,0.95);
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    width: 380px;
    min-width: 380px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    height: 100%;
    overflow-y: auto;
}

.design-area {
    flex: 1;
    background: rgba(255,255,255,0.95);
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    height: 100%;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.label-area {
    border: 2px dashed #007bff;
    background: #fff;
    position: relative;
    overflow: hidden;
    min-height: 150px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    transform-origin: top left;
    transition: all 0.3s ease;
    margin: 0 auto;
}

.label-area:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.draggable {
    position: absolute;
    cursor: move;
    user-select: none;
    padding: 6px 12px;
    white-space: nowrap;
    border: 1px solid transparent;
    transition: all 0.3s ease;
    border-radius: 4px;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(10px);
    min-width: 60px;
    text-align: center;
}

.draggable:hover {
    border: 1px dashed #007bff;
    background: rgba(0,123,255,0.1);
    transform: scale(1.02);
}

.draggable:active {
    cursor: grabbing;
}

.extra-item {
    background: #f8f9fa;
    padding: 12px;
    margin-bottom: 12px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.extra-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.hidden-element {
    opacity: 0.3;
    background-color: #ffe6e6 !important;
    border: 1px dashed #dc3545 !important;
}

.btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    padding: 10px 16px;
    font-size: 14px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.btn-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
.btn-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
.btn-secondary { background: linear-gradient(135deg, #6c757d, #545b62); }
.btn-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
.btn-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
.btn-info { background: linear-gradient(135deg, #17a2b8, #138496); }

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    margin: 2px;
}

.toggle-btn {
    margin-top: 5px;
    font-size: 11px;
    padding: 5px 10px;
}

.control-group {
    background: rgba(248,249,250,0.8);
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 10px;
    border-left: 4px solid #007bff;
    transition: all 0.3s ease;
}

.control-group:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.control-group h5 {
    margin: 0 0 12px 0;
    color: #2c3e50;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.control-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.control-header h5 {
    margin: 0;
    color: #2c3e50;
    font-size: 15px;
}

.form-control, .form-select {
    margin-bottom: 10px;
    padding: 10px 12px;
    width: 100%;
    box-sizing: border-box;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 14px;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    outline: none;
}

label {
    font-weight: 600;
    margin-top: 8px;
    display: block;
    color: #495057;
    font-size: 13px;
    margin-bottom: 4px;
}

.actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 20px;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px 10px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.action-btn:hover {
    border-color: #007bff;
    background: #f8f9ff;
    transform: translateY(-2px);
}

.action-btn span {
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    margin-top: 5px;
}

.barcode-container {
    background: white;
    padding: 6px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: inline-block;
}

.scale-ruler {
    position: absolute;
    bottom: -25px;
    left: 0;
    width: 100%;
    height: 20px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    padding: 3px 8px;
    font-size: 10px;
    color: #6c757d;
    border-radius: 0 0 6px 6px;
}

.dpi-info {
    background: linear-gradient(135deg, #e9ecef, #dee2e6);
    padding: 10px 12px;
    border-radius: 8px;
    margin-top: 12px;
    font-size: 12px;
    color: #495057;
    border-left: 3px solid #007bff;
}

.zoom-controls {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.zoom-btn {
    padding: 8px 12px;
    border: 2px solid #dee2e6;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: 12px;
    flex: 1;
}

.zoom-btn:hover {
    background: #f8f9fa;
}

.barcode-size-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 12px;
}

.size-preview {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 8px;
    border-radius: 6px;
    text-align: center;
    font-size: 11px;
    margin-top: 6px;
    border: 1px dashed #dee2e6;
    font-weight: 600;
}

.stats-panel {
    background: rgba(255,255,255,0.9);
    padding: 12px;
    border-radius: 8px;
    margin-top: 12px;
    border-left: 3px solid #28a745;
}

.stats-panel h6 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 13px;
}

.auto-save-status {
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    margin-top: 10px;
    text-align: center;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.designs-list {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px;
    background: white;
}

.design-item {
    padding: 8px;
    border-bottom: 1px solid #f1f1f1;
    cursor: pointer;
    transition: all 0.3s ease;
}

.design-item:hover {
    background: #f8f9fa;
}

.design-item:last-child {
    border-bottom: none;
}

.design-item.active {
    background: #007bff;
    color: white;
}

/* شريط التمرير المخصص */
.control-panel::-webkit-scrollbar {
    width: 6px;
}

.control-panel::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.control-panel::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.control-panel::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.design-area::-webkit-scrollbar {
    width: 6px;
}

.design-area::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.design-area::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.design-area::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* تحسينات للعرض على شاشات صغيرة */
@media (max-height: 800px) {
    .editor-container {
        height: calc(100vh - 60px);
    }
    
    .control-panel {
        padding: 15px;
    }
    
    .design-area {
        padding: 15px;
    }
}

/* تأثيرات للعناصر النشطة */
.active-control {
    border-left-color: #28a745 !important;
    background: rgba(40, 167, 69, 0.05) !important;
}

.save-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 10px 15px;
    background: #28a745;
    color: white;
    border-radius: 5px;
    font-weight: bold;
    z-index: 10000;
    transform: translateX(200px);
    transition: transform 0.3s ease;
}

.save-indicator.show {
    transform: translateX(0);
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    z-index: 10000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateX(400px);
    transition: transform 0.3s ease;
}

.notification.show {
    transform: translateX(0);
}

.notification.success { background: #28a745; }
.notification.error { background: #dc3545; }
.notification.warning { background: #ffc107; color: #212529; }
.notification.info { background: #17a2b8; }
</style>

<div class="editor-container">
    <!-- لوحة التحكم -->
    <div class="control-panel" id="controlPanel">
        <div style="text-align: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #2c3e50; display: flex; align-items: center; justify-content: center; gap: 10px;">
                🎨 مصمم الباركود الاحترافي
            </h3>
            <p style="color: #6c757d; margin: 5px 0 0 0; font-size: 13px;">صمم وادمج واطبع ملصقات الباركود</p>
        </div>

        <!-- إدارة التصاميم -->
        <div class="control-group">
            <h5>📁 إدارة التصاميم</h5>
            <label>اسم التصميم:</label>
            <input type="text" class="form-control" id="templateName" value="تصميم مخصص" placeholder="أدخل اسم التصميم">
            
            <div class="zoom-controls">
                <button class="zoom-btn" onclick="changeZoom(0.7)">🔍 تصغير</button>
                <button class="zoom-btn" onclick="changeZoom(1)">🔍 100%</button>
                <button class="zoom-btn" onclick="changeZoom(1.5)">🔍 تكبير</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px;">
                <button class="btn btn-info btn-sm" onclick="testConnection()">🧪 اختبار الاتصال</button>
                <button class="btn btn-warning btn-sm" onclick="loadUserDesigns()">📂 التصاميم</button>
            </div>
        </div>

        <!-- حجم الملصق -->
        <div class="control-group">
            <h5>📏 حجم الملصق</h5>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label>العرض (مم):</label>
                    <input type="number" class="form-control" id="labelWidth" value="50" oninput="updateLabelSize()" min="10" max="200">
                </div>
                <div>
                    <label>الارتفاع (مم):</label>
                    <input type="number" class="form-control" id="labelHeight" value="25" oninput="updateLabelSize()" min="10" max="200">
                </div>
            </div>
            
            <div class="dpi-info">
                <strong>معلومات الدقة:</strong><br>
                <span id="dpiInfo">1mm = 3.78px (96 DPI)</span>
            </div>
        </div>

        <!-- اسم المنتج -->
        <div class="control-group" data-element="lblName">
            <div class="control-header">
                <h5>🏷️ اسم المنتج</h5>
                <button class="btn btn-sm toggle-btn btn-secondary" onclick="toggleElement('lblName')">👁️ إخفاء</button>
            </div>
            <input type="text" class="form-control" id="txtName" value="اسم المنتج" oninput="updateItem('lblName')" placeholder="أدخل اسم المنتج">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div>
                    <label>📍 X (مم):</label>
                    <input type="number" id="xName" class="form-control" value="5" oninput="updatePosition('lblName')" step="0.1">
                </div>
                <div>
                    <label>📍 Y (مم):</label>
                    <input type="number" id="yName" class="form-control" value="5" oninput="updatePosition('lblName')" step="0.1">
                </div>
            </div>
            <label>🔤 حجم الخط (pt):</label>
            <input type="number" id="sizeName" value="12" class="form-control" oninput="updateFont('lblName')" min="6" max="72">
            <label>📝 نوع الخط:</label>
            <select id="fontNameName" class="form-select" onchange="updateFont('lblName')">
                <option value="Arial">Arial</option>
                <option value="Tahoma">Tahoma</option>
                <option value="Cairo">Cairo</option>
            </select>
        </div>

        <!-- السعر -->
        <div class="control-group" data-element="lblPrice">
            <div class="control-header">
                <h5>💰 السعر</h5>
                <button class="btn btn-sm toggle-btn btn-secondary" onclick="toggleElement('lblPrice')">👁️ إخفاء</button>
            </div>
            <input type="text" class="form-control" id="txtPrice" value="0.00" oninput="updateItem('lblPrice')" placeholder="أدخل السعر">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div>
                    <label>📍 X (مم):</label>
                    <input type="number" id="xPrice" class="form-control" value="5" oninput="updatePosition('lblPrice')" step="0.1">
                </div>
                <div>
                    <label>📍 Y (مم):</label>
                    <input type="number" id="yPrice" class="form-control" value="15" oninput="updatePosition('lblPrice')" step="0.1">
                </div>
            </div>
            <label>🔤 حجم الخط (pt):</label>
            <input type="number" id="sizePrice" value="12" class="form-control" oninput="updateFont('lblPrice')" min="6" max="72">
            <label>📝 نوع الخط:</label>
            <select id="fontNamePrice" class="form-select" onchange="updateFont('lblPrice')">
                <option value="Arial">Arial</option>
                <option value="Tahoma">Tahoma</option>
                <option value="Cairo">Cairo</option>
            </select>
        </div>

        <!-- البراند -->
        <div class="control-group" data-element="lblBrand">
            <div class="control-header">
                <h5>🏭 البراند</h5>
                <button class="btn btn-sm toggle-btn btn-secondary" onclick="toggleElement('lblBrand')">👁️ إخفاء</button>
            </div>
            <input type="text" class="form-control" id="txtBrand" value="Brand" oninput="updateItem('lblBrand')" placeholder="أدخل اسم البراند">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div>
                    <label>📍 X (مم):</label>
                    <input type="number" id="xBrand" class="form-control" value="30" oninput="updatePosition('lblBrand')" step="0.1">
                </div>
                <div>
                    <label>📍 Y (مم):</label>
                    <input type="number" id="yBrand" class="form-control" value="5" oninput="updatePosition('lblBrand')" step="0.1">
                </div>
            </div>
            <label>🔤 حجم الخط (pt):</label>
            <input type="number" id="sizeBrand" value="10" class="form-control" oninput="updateFont('lblBrand')" min="6" max="72">
            <label>📝 نوع الخط:</label>
            <select id="fontNameBrand" class="form-select" onchange="updateFont('lblBrand')">
                <option value="Arial">Arial</option>
                <option value="Tahoma">Tahoma</option>
                <option value="Cairo">Cairo</option>
            </select>
        </div>

        <!-- الباركود -->
        <div class="control-group" data-element="barcode">
            <div class="control-header">
                <h5>📊 الباركود</h5>
                <button class="btn btn-sm toggle-btn btn-secondary" onclick="toggleElement('barcode')">👁️ إخفاء</button>
            </div>
            
            <label>🔢 كود الباركود:</label>
            <input type="text" class="form-control" id="txtBarcode" value="123456789012" oninput="updateBarcode()" placeholder="أدخل كود الباركود">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div>
                    <label>📍 X (مم):</label>
                    <input type="number" id="xBarcode" class="form-control" value="5" oninput="updateBarcodePosition()" step="0.1">
                </div>
                <div>
                    <label>📍 Y (مم):</label>
                    <input type="number" id="yBarcode" class="form-control" value="8" oninput="updateBarcodePosition()" step="0.1">
                </div>
            </div>

            <div class="barcode-size-controls">
                <div>
                    <label>📏 العرض (مم):</label>
                    <input type="number" id="barcodeWidth" class="form-control" value="40" oninput="updateBarcodeSize()" min="10" max="100" step="0.1">
                </div>
                <div>
                    <label>📏 الارتفاع (مم):</label>
                    <input type="number" id="barcodeHeight" class="form-control" value="15" oninput="updateBarcodeSize()" min="5" max="50" step="0.1">
                </div>
            </div>
            
            <div class="size-preview">
                الحجم: <span id="barcodeSizePreview">40×15 مم</span>
            </div>

            <label>🎨 لون الباركود:</label>
            <input type="color" id="barcodeColor" class="form-control" value="#000000" oninput="updateBarcodeStyle()">
            
            <label>🔤 حجم نص الباركود (pt):</label>
            <input type="number" id="barcodeFontSize" class="form-control" value="10" oninput="updateBarcodeStyle()" min="6" max="20">
            
            <label>📝 إظهار النص:</label>
            <select id="barcodeShowText" class="form-select" onchange="updateBarcodeStyle()">
                <option value="true">👁️ إظهار</option>
                <option value="false">🙈 إخفاء</option>
            </select>

            <label>🔣 نوع الباركود:</label>
            <select id="barcodeType" class="form-select" onchange="updateBarcodeType()">
                <option value="CODE128">CODE128</option>
                <option value="EAN13">EAN-13</option>
                <option value="EAN8">EAN-8</option>
                <option value="CODE39">CODE39</option>
            </select>
        </div>

        <!-- العناصر الإضافية -->
        <div class="control-group">
            <h5>✨ العناصر الإضافية</h5>
            <div id="extraList"></div>
            <button class="btn btn-info w-100 mt-2" onclick="addExtra()">
                ➕ إضافة عنصر جديد
            </button>
        </div>

        <!-- إعدادات الطباعة -->
        <div class="control-group">
            <h5>🖨️ إعدادات الطباعة</h5>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div>
                    <label>هامش أعلى (مم):</label>
                    <input type="number" id="marginTop" class="form-control" value="0" min="0" max="20" step="0.1">
                </div>
                <div>
                    <label>هامش أسفل (مم):</label>
                    <input type="number" id="marginBottom" class="form-control" value="0" min="0" max="20" step="0.1">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                <div>
                    <label>هامش يسار (مم):</label>
                    <input type="number" id="marginLeft" class="form-control" value="0" min="0" max="20" step="0.1">
                </div>
                <div>
                    <label>هامش يمين (مم):</label>
                    <input type="number" id="marginRight" class="form-control" value="0" min="0" max="20" step="0.1">
                </div>
            </div>
            <label>📄 اتجاه الورق:</label>
            <select id="pageOrientation" class="form-select">
                <option value="portrait">عمودي</option>
                <option value="landscape">أفقي</option>
            </select>
        </div>

        <!-- لوحة الإحصائيات -->
        <div class="stats-panel">
            <h6>📊 إحصائيات التصميم</h6>
            <div style="font-size: 12px; color: #6c757d;">
                <div>عدد العناصر: <span id="elementCount">4</span></div>
                <div>الحجم: <span id="currentSize">50×25 مم</span></div>
                <div>آخر تحديث: <span id="lastUpdate">الآن</span></div>
                <div>الإصدار: <span id="designVersion">2.0</span></div>
            </div>
        </div>

        <!-- أزرار الإجراءات -->
        <div class="actions-grid">
            <div class="action-btn" onclick="showPreview()">
                <span>👁️</span>
                <span>معاينة</span>
            </div>
            <div class="action-btn" onclick="printLabel()">
                <span>🖨️</span>
                <span>طباعة</span>
            </div>
            <div class="action-btn" onclick="saveDesignToDB()">
                <span>💾</span>
                <span>حفظ</span>
            </div>
            <div class="action-btn" onclick="loadDesignFromDB()">
                <span>📂</span>
                <span>فتح</span>
            </div>
            <div class="action-btn" onclick="exportDesign()">
                <span>📤</span>
                <span>تصدير</span>
            </div>
            <div class="action-btn" onclick="duplicateDesign()">
                <span>📋</span>
                <span>نسخ</span>
            </div>
        </div>

        <!-- حالة الحفظ التلقائي -->
        <div id="autoSaveStatus" class="auto-save-status" style="display: none;">
            ✅ تم الحفظ تلقائياً
        </div>
    </div>

    <!-- منطقة التصميم -->
    <div class="design-area">
        <h4 style="margin: 0 0 15px 0; color: #2c3e50; display: flex; align-items: center; gap: 8px;">
            🎯 منطقة التصميم <small id="zoomLevel" style="font-size: 13px; color: #6c757d;">(100%)</small>
        </h4>
        
        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 400px;">
            <div style="position: relative; display: inline-block;">
                <div id="label" class="label-area" style="width:189px; height:94px; transform: scale(1);">
                    <div id="lblName" class="draggable" style="top:19px; left:19px; font-size:16px; font-family:Arial;">اسم المنتج</div>
                    <div id="lblPrice" class="draggable" style="top:57px; left:19px; font-size:16px; font-family:Arial;">0.00</div>
                    <div id="lblBrand" class="draggable" style="top:19px; left:113px; font-size:13px; font-family:Arial;">Brand</div>
                    <div id="barcode-container" class="draggable barcode-container" style="top:76px; left:19px;">
                        <svg id="barcode"></svg>
                    </div>
                </div>
                <div id="scale-ruler" class="scale-ruler">
                    <span>0mm</span>
                    <span>25mm</span>
                    <span>50mm</span>
                </div>
            </div>
        </div>

        <!-- قائمة التصاميم -->
        <div id="designsList" class="designs-list" style="display: none; margin-top: 20px;">
            <h6>📂 التصاميم المحفوظة</h6>
            <div id="designsContainer"></div>
        </div>

        <!-- المعاينة -->
        <div id="preview-area" style="display:none; margin-top:20px;">
            <h5>👁️ المعاينة</h5>
            <div id="previewBox"></div>
            <button class="btn btn-secondary mt-3" onclick="closePreview()">إغلاق المعاينة</button>
        </div>
    </div>
</div>

<!-- مؤشر الحفظ -->
<div id="saveIndicator" class="save-indicator">💾 جاري الحفظ...</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
const BASE_URL = '{{ url("/") }}';
const SAVE_URL = '{{ route("barcode.save") }}';      // /barcode-save
const LOAD_URL = '{{ route("barcode.load") }}';      // /barcode-load  
const TEST_URL = '{{ route("barcode.test") }}';      // /barcode-test
const DELETE_URL = '{{ route("barcode.delete") }}';  // /barcode-delete
const CSRF_TOKEN = '{{ csrf_token() }}';

console.log('🔗 الروابط المستخدمة:', {
    SAVE_URL,
    LOAD_URL, 
    TEST_URL,
    DELETE_URL
});

// ثوابت التحويل
const MM_TO_PX = 3.7795275591;

// حالة العناصر
const elementStates = {
    'lblName': true,
    'lblPrice': true,
    'lblBrand': true,
    'barcode': true
};

let extraCount = 0;
let currentZoom = 1;
let lastSaveTime = new Date();
let currentDesignId = null;
let userDesigns = [];

// تهيئة أولية
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 بدء تحميل مصمم الباركود...');
    console.log('🔗 URLs:', { BASE_URL, SAVE_URL, LOAD_URL });
    
    initDraggables();
    updateBarcode();
    updateScaleRuler();
    updateStats();
    
    // تحميل التصميم المحفوظ تلقائياً
    setTimeout(() => {
        loadDesignFromDB();
    }, 1000);
    
    console.log('✅ مصمم الباركود جاهز للاستخدام');
});

/* ========== نظام التحويل ========== */
function mmToPx(mm) {
    return Math.round(mm * MM_TO_PX);
}

function pxToMm(px) {
    return (px / MM_TO_PX).toFixed(1);
}

/* ========== إدارة حجم الملصق ========== */
function updateLabelSize(){
    const widthMm = parseInt(document.getElementById('labelWidth').value);
    const heightMm = parseInt(document.getElementById('labelHeight').value);
    
    const widthPx = mmToPx(widthMm);
    const heightPx = mmToPx(heightMm);
    
    const label = document.getElementById('label');
    label.style.width = widthPx + "px";
    label.style.height = heightPx + "px";
    
    applyZoom();
    updateScaleRuler();
    updateStats();
    
    document.getElementById('dpiInfo').textContent = `1mm = ${MM_TO_PX.toFixed(2)}px (96 DPI)`;
    
    autoSave();
}

function updateScaleRuler() {
    const widthMm = parseInt(document.getElementById('labelWidth').value);
    const ruler = document.getElementById('scale-ruler');
    
    ruler.innerHTML = `
        <span>0mm</span>
        <span>${Math.floor(widthMm/2)}mm</span>
        <span>${widthMm}mm</span>
    `;
}

/* ========== نظام التكبير/التصغير ========== */
function changeZoom(zoomLevel) {
    currentZoom = zoomLevel;
    applyZoom();
    document.getElementById('zoomLevel').textContent = `(${Math.round(zoomLevel * 100)}%)`;
}

function applyZoom() {
    const label = document.getElementById('label');
    label.style.transform = `scale(${currentZoom})`;
}

/* ========== إدارة العناصر ========== */
function updateItem(id){
    let value = document.getElementById("txt" + id.replace("lbl", "")).value;
    document.getElementById(id).innerText = value;
    updateStats();
    autoSave();
}

function updatePosition(id){
    let base = id.replace("lbl","");
    let xMm = parseFloat(document.getElementById("x" + base).value);
    let yMm = parseFloat(document.getElementById("y" + base).value);
    
    let xPx = mmToPx(xMm);
    let yPx = mmToPx(yMm);
    
    let el = document.getElementById(id);
    el.style.left = xPx + "px";
    el.style.top = yPx + "px";
    updateStats();
    autoSave();
}

function updateFont(id){
    let base = id.replace("lbl","");
    let el = document.getElementById(id);
    let size = document.getElementById("size" + base).value;
    let font = document.getElementById("fontName" + base).value;
    
    let sizePx = Math.round(size * 1.33);
    el.style.fontSize = sizePx + "px";
    el.style.fontFamily = font;
    autoSave();
}

/* ========== نظام الباركود ========== */
function updateBarcode(){
    const code = document.getElementById('txtBarcode').value;
    if (!code) return;
    
    const widthMm = parseFloat(document.getElementById('barcodeWidth').value);
    const heightMm = parseFloat(document.getElementById('barcodeHeight').value);
    const color = document.getElementById('barcodeColor').value;
    const fontSize = parseInt(document.getElementById('barcodeFontSize').value);
    const showText = document.getElementById('barcodeShowText').value === 'true';
    const type = document.getElementById('barcodeType').value;
    
    const heightPx = mmToPx(heightMm);
    
    document.getElementById('barcodeSizePreview').textContent = `${widthMm}×${heightMm} مم`;
    
    try {
        JsBarcode("#barcode", code, {
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
        
        const barcodeContainer = document.getElementById('barcode-container');
        barcodeContainer.style.width = mmToPx(widthMm) + 'px';
        
    } catch (error) {
        console.error('❌ خطأ في إنشاء الباركود:', error);
    }
    
    autoSave();
}

function updateBarcodeSize() {
    updateBarcode();
}

function updateBarcodePosition(){
    let xMm = parseFloat(document.getElementById('xBarcode').value);
    let yMm = parseFloat(document.getElementById('yBarcode').value);
    
    let xPx = mmToPx(xMm);
    let yPx = mmToPx(yMm);
    
    let el = document.getElementById("barcode-container");
    el.style.left = xPx + "px";
    el.style.top = yPx + "px";
    autoSave();
}

function updateBarcodeStyle() {
    updateBarcode();
}

function updateBarcodeType() {
    const type = document.getElementById('barcodeType').value;
    const barcodeInput = document.getElementById('txtBarcode');
    
    switch(type) {
        case 'EAN13':
            barcodeInput.placeholder = '13 رقم (1234567890123)';
            break;
        case 'EAN8':
            barcodeInput.placeholder = '8 أرقام (12345678)';
            break;
        case 'CODE39':
            barcodeInput.placeholder = 'أرقام وحروف (ABC123)';
            break;
        default:
            barcodeInput.placeholder = 'أدخل كود الباركود';
    }
    updateBarcode();
}

/* ========== العناصر الإضافية ========== */
function addExtra(){
    extraCount++;
    let id = "extra" + extraCount;
    
    elementStates[id] = true;
    
    let el = document.createElement("div");
    el.id = id;
    el.className = "draggable";
    el.style.top = mmToPx(10) + "px";
    el.style.left = mmToPx(10) + "px";
    el.style.fontSize = "14px";
    el.style.fontFamily = "Arial";
    el.innerText = "نص جديد";
    document.getElementById("label").appendChild(el);
    makeDraggable(el);

    let box = document.createElement("div");
    box.className = "extra-item";
    box.innerHTML = `
        <div class="control-header">
            <b>✨ ${id}</b>
            <button class="btn btn-sm toggle-btn btn-secondary" onclick="toggleElement('${id}')">👁️ إخفاء</button>
        </div>
        نص:<input class="form-control" id="txt_${id}" value="نص جديد" oninput="updateExtraText('${id}')">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
            <div>
                <label>📍 X (مم):</label>
                <input class="form-control" id="x_${id}" value="10" oninput="updateExtraPos('${id}')" step="0.1">
            </div>
            <div>
                <label>📍 Y (مم):</label>
                <input class="form-control" id="y_${id}" value="10" oninput="updateExtraPos('${id}')" step="0.1">
            </div>
        </div>
        <label>🔤 حجم الخط (pt):</label>
        <input class="form-control" id="size_${id}" value="10" oninput="updateExtraFont('${id}')">
        <label>📝 نوع الخط:</label>
        <select id="font_${id}" class="form-select" onchange="updateExtraFont('${id}')">
            <option value="Arial">Arial</option>
            <option value="Tahoma">Tahoma</option>
            <option value="Cairo">Cairo</option>
        </select>
        <button class="btn btn-danger btn-sm w-100 mt-2" onclick="removeExtra('${id}')">🗑️ حذف</button>
    `;
    document.getElementById("extraList").appendChild(box);
    updateStats();
    autoSave();
}

function removeExtra(id){
    document.getElementById(id).remove();
    delete elementStates[id];
    const extraItem = document.querySelector(`[id^="txt_${id}"]`)?.closest('.extra-item');
    if (extraItem) extraItem.remove();
    updateStats();
    autoSave();
}

function updateExtraText(id){
    document.getElementById(id).innerText = document.getElementById("txt_" + id).value;
    updateStats();
    autoSave();
}

function updateExtraPos(id){
    let xMm = parseFloat(document.getElementById("x_" + id).value);
    let yMm = parseFloat(document.getElementById("y_" + id).value);
    
    let xPx = mmToPx(xMm);
    let yPx = mmToPx(yMm);
    
    let el = document.getElementById(id);
    el.style.left = xPx + "px";
    el.style.top = yPx + "px";
    updateStats();
    autoSave();
}

function updateExtraFont(id){
    let el = document.getElementById(id);
    let size = document.getElementById("size_" + id).value;
    let font = document.getElementById("font_" + id).value;
    
    let sizePx = Math.round(size * 1.33);
    el.style.fontSize = sizePx + "px";
    el.style.fontFamily = font;
    autoSave();
}

/* ========== نظام السحب والإفلات ========== */
function makeDraggable(element) {
    let isDragging = false;
    let startX, startY, initialX, initialY;
    
    element.addEventListener('mousedown', startDrag);
    
    function startDrag(e) {
        e.preventDefault();
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        
        const style = window.getComputedStyle(element);
        initialX = parseInt(style.left) || 0;
        initialY = parseInt(style.top) || 0;
        
        element.style.zIndex = '1000';
        element.style.cursor = 'grabbing';
        
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);
    }
    
    function drag(e) {
        if (!isDragging) return;
        
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        
        updateElementPosition(element, initialX + dx, initialY + dy);
    }
    
    function stopDrag() {
        isDragging = false;
        element.style.zIndex = '';
        element.style.cursor = 'move';
        
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', stopDrag);
        
        updateInputFieldsAfterDrag(element.id, parseInt(element.style.left), parseInt(element.style.top));
        updateStats();
        autoSave();
    }
}

function updateElementPosition(element, x, y) {
    const label = document.getElementById('label');
    const maxX = label.offsetWidth - element.offsetWidth;
    const maxY = label.offsetHeight - element.offsetHeight;
    
    const newX = Math.max(0, Math.min(x, maxX));
    const newY = Math.max(0, Math.min(y, maxY));
    
    element.style.left = newX + 'px';
    element.style.top = newY + 'px';
}

function updateInputFieldsAfterDrag(elementId, xPx, yPx) {
    const xMm = pxToMm(xPx);
    const yMm = pxToMm(yPx);
    
    if (elementId === 'barcode-container') {
        document.getElementById('xBarcode').value = xMm;
        document.getElementById('yBarcode').value = yMm;
    } else if (elementId.startsWith('extra')) {
        document.getElementById(`x_${elementId}`).value = xMm;
        document.getElementById(`y_${elementId}`).value = yMm;
    } else {
        const base = elementId.replace('lbl', '');
        document.getElementById(`x${base}`).value = xMm;
        document.getElementById(`y${base}`).value = yMm;
    }
}

function initDraggables() {
    document.querySelectorAll('.draggable').forEach(el => {
        makeDraggable(el);
    });
}

/* ========== إخفاء/إظهار العناصر ========== */
function toggleElement(elementId) {
    const element = document.getElementById(elementId === 'barcode' ? 'barcode-container' : elementId);
    const button = document.querySelector(`[onclick="toggleElement('${elementId}')"]`);
    
    elementStates[elementId] = !elementStates[elementId];
    
    if (elementStates[elementId]) {
        element.classList.remove('hidden-element');
        button.innerHTML = '👁️ إخفاء';
        button.classList.remove('btn-warning');
        button.classList.add('btn-secondary');
    } else {
        element.classList.add('hidden-element');
        button.innerHTML = '👁️ إظهار';
        button.classList.remove('btn-secondary');
        button.classList.add('btn-warning');
    }
    updateStats();
    autoSave();
}

/* ========== المعاينة ========== */
function showPreview(){
    let clone = document.getElementById('label').cloneNode(true);
    clone.querySelectorAll('.hidden-element').forEach(el => el.remove());
    
    document.getElementById('previewBox').innerHTML = '';
    document.getElementById('previewBox').appendChild(clone);
    document.getElementById('preview-area').style.display = "block";
    
    // التمرير للمعاينة تلقائياً
    document.getElementById('preview-area').scrollIntoView({ behavior: 'smooth' });
}

function closePreview(){
    document.getElementById('preview-area').style.display = "none";
}

/* ========== نظام الحفظ والتحميل ========== */
async function saveDesignToDB(){
    showSaveIndicator();
    
    try {
        console.log('💾 بدء عملية الحفظ...');
        
        const designData = {
            label_size: {
                width: document.getElementById('labelWidth').value,
                height: document.getElementById('labelHeight').value,
                unit: 'mm',
                dpi: 96
            },
            elements: {},
            extra_elements: {},
            barcode_settings: {
                width: document.getElementById('barcodeWidth').value,
                height: document.getElementById('barcodeHeight').value,
                color: document.getElementById('barcodeColor').value,
                type: document.getElementById('barcodeType').value,
                show_text: document.getElementById('barcodeShowText').value,
                font_size: document.getElementById('barcodeFontSize').value
            },
            design_metadata: {
                version: '2.0',
                created_by: {{ Auth::id() ?? 'null' }},
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
                total_elements: 0,
                template_name: document.getElementById('templateName').value || 'تصميم مخصص'
            },
            print_settings: {
                margin_top: document.getElementById('marginTop').value,
                margin_bottom: document.getElementById('marginBottom').value,
                margin_left: document.getElementById('marginLeft').value,
                margin_right: document.getElementById('marginRight').value,
                orientation: document.getElementById('pageOrientation').value,
                paper_type: 'label'
            },
            saved_at: new Date().toISOString(),
            user_id: {{ Auth::id() ?? 'null' }}
        };

        // جمع بيانات العناصر الأساسية
        const basicElements = ['lblName', 'lblPrice', 'lblBrand', 'barcode-container'];
        basicElements.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                designData.elements[id] = {
                    text: id === 'barcode-container' ? document.getElementById('txtBarcode').value : el.innerText,
                    left: el.style.left,
                    top: el.style.top,
                    fontSize: el.style.fontSize,
                    fontFamily: el.style.fontFamily,
                    color: el.style.color || '#000000',
                    visible: elementStates[id.replace('-container', '')] !== false
                };
            }
        });

        // جمع بيانات العناصر الإضافية
        document.querySelectorAll('.draggable').forEach(el => {
            if (el.id.startsWith('extra')) {
                designData.extra_elements[el.id] = {
                    text: el.innerText,
                    left: el.style.left,
                    top: el.style.top,
                    fontSize: el.style.fontSize,
                    fontFamily: el.style.fontFamily,
                    color: el.style.color || '#000000',
                    visible: elementStates[el.id] !== false
                };
            }
        });

        // حساب إجمالي العناصر
        designData.design_metadata.total_elements = 
            Object.keys(designData.elements).length + 
            Object.keys(designData.extra_elements).length;

        console.log('📦 البيانات المرسلة:', designData);

        const response = await fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify(designData)
        });

        console.log('📨 استجابة الخادم:', response.status);

        if (!response.ok) {
            throw new Error(`خطأ في الخادم: ${response.status}`);
        }

        const result = await response.json();
        console.log('🎯 نتيجة الحفظ:', result);
        
        if (result.success) {
            showNotification('✅ تم حفظ التصميم بنجاح', 'success');
            lastSaveTime = new Date();
            updateLastUpdateTime();
            currentDesignId = result.design_id;
            
            // تحديث الإصدار
            document.getElementById('designVersion').textContent = '2.0';
        } else {
            showNotification('❌ ' + result.message, 'error');
        }

    } catch (error) {
        console.error('❌ خطأ في الحفظ:', error);
        showNotification('❌ حدث خطأ أثناء الحفظ: ' + error.message, 'error');
    } finally {
        hideSaveIndicator();
    }
}

async function loadDesignFromDB(){
    try {
        console.log('📂 جاري تحميل التصميم...');
        
        const response = await fetch(LOAD_URL, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`خطأ في الخادم: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.design) {
            applyDesign(result.design);
            currentDesignId = result.design_id;
            showNotification('✅ تم تحميل التصميم بنجاح', 'success');
        } else {
            console.log('ℹ️ لا يوجد تصميم محفوظ:', result.message);
        }

    } catch (error) {
        console.error('❌ خطأ في التحميل:', error);
        showNotification('❌ حدث خطأ أثناء التحميل: ' + error.message, 'error');
    }
}

function applyDesign(design) {
    console.log('🎨 تطبيق التصميم المحمل:', design);
    
    if (!design) return;

    try {
        // تطبيق حجم الملصق
        if (design.label_size) {
            document.getElementById('labelWidth').value = design.label_size.width || 50;
            document.getElementById('labelHeight').value = design.label_size.height || 25;
            updateLabelSize();
        }

        // تطبيق إعدادات الباركود
        if (design.barcode_settings) {
            document.getElementById('barcodeWidth').value = design.barcode_settings.width || 40;
            document.getElementById('barcodeHeight').value = design.barcode_settings.height || 15;
            document.getElementById('barcodeColor').value = design.barcode_settings.color || '#000000';
            document.getElementById('barcodeType').value = design.barcode_settings.type || 'CODE128';
            document.getElementById('barcodeShowText').value = design.barcode_settings.show_text || 'true';
            document.getElementById('barcodeFontSize').value = design.barcode_settings.font_size || 10;
        }

        // تطبيق العناصر الأساسية
        if (design.elements) {
            Object.keys(design.elements).forEach(id => {
                const elementData = design.elements[id];
                const el = document.getElementById(id);
                
                if (el) {
                    if (id === 'barcode-container') {
                        document.getElementById('txtBarcode').value = elementData.text || '123456789012';
                    } else {
                        el.innerText = elementData.text || '';
                        document.getElementById(`txt${id.replace('lbl', '')}`).value = elementData.text || '';
                    }
                    
                    el.style.left = elementData.left || '10px';
                    el.style.top = elementData.top || '10px';
                    el.style.fontSize = elementData.fontSize || '16px';
                    el.style.fontFamily = elementData.fontFamily || 'Arial';
                    el.style.color = elementData.color || '#000000';

                    // تحديث حقول الإدخال
                    if (id !== 'barcode-container') {
                        const base = id.replace('lbl', '');
                        const leftMm = pxToMm(parseInt(elementData.left));
                        const topMm = pxToMm(parseInt(elementData.top));
                        
                        document.getElementById(`x${base}`).value = leftMm || 5;
                        document.getElementById(`y${base}`).value = topMm || 5;
                        document.getElementById(`size${base}`).value = parseInt(elementData.fontSize) / 1.33 || 12;
                        document.getElementById(`fontName${base}`).value = elementData.fontFamily || 'Arial';
                    } else {
                        const leftMm = pxToMm(parseInt(elementData.left));
                        const topMm = pxToMm(parseInt(elementData.top));
                        document.getElementById('xBarcode').value = leftMm || 5;
                        document.getElementById('yBarcode').value = topMm || 8;
                    }

                    if (elementData.visible === false) {
                        toggleElement(id.replace('-container', ''));
                    }
                }
            });
        }

        // تطبيق العناصر الإضافية
        if (design.extra_elements) {
            Object.keys(design.extra_elements).forEach(id => {
                const elementData = design.extra_elements[id];
                createExtraElementFromLoad(id, elementData);
            });
        }

        // تطبيق إعدادات الطباعة
        if (design.print_settings) {
            document.getElementById('marginTop').value = design.print_settings.margin_top || 0;
            document.getElementById('marginBottom').value = design.print_settings.margin_bottom || 0;
            document.getElementById('marginLeft').value = design.print_settings.margin_left || 0;
            document.getElementById('marginRight').value = design.print_settings.margin_right || 0;
            document.getElementById('pageOrientation').value = design.print_settings.orientation || 'portrait';
        }

        // تطبيق metadata
        if (design.design_metadata) {
            document.getElementById('templateName').value = design.design_metadata.template_name || 'تصميم مخصص';
            document.getElementById('designVersion').textContent = design.design_metadata.version || '2.0';
        }
        
        // تحديث الباركود بعد تحميل كل الإعدادات
        updateBarcode();
        updateStats();
        
    } catch (error) {
        console.error('❌ خطأ في تطبيق التصميم:', error);
    }
}

function createExtraElementFromLoad(id, elementData) {
    const match = id.match(/extra(\d+)/);
    if (match) {
        extraCount = Math.max(extraCount, parseInt(match[1]));
    }
    
    elementStates[id] = elementData.visible !== false;

    let el = document.createElement("div");
    el.id = id;
    el.className = "draggable";
    el.style.left = elementData.left || "20px";
    el.style.top = elementData.top || "130px";
    el.style.fontSize = elementData.fontSize || "14px";
    el.style.fontFamily = elementData.fontFamily || "Arial";
    el.style.color = elementData.color || "#000000";
    el.innerText = elementData.text || "نص جديد";
    document.getElementById("label").appendChild(el);
    makeDraggable(el);

    let box = document.createElement("div");
    box.className = "extra-item";
    box.innerHTML = `
        <div class="control-header">
            <b>✨ ${id}</b>
            <button class="btn btn-sm toggle-btn ${elementData.visible === false ? 'btn-warning' : 'btn-secondary'}" onclick="toggleElement('${id}')">👁️ ${elementData.visible === false ? 'إظهار' : 'إخفاء'}</button>
        </div>
        نص:<input class="form-control" id="txt_${id}" value="${elementData.text || 'نص جديد'}" oninput="updateExtraText('${id}')">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
            <div>
                <label>📍 X (مم):</label>
                <input class="form-control" id="x_${id}" value="${pxToMm(parseInt(elementData.left)) || 10}" oninput="updateExtraPos('${id}')" step="0.1">
            </div>
            <div>
                <label>📍 Y (مم):</label>
                <input class="form-control" id="y_${id}" value="${pxToMm(parseInt(elementData.top)) || 10}" oninput="updateExtraPos('${id}')" step="0.1">
            </div>
        </div>
        <label>🔤 حجم الخط (pt):</label>
        <input class="form-control" id="size_${id}" value="${parseInt(elementData.fontSize) / 1.33 || 10}" oninput="updateExtraFont('${id}')">
        <label>📝 نوع الخط:</label>
        <select id="font_${id}" class="form-select" onchange="updateExtraFont('${id}')">
            <option value="Arial" ${(elementData.fontFamily || 'Arial') === 'Arial' ? 'selected' : ''}>Arial</option>
            <option value="Tahoma" ${(elementData.fontFamily || 'Arial') === 'Tahoma' ? 'selected' : ''}>Tahoma</option>
            <option value="Cairo" ${(elementData.fontFamily || 'Arial') === 'Cairo' ? 'selected' : ''}>Cairo</option>
        </select>
        <button class="btn btn-danger btn-sm w-100 mt-2" onclick="removeExtra('${id}')">🗑️ حذف</button>
    `;
    document.getElementById("extraList").appendChild(box);

    if (elementData.visible === false) {
        const element = document.getElementById(id);
        element.classList.add('hidden-element');
    }
}

/* ========== إدارة التصاميم ========== */
async function loadUserDesigns() {
    try {
        const response = await fetch(DESIGNS_URL);
        const result = await response.json();

        if (result.success) {
            userDesigns = result.designs;
            showDesignsList();
        } else {
            showNotification('❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('❌ خطأ في جلب التصاميم:', error);
        showNotification('❌ حدث خطأ في جلب التصاميم', 'error');
    }
}

function showDesignsList() {
    const container = document.getElementById('designsContainer');
    const list = document.getElementById('designsList');
    
    if (userDesigns.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #6c757d; font-size: 12px;">لا توجد تصاميم محفوظة</p>';
    } else {
        container.innerHTML = userDesigns.map(design => `
            <div class="design-item" onclick="loadDesignById(${design.id})">
                <strong>${design.name}</strong>
                <div style="font-size: 11px; color: #6c757d;">
                    ${design.label_size} • ${design.total_elements} عناصر
                </div>
                <div style="font-size: 10px; color: #999;">
                    ${new Date(design.updated_at).toLocaleDateString('ar-EG')}
                </div>
            </div>
        `).join('');
    }
    
    list.style.display = 'block';
    list.scrollIntoView({ behavior: 'smooth' });
}

async function loadDesignById(designId) {
    try {
        // هنا يمكن إضافة API لتحميل تصميم محدد
        showNotification('⏳ جاري تحميل التصميم...', 'info');
    } catch (error) {
        console.error('❌ خطأ في تحميل التصميم:', error);
        showNotification('❌ حدث خطأ في تحميل التصميم', 'error');
    }
}

async function duplicateDesign() {
    if (!currentDesignId) {
        showNotification('❌ لا يوجد تصميم نشط للنسخ', 'error');
        return;
    }

    try {
        const response = await fetch(DUPLICATE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ design_id: currentDesignId })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('✅ تم نسخ التصميم بنجاح', 'success');
            document.getElementById('templateName').value = result.template_name;
        } else {
            showNotification('❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('❌ خطأ في نسخ التصميم:', error);
        showNotification('❌ حدث خطأ في نسخ التصميم', 'error');
    }
}

async function exportDesign() {
    try {
        if (!currentDesignId) {
            showNotification('❌ لا يوجد تصميم نشط للتصدير', 'error');
            return;
        }

        const response = await fetch(EXPORT_URL + '?design_id=' + currentDesignId);
        const result = await response.json();

        if (result.success) {
            // إنشاء ملف JSON للتحميل
            const dataStr = JSON.stringify(result.design, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = result.file_name;
            link.click();
            URL.revokeObjectURL(url);
            
            showNotification('✅ تم تصدير التصميم بنجاح', 'success');
        } else {
            showNotification('❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('❌ خطأ في تصدير التصميم:', error);
        showNotification('❌ حدث خطأ في تصدير التصميم', 'error');
    }
}

/* ========== الطباعة ========== */
function printLabel() {
    const printContent = document.getElementById('label').cloneNode(true);
    printContent.querySelectorAll('.hidden-element').forEach(el => el.remove());
    
    const marginTop = document.getElementById('marginTop').value;
    const marginBottom = document.getElementById('marginBottom').value;
    const marginLeft = document.getElementById('marginLeft').value;
    const marginRight = document.getElementById('marginRight').value;
    const orientation = document.getElementById('pageOrientation').value;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>طباعة الملصق</title>
                <style>
                    @media print {
                        @page {
                            size: ${orientation};
                            margin: ${marginTop}mm ${marginRight}mm ${marginBottom}mm ${marginLeft}mm;
                        }
                        body { 
                            margin: 0; 
                            padding: 0; 
                        }
                        .label-area { 
                            border: none; 
                            box-shadow: none;
                        }
                    }
                    @media screen {
                        body { 
                            margin: 20px; 
                            padding: 20px; 
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            background: #f5f5f5;
                        }
                        .label-area { 
                            border: 1px solid #000; 
                            background: white;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        }
                    }
                </style>
            </head>
            <body>${printContent.outerHTML}</body>
        </html>
    `);
    printWindow.document.close();
    
    setTimeout(() => {
        printWindow.print();
        setTimeout(() => {
            printWindow.close();
        }, 500);
    }, 1000);
}

/* ========== وظائف مساعدة ========== */
function updateStats() {
    const elements = document.querySelectorAll('.draggable:not(.hidden-element)').length;
    const width = document.getElementById('labelWidth').value;
    const height = document.getElementById('labelHeight').value;
    
    document.getElementById('elementCount').textContent = elements;
    document.getElementById('currentSize').textContent = `${width}×${height} مم`;
}

function updateLastUpdateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('ar-EG');
    document.getElementById('lastUpdate').textContent = timeString;
}

function autoSave() {
    // حفظ تلقائي بعد 3 ثواني من آخر تعديل
    clearTimeout(window.autoSaveTimeout);
    window.autoSaveTimeout = setTimeout(() => {
        if (new Date() - lastSaveTime > 3000) {
            saveDesignToDB();
        }
    }, 3000);
}

function showSaveIndicator() {
    const indicator = document.getElementById('saveIndicator');
    indicator.classList.add('show');
}

function hideSaveIndicator() {
    const indicator = document.getElementById('saveIndicator');
    indicator.classList.remove('show');
}

function showNotification(message, type = 'info') {
    // إزالة الإشعارات القديمة
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // إظهار الإشعار
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // إخفاء الإشعار بعد 3 ثوان
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

async function testConnection() {
    try {
        const response = await fetch('{{ url("/barcode/test") }}');
        const result = await response.json();
        
        if (result.success) {
            showNotification('✅ الاتصال مع الخادم ناجح', 'success');
            console.log('✅ اختبار الاتصال:', result);
        } else {
            showNotification('❌ فشل في الاتصال', 'error');
        }
    } catch (error) {
        console.error('❌ خطأ في الاتصال:', error);
        showNotification('❌ لا يمكن الاتصال بالخادم', 'error');
    }
}

// منع فقدان البيانات
window.addEventListener('beforeunload', function (e) {
    // يمكن إضافة منطق للتحقق من وجود تغييرات غير محفوظة
});

console.log('🎯 مصمم الباركود الاحترافي جاهز للعمل!');
</script>
@endsection