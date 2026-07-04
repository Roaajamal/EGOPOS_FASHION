@extends('layouts.app')
@section('title', __('lang_v1.product_offers'))
@section('content')



<style>
    .ego-page-head {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        margin: 4px 0 20px; flex-wrap: wrap;
        background: #fff; border: 1px solid #eef0f4; border-radius: 16px;
        padding: 14px 18px; box-shadow: 0 2px 14px rgba(15,23,42,.05);
    }
    .ego-home-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 14px;
        background: linear-gradient(135deg,#0d9488,#0891b2); color: #fff !important;
        text-decoration: none !important; transition: all .2s ease;
        box-shadow: 0 6px 16px rgba(13,148,136,.28);
    }
    .ego-home-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(13,148,136,.36); color:#fff; }
    .ego-page-head .ico {
        width: 48px; height: 48px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
        color:#fff; font-size: 22px;
        box-shadow: 0 8px 20px rgba(79,70,229,.30);
    }
    .ego-page-head h2 { margin:0; font-weight:800; color:#1e293b; font-size:22px; }
    .ego-page-head p  { margin:0; color:#94a3b8; font-size:13px; }

    /* تبويبات النوع */
    .ego-offer-types { display:flex; gap:14px; margin-bottom:22px; flex-wrap:wrap; }
    .ego-otype {
        flex:1 1 220px; position:relative; display:flex; flex-direction:column;
        align-items:center; gap:4px; padding:18px 14px; border:2px solid #e6e9ef;
        border-radius:16px; background:#fff; color:#6b7280; cursor:pointer;
        text-align:center; transition:all .22s ease; overflow:hidden;
    }
    .ego-otype:hover { border-color:#c7d2fe; transform:translateY(-2px); box-shadow:0 8px 22px rgba(79,70,229,.10); }
    .ego-otype i { font-size:26px; color:#94a3b8; transition:all .22s ease; }
    .ego-otype .t { font-size:15px; font-weight:700; color:#374151; }
    .ego-otype small { font-size:11px; color:#9ca3af; line-height:1.4; }
    .ego-otype.active { border-color:transparent; background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%); box-shadow:0 10px 26px rgba(79,70,229,.32); }
    .ego-otype.active i, .ego-otype.active .t { color:#fff; }
    .ego-otype.active small { color:rgba(255,255,255,.85); }
    .ego-otype.active::after { content:''; position:absolute; bottom:8px; left:50%; transform:translateX(-50%); width:30px; height:3px; border-radius:3px; background:rgba(255,255,255,.9); }

    .ego-type-pane { display:none; }
    .ego-type-pane.active { display:block; animation:egoFade .25s ease; }
    @keyframes egoFade { from{opacity:0; transform:translateY(6px);} to{opacity:1; transform:none;} }

    /* البطاقات */
    .ego-card { background:#fff; border:1px solid #eef0f4; border-radius:16px; box-shadow:0 2px 14px rgba(15,23,42,.05); margin-bottom:22px; }
    .ego-card-head { padding:16px 20px; border-bottom:1px solid #f1f3f7; display:flex; align-items:center; gap:10px; }
    .ego-card-head i { color:#6366f1; font-size:18px; }
    .ego-card-head h4 { margin:0; font-size:16px; font-weight:700; color:#334155; }
    .ego-card-body { padding:20px; }

    .ego-label { font-weight:600; color:#475569; font-size:13px; margin-bottom:6px; display:block; }
    .ego-hint { color:#94a3b8; font-size:12px; }

    /* صف التكرار (شرائح/عناصر/باركود) */
    .ego-rep-row { display:flex; gap:10px; align-items:flex-end; margin-bottom:10px; flex-wrap:wrap; }
    .ego-rep-row .col { flex:1 1 160px; }
    .ego-rep-row .col.sm { flex:0 0 130px; }
    .ego-del { flex:0 0 42px; }
    .ego-del .btn { width:42px; height:38px; }

    .ego-btn-add-row { border:1px dashed #c7d2fe; background:#f5f7ff; color:#4f46e5; border-radius:10px; padding:8px 14px; font-weight:600; font-size:13px; }
    .ego-btn-add-row:hover { background:#eef2ff; }

    .ego-save-bar { display:flex; justify-content:flex-end; gap:10px; margin-top:8px; }
    .ego-soon { background:#fff; border:2px dashed #d8dee9; border-radius:16px; padding:40px 24px; text-align:center; color:#64748b; }

    table.ego-dt thead th { background:#f8fafc; font-weight:700; color:#475569; white-space:nowrap; }
    .ego-empty-tip { background:#f8fafc; border-radius:10px; padding:10px 14px; color:#64748b; font-size:13px; margin-bottom:14px; }
    .select2-container { width:100% !important; }
    /* 🆕 أزرار إجراءات أكبر وواضحة (فحص/تعديل/حذف) في كل الجداول */
    .ego-act-wrap { display:flex; flex-wrap:wrap; gap:6px; }
    .ego-act-btn { padding:7px 14px !important; font-size:13px !important; font-weight:700; border-radius:8px !important; display:inline-flex; align-items:center; gap:5px; line-height:1.2; white-space:nowrap; }
</style>

<div class="row">
    <div class="col-sm-12">

        <div class="ego-page-head">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="ico"><i class="fas fa-percent"></i></div>
                <div>
                    <h2>عروض المنتجات</h2>
                    <p>أنشئ عروض الكمية، حزم المنتجات، والباركود البديل من مكان واحد</p>
                </div>
            </div>
            <a href="{{ url('home') }}" class="ego-home-btn"><i class="fas fa-home"></i> الصفحة الرئيسية</a>
        </div>

        {{-- 🆕 تبويبات النوع --}}
        <div class="ego-offer-types">
            <button type="button" class="ego-otype active" data-type="type-quantity">
                <i class="fas fa-layer-group"></i>
                <span class="t">إضافة عرض</span>
                <small>عروض الكمية: القطعة بـ3، القطعتين بـ5…</small>
            </button>
            <button type="button" class="ego-otype" data-type="type-bundle">
                <i class="fas fa-box-open"></i>
                <span class="t">إضافة مجموعة عروض</span>
                <small>منتجات مختلفة معاً بسعر خاص</small>
            </button>
            <button type="button" class="ego-otype" data-type="type-altbarcode">
                <i class="fas fa-barcode"></i>
                <span class="t">الباركود البديل</span>
                <small>عدة باركودات لمنتج واحد</small>
            </button>
            <button type="button" class="ego-otype" data-type="type-special">
                <i class="fas fa-gift"></i>
                <span class="t">عروض خاصة</span>
                <small>اشترِ 1 والثاني مجاناً، القطعة التالية بخصم، خصم % على أصناف</small>
            </button>
        </div>

        <div class="ego-type-content">

        {{-- ============================================================
             تبويب 1: إضافة عرض (عروض الكمية)
        ============================================================ --}}
        <div class="ego-type-pane active" id="type-quantity">

            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-plus-circle"></i><h4>إضافة عرض كمية جديد</h4></div>
                <div class="ego-card-body">
                    <form id="ego_qty_form">
                        @csrf
                        <input type="hidden" id="ego_qty_variation_id" name="variation_id">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="ego-label">المنتج <span class="text-danger">*</span></label>
                                <select id="ego_qty_product" class="form-control ego-product-select" style="width:100%"></select>
                                <span class="ego-hint">ابحث بالاسم أو الباركود (SKU)</span>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="ego-label">الفرع <span class="text-danger">*</span></label>
                                <select id="ego_qty_location" name="location_id" class="form-control">
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="ego-label" style="margin-top:6px;">شرائح الكمية والسعر <span class="text-danger">*</span></label>
                        <div class="ego-empty-tip">
                            <i class="fas fa-lightbulb text-warning"></i>
                            لكل شريحة: حدّد <b>الكمية</b> و<b>السعر الإجمالي</b> عند شرائها. مثال: 1 قطعة = 3، &nbsp; 2 قطعة = 5.
                        </div>
                        <div id="ego_qty_tiers">
                            {{-- صفوف الشرائح تُضاف هنا --}}
                        </div>
                        <button type="button" class="ego-btn-add-row" id="ego_qty_add_tier"><i class="fas fa-plus"></i> إضافة شريحة</button>

                        <div class="row" style="margin-top:16px;">
                            <div class="col-md-4 form-group">
                                <label class="ego-label">تاريخ البداية</label>
                                <input type="date" name="start_date" id="ego_qty_start" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="ego-label">تاريخ النهاية</label>
                                <input type="date" name="end_date" id="ego_qty_end" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="ego-label">الحالة</label>
                                <select id="ego_qty_active" class="form-control">
                                    <option value="1">فعّال</option>
                                    <option value="0">غير فعّال</option>
                                </select>
                            </div>
                        </div>

                        <div class="ego-save-bar">
                            <button type="button" class="btn btn-default" id="ego_qty_reset">تفريغ</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ العرض</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 🆕 استيراد عروض الكمية من Excel (يعيد استخدام مساري import-excel و download-template) --}}
            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-file-excel" style="color:#16a34a"></i><h4>استيراد من Excel</h4></div>
                <div class="ego-card-body">
                    @if(session('status') && is_array(session('status')) && isset(session('status')['msg']))
                        <div class="alert {{ !empty(session('status')['success']) ? 'alert-success' : 'alert-danger' }}">{!! session('status')['msg'] !!}</div>
                    @endif
                    <form action="{{ route('product-offers.import-excel') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="ego-label">الفرع <span class="text-danger">*</span></label>
                                <select name="location_id" class="form-control" required>
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="ego-label">طريقة الاستيراد</label>
                                <select name="import_mode" class="form-control">
                                    <option value="add">إضافة/تحديث (يُبقي القديم)</option>
                                    <option value="replace">استبدال (يحذف عروض الفرع القديمة)</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="ego-label">ملف Excel <span class="text-danger">*</span></label>
                                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            </div>
                        </div>
                        <div class="ego-empty-tip">
                            <i class="fas fa-lightbulb text-warning"></i>
                            أعمدة الملف: <b>SKU/Barcode</b> ، الكمية ، السعر ، النوع (fixed/percentage/override) ، تاريخ البداية ، تاريخ النهاية ، فعّال (1/0).
                        </div>
                        <div class="ego-save-bar">
                            <a href="{{ route('product-offers.download-template') }}" class="btn btn-default"><i class="fas fa-download"></i> تحميل قالب Excel</a>
                            <button type="submit" class="btn btn-success"><i class="fas fa-file-import"></i> استيراد</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-tags"></i><h4>عروض الكمية الحالية</h4></div>
                <div class="ego-card-body">
                    <div class="row" style="margin-bottom:12px;">
                        <div class="col-md-4">
                            <select id="filter_location" class="form-control input-sm">
                                <option value="">كل الفروع</option>
                                @foreach($business_locations as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filter_status" class="form-control input-sm">
                                <option value="">كل الحالات</option>
                                <option value="1">فعّال</option>
                                <option value="0">غير فعّال</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="offers_table" class="table table-bordered table-striped ego-dt" style="width:100%">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>السعر</th>
                                    <th>النوع</th>
                                    <th>الفرع</th>
                                    <th>الفترة</th>
                                    <th>الحالة</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             تبويب 2: إضافة مجموعة عروض (حزم)
        ============================================================ --}}
        <div class="ego-type-pane" id="type-bundle">

            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-box-open"></i><h4>إضافة مجموعة عروض (حزمة)</h4></div>
                <div class="ego-card-body">
                    <form id="ego_bundle_form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="ego-label">اسم الحزمة <span class="ego-hint">(اختياري)</span></label>
                                <input type="text" id="ego_bundle_name" name="name" class="form-control" placeholder="مثال: عرض الصيف">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="ego-label">الفرع</label>
                                <select id="ego_bundle_location" name="location_id" class="form-control">
                                    <option value="">كل الفروع</option>
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <label class="ego-label" style="margin-top:6px;">منتجات الحزمة <span class="text-danger">*</span></label>
                        <div class="ego-empty-tip">
                            <i class="fas fa-lightbulb text-warning"></i>
                            أضف <b>منتجين مختلفين على الأقل</b> (بباركودات مختلفة). عند بيعها معاً في نفس الفاتورة يُطبَّق سعر الحزمة الخاص.
                        </div>
                        <div id="ego_bundle_items"></div>
                        <button type="button" class="ego-btn-add-row" id="ego_bundle_add_item"><i class="fas fa-plus"></i> إضافة منتج</button>

                        <div class="row" style="margin-top:16px;">
                            <div class="col-md-3 form-group">
                                <label class="ego-label">سعر الحزمة الإجمالي <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" id="ego_bundle_price" name="bundle_price" class="form-control" placeholder="مثال: 100">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="ego-label">تاريخ البداية</label>
                                <input type="date" id="ego_bundle_start" name="start_date" class="form-control">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="ego-label">تاريخ النهاية</label>
                                <input type="date" id="ego_bundle_end" name="end_date" class="form-control">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="ego-label">الحالة</label>
                                <select id="ego_bundle_active" class="form-control">
                                    <option value="1">فعّال</option>
                                    <option value="0">غير فعّال</option>
                                </select>
                            </div>
                        </div>

                        <div class="ego-save-bar">
                            <button type="button" class="btn btn-default" id="ego_bundle_reset">تفريغ</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الحزمة</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 🆕 استيراد الحزم من Excel --}}
            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-file-excel" style="color:#16a34a"></i><h4>استيراد الحزم من Excel</h4></div>
                <div class="ego-card-body">
                    @if(session('status') && is_array(session('status')) && isset(session('status')['msg']))
                        <div class="alert {{ !empty(session('status')['success']) ? 'alert-success' : 'alert-danger' }}">{!! session('status')['msg'] !!}</div>
                    @endif
                    <form action="{{ route('product-offers.bundles.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="ego-label">الفرع</label>
                                <select name="location_id" class="form-control">
                                    <option value="">كل الفروع</option>
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 form-group">
                                <label class="ego-label">ملف Excel <span class="text-danger">*</span></label>
                                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            </div>
                        </div>
                        <div class="ego-empty-tip">
                            <i class="fas fa-lightbulb text-warning"></i>
                            الأعمدة: <b>اسم الحزمة</b> ، SKU/الباركود ، الكمية ، سعر الحزمة ، تاريخ البداية ، تاريخ النهاية ، فعّال (1/0).
                            الصفوف بنفس <b>اسم الحزمة</b> تُجمَّع في حزمة واحدة (منتجان على الأقل).
                        </div>
                        <div class="ego-save-bar">
                            <a href="{{ route('product-offers.bundles.template') }}" class="btn btn-default"><i class="fas fa-download"></i> تحميل قالب Excel</a>
                            <button type="submit" class="btn btn-success"><i class="fas fa-file-import"></i> استيراد</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-boxes"></i><h4>الحزم الحالية</h4></div>
                <div class="ego-card-body">
                    <div class="table-responsive">
                        <table id="bundles_table" class="table table-bordered table-striped ego-dt" style="width:100%">
                            <thead>
                                <tr>
                                    <th>اسم الحزمة</th>
                                    <th>المنتجات</th>
                                    <th>سعر الحزمة</th>
                                    <th>الفرع</th>
                                    <th>الحالة</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             تبويب 3: الباركود البديل
        ============================================================ --}}
        <div class="ego-type-pane" id="type-altbarcode">

            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-barcode"></i><h4>إضافة باركود بديل</h4></div>
                <div class="ego-card-body">
                    <form id="ego_alt_form">
                        @csrf
                        <input type="hidden" id="ego_alt_variation_id" name="variation_id">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="ego-label">المنتج <span class="text-danger">*</span></label>
                                <select id="ego_alt_product" class="form-control ego-product-select" style="width:100%"></select>
                                <span class="ego-hint">المنتج الذي ستُربط به الباركودات البديلة</span>
                            </div>
                        </div>

                        <label class="ego-label" style="margin-top:6px;">الباركودات البديلة <span class="text-danger">*</span></label>
                        <div class="ego-empty-tip">
                            <i class="fas fa-lightbulb text-warning"></i>
                            أضف باركوداً واحداً أو أكثر. عند مسح أي منها في نقطة البيع سيُحضَر هذا المنتج نفسه.
                        </div>
                        <div id="ego_alt_codes"></div>
                        <button type="button" class="ego-btn-add-row" id="ego_alt_add_code"><i class="fas fa-plus"></i> إضافة باركود</button>

                        <div class="ego-save-bar">
                            <button type="button" class="btn btn-default" id="ego_alt_reset">تفريغ</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الباركودات</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 🆕 استيراد الباركود البديل من Excel --}}
            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-file-excel" style="color:#16a34a"></i><h4>استيراد من Excel</h4></div>
                <div class="ego-card-body">
                    @if(session('status') && is_array(session('status')) && isset(session('status')['msg']))
                        <div class="alert {{ !empty(session('status')['success']) ? 'alert-success' : 'alert-danger' }}">{!! session('status')['msg'] !!}</div>
                    @endif
                    <form action="{{ route('product-offers.alt-barcodes.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-8 form-group">
                                <label class="ego-label">ملف Excel <span class="text-danger">*</span></label>
                                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            </div>
                        </div>
                        <div class="ego-empty-tip">
                            <i class="fas fa-lightbulb text-warning"></i>
                            عمودان: <b>SKU/الباركود الأصلي</b> ثم <b>الباركود البديل</b>. الباركودات المكرّرة أو غير الموجودة تُتخطّى.
                        </div>
                        <div class="ego-save-bar">
                            <a href="{{ route('product-offers.alt-barcodes.template') }}" class="btn btn-default"><i class="fas fa-download"></i> تحميل قالب Excel</a>
                            <button type="submit" class="btn btn-success"><i class="fas fa-file-import"></i> استيراد</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-list"></i><h4>الباركودات البديلة الحالية</h4></div>
                <div class="ego-card-body">
                    <div class="table-responsive">
                        <table id="alt_table" class="table table-bordered table-striped ego-dt" style="width:100%">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>عدد الباركودات البديلة</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             تبويب 4: عروض خاصة
        ============================================================ --}}
        <div class="ego-type-pane" id="type-special">

            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-gift"></i><h4>إضافة عرض خاص</h4></div>
                <div class="ego-card-body">
                    <form id="ego_special_form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="ego-label">اسم العرض <span class="text-danger">*</span></label>
                                <input type="text" id="ego_sp_name" class="form-control" placeholder="مثال: عرض العيد">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="ego-label">نوع العرض <span class="text-danger">*</span></label>
                                <select id="ego_sp_type" class="form-control">
                                    <option value="bogo">اشترِ قطعة واحصل على الثانية مجاناً</option>
                                    <option value="nth_percent">اشترِ قطعة والقطعة التالية بخصم %</option>
                                    <option value="percent_items">خصم % على الأصناف المحددة</option>
                                </select>
                            </div>
                        </div>

                        {{-- حقول حسب النوع --}}
                        <div class="row" id="ego_sp_fields">
                            <div class="col-md-3 form-group ego-sp-buy">
                                <label class="ego-label">عدد الشراء (قطعة)</label>
                                <input type="number" step="1" min="1" id="ego_sp_buy" class="form-control" value="1">
                            </div>
                            <div class="col-md-3 form-group ego-sp-free">
                                <label class="ego-label">المجاني (الثانية)</label>
                                <input type="number" step="1" min="1" id="ego_sp_free" class="form-control" value="1">
                            </div>
                            <div class="col-md-3 form-group ego-sp-percent">
                                <label class="ego-label">نسبة الخصم %</label>
                                <input type="number" step="0.01" min="0" max="100" id="ego_sp_percent" class="form-control" placeholder="مثال: 50">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="ego-label">الفرع</label>
                                <select id="ego_sp_location" class="form-control">
                                    <option value="">كل الفروع</option>
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="ego-label">الأصناف المشمولة بالعرض <span class="text-danger">*</span></label>
                            <select id="ego_sp_products" class="form-control" multiple style="width:100%"></select>
                            <span class="ego-hint">اختر منتجاً أو أكثر يطبَّق عليها العرض</span>
                        </div>

                        <div class="ego-empty-tip" id="ego_sp_hint"><i class="fas fa-lightbulb text-warning"></i> <span></span></div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="ego-label">تاريخ البداية</label>
                                <input type="date" id="ego_sp_start" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="ego-label">تاريخ النهاية</label>
                                <input type="date" id="ego_sp_end" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="ego-label">الحالة</label>
                                <select id="ego_sp_active" class="form-control">
                                    <option value="1">فعّال</option>
                                    <option value="0">غير فعّال</option>
                                </select>
                            </div>
                        </div>

                        <div class="ego-save-bar">
                            <button type="button" class="btn btn-default" id="ego_sp_reset">تفريغ</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ العرض الخاص</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 🆕 استيراد العروض الخاصة من Excel --}}
            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-file-excel" style="color:#16a34a"></i><h4>استيراد العروض الخاصة من Excel</h4></div>
                <div class="ego-card-body">
                    @if(session('status') && is_array(session('status')) && isset(session('status')['msg']))
                        <div class="alert {{ !empty(session('status')['success']) ? 'alert-success' : 'alert-danger' }}">{!! session('status')['msg'] !!}</div>
                    @endif
                    <form action="{{ route('product-offers.special.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="ego-label">الفرع</label>
                                <select name="location_id" class="form-control">
                                    <option value="">كل الفروع</option>
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 form-group">
                                <label class="ego-label">ملف Excel <span class="text-danger">*</span></label>
                                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            </div>
                        </div>
                        <div class="ego-empty-tip">
                            <i class="fas fa-lightbulb text-warning"></i>
                            الأعمدة: <b>اسم العرض</b> ، النوع (bogo/nth_percent/percent_items) ، عدد الشراء ، المجاني ، نسبة % ، <b>الأصناف SKU مفصولة بفواصل</b> ، تاريخ البداية ، تاريخ النهاية ، فعّال (1/0). كل صف = عرض واحد يظهر بخانة واحدة، واضغط <b>فحص</b> لرؤية أصنافه.
                        </div>
                        <div class="ego-save-bar">
                            <a href="{{ route('product-offers.special.template') }}" class="btn btn-default"><i class="fas fa-download"></i> تحميل قالب Excel</a>
                            <button type="submit" class="btn btn-success"><i class="fas fa-file-import"></i> استيراد</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="ego-card">
                <div class="ego-card-head"><i class="fas fa-gifts"></i><h4>العروض الخاصة الحالية</h4></div>
                <div class="ego-card-body">
                    <div class="table-responsive">
                        <table id="special_table" class="table table-bordered table-striped ego-dt" style="width:100%">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>النوع</th>
                                    <th>التفاصيل</th>
                                    <th>الأصناف</th>
                                    <th>الفرع</th>
                                    <th>الحالة</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        </div>{{-- /.ego-type-content --}}

    </div>
</div>

{{-- 🆕 نافذة تعديل عرض الكمية --}}
<div class="modal fade" id="ego_edit_offer_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="ego_edit_offer_form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-edit"></i> تعديل العرض</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ego_edit_offer_id">
                    <input type="hidden" id="ego_edit_price_type" value="override">
                    <div class="form-group">
                        <label class="ego-label">المنتج</label>
                        <div id="ego_edit_product_name" style="font-weight:700;color:#334155"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="ego-label">الكمية <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0.001" id="ego_edit_min_qty" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="ego-label">السعر الإجمالي عند هذه الكمية <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" id="ego_edit_offer_price" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="ego-label">تاريخ البداية</label>
                            <input type="date" id="ego_edit_start" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="ego-label">تاريخ النهاية</label>
                            <input type="date" id="ego_edit_end" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="ego-label">الحالة</label>
                            <select id="ego_edit_active" class="form-control">
                                <option value="1">فعّال</option>
                                <option value="0">غير فعّال</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديل</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 🆕 نافذة تعديل الحزمة --}}
<div class="modal fade" id="ego_edit_bundle_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="ego_edit_bundle_form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-edit"></i> تعديل مجموعة العروض</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ego_edit_bundle_id">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="ego-label">اسم الحزمة</label>
                            <input type="text" id="ego_edit_bundle_name" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="ego-label">الفرع</label>
                            <select id="ego_edit_bundle_location" class="form-control">
                                <option value="">كل الفروع</option>
                                @foreach($business_locations as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label class="ego-label">منتجات الحزمة <span class="text-danger">*</span></label>
                    <div id="ego_edit_bundle_items"></div>
                    <button type="button" class="ego-btn-add-row" id="ego_edit_bundle_add_item"><i class="fas fa-plus"></i> إضافة منتج</button>
                    <div class="row" style="margin-top:16px;">
                        <div class="col-md-4 form-group">
                            <label class="ego-label">سعر الحزمة <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" id="ego_edit_bundle_price" class="form-control">
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="ego-label">تاريخ البداية</label>
                            <input type="date" id="ego_edit_bundle_start" class="form-control">
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="ego-label">تاريخ النهاية</label>
                            <input type="date" id="ego_edit_bundle_end" class="form-control">
                        </div>
                        <div class="col-md-2 form-group">
                            <label class="ego-label">الحالة</label>
                            <select id="ego_edit_bundle_active" class="form-control">
                                <option value="1">فعّال</option>
                                <option value="0">غير فعّال</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديل</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 🆕 نافذة تعديل الباركود البديل --}}
<div class="modal fade" id="ego_edit_alt_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="ego_edit_alt_form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-edit"></i> تعديل الباركود البديل</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ego_edit_alt_id">
                    <div class="form-group">
                        <label class="ego-label">الباركود البديل <span class="text-danger">*</span></label>
                        <input type="text" id="ego_edit_alt_code" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديل</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 🆕 نافذة تعديل العرض الخاص --}}
<div class="modal fade" id="ego_edit_special_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="ego_edit_special_form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><i class="fas fa-edit"></i> تعديل العرض الخاص</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ego_esp_id">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="ego-label">اسم العرض <span class="text-danger">*</span></label>
                            <input type="text" id="ego_esp_name" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="ego-label">نوع العرض <span class="text-danger">*</span></label>
                            <select id="ego_esp_type" class="form-control">
                                <option value="bogo">اشترِ X واحصل على Y مجاناً</option>
                                <option value="nth_percent">اشترِ X والقطعة التالية بخصم %</option>
                                <option value="percent_items">خصم % على الأصناف المحددة</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group ego-esp-buy">
                            <label class="ego-label">عدد الشراء (X)</label>
                            <input type="number" step="1" min="1" id="ego_esp_buy" class="form-control" value="1">
                        </div>
                        <div class="col-md-3 form-group ego-esp-free">
                            <label class="ego-label">المجاني (Y)</label>
                            <input type="number" step="1" min="1" id="ego_esp_free" class="form-control" value="1">
                        </div>
                        <div class="col-md-3 form-group ego-esp-percent">
                            <label class="ego-label">نسبة الخصم %</label>
                            <input type="number" step="0.01" min="0" max="100" id="ego_esp_percent" class="form-control">
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="ego-label">الفرع</label>
                            <select id="ego_esp_location" class="form-control">
                                <option value="">كل الفروع</option>
                                @foreach($business_locations as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="ego-label">الأصناف المشمولة <span class="text-danger">*</span></label>
                        <select id="ego_esp_products" class="form-control" multiple style="width:100%"></select>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="ego-label">تاريخ البداية</label>
                            <input type="date" id="ego_esp_start" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="ego-label">تاريخ النهاية</label>
                            <input type="date" id="ego_esp_end" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="ego-label">الحالة</label>
                            <select id="ego_esp_active" class="form-control">
                                <option value="1">فعّال</option>
                                <option value="0">غير فعّال</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديل</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 🆕 نافذة فحص تفاصيل العرض/الحزمة (تعرض المنتجات والأعمدة الخاصة) --}}
<div class="modal fade" id="ego_inspect_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-search"></i> <span id="ego_inspect_title">فحص التفاصيل</span></h4>
            </div>
            <div class="modal-body">
                <div id="ego_inspect_header" style="margin-bottom:14px;"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ego-dt" style="width:100%">
                        <thead><tr id="ego_inspect_cols"></tr></thead>
                        <tbody id="ego_inspect_rows"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@stop

@section('javascript')
<script>
$(document).ready(function() {

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    /* ====== تبديل تبويبات النوع ====== */
    $(document).on('click', '.ego-otype', function() {
        var target = $(this).data('type');
        $('.ego-otype').removeClass('active');
        $(this).addClass('active');
        $('.ego-type-pane').removeClass('active');
        $('#' + target).addClass('active');
        // إعادة ضبط أعمدة الجداول المرئية
        $.fn.dataTable && $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // 🆕 نصوص الجداول بالعربية (بدل تحميل ملف اللغة الذي قد يظهر بالإنجليزية)
    var egoDtLang = {
        "sProcessing":   "جاري التحميل...",
        "sLengthMenu":   "أظهر _MENU_ مدخل",
        "sZeroRecords":  "لم يُعثر على أي سجل",
        "sEmptyTable":   "لا توجد بيانات",
        "sInfo":         "إظهار _START_ إلى _END_ من _TOTAL_ مدخل",
        "sInfoEmpty":    "إظهار 0 إلى 0 من 0 مدخل",
        "sInfoFiltered": "(منتقاة من مجموع _MAX_ مدخل)",
        "sSearch":       "ابحث:",
        "sLoadingRecords": "جاري التحميل...",
        "oPaginate": { "sFirst": "الأول", "sLast": "الأخير", "sNext": "التالي", "sPrevious": "السابق" }
    };

    /* ====== أدوات select2 للمنتجات ====== */
    function egoFmtProduct(p) {
        if (p.loading) return p.text;
        if (!p.id) return p.text;
        var sku = p.sub_sku ? ' <small style="color:#94a3b8">(' + p.sub_sku + ')</small>' : '';
        return $('<span>' + p.text + sku + '</span>');
    }
    function egoFmtProductSel(p) { return p.text || 'اختر منتجاً'; }

    function egoInitProductSelect($el, $parent) {
        var opts = {
            ajax: {
                url: "{{ route('product-offers.search-products') }}",
                dataType: 'json', delay: 250,
                data: function(params) { return { term: params.term }; },
                processResults: function(data) { return { results: data }; },
                cache: true
            },
            minimumInputLength: 1,
            placeholder: 'ابحث عن منتج…',
            templateResult: egoFmtProduct,
            templateSelection: egoFmtProductSel,
            width: '100%'
        };
        if ($parent && $parent.length) { opts.dropdownParent = $parent; } // ليعمل select2 داخل النوافذ المنبثقة
        $el.select2(opts);
    }

    /* ============================================================
       تبويب 1: عروض الكمية
    ============================================================ */
    egoInitProductSelect($('#ego_qty_product'));
    $('#ego_qty_product').on('select2:select', function(e) {
        $('#ego_qty_variation_id').val(e.params.data.id);
    });

    function egoQtyTierRow() {
        return '<div class="ego-rep-row ego-qty-tier">'
            + '<div class="col sm"><label class="ego-label">الكمية</label>'
            + '<input type="number" step="0.001" min="0.001" class="form-control ego-tier-qty" placeholder="مثال: 2"></div>'
            + '<div class="col"><label class="ego-label">السعر الإجمالي عند هذه الكمية</label>'
            + '<input type="number" step="0.01" min="0" class="form-control ego-tier-price" placeholder="مثال: 5"></div>'
            + '<div class="ego-del"><button type="button" class="btn btn-danger ego-rm-tier"><i class="fas fa-times"></i></button></div>'
            + '</div>';
    }
    $('#ego_qty_add_tier').on('click', function() { $('#ego_qty_tiers').append(egoQtyTierRow()); });
    $(document).on('click', '.ego-rm-tier', function() { $(this).closest('.ego-qty-tier').remove(); });
    // صف افتراضي واحد
    $('#ego_qty_tiers').append(egoQtyTierRow());

    function egoQtyReset() {
        $('#ego_qty_form')[0].reset();
        $('#ego_qty_product').val(null).trigger('change');
        $('#ego_qty_variation_id').val('');
        $('#ego_qty_tiers').empty().append(egoQtyTierRow());
    }
    $('#ego_qty_reset').on('click', egoQtyReset);

    $('#ego_qty_form').on('submit', function(e) {
        e.preventDefault();
        var variationId = $('#ego_qty_variation_id').val();
        if (!variationId) { toastr.error('اختر منتجاً أولاً'); return; }

        var tiers = [];
        var valid = true;
        $('#ego_qty_tiers .ego-qty-tier').each(function() {
            var q = $(this).find('.ego-tier-qty').val();
            var p = $(this).find('.ego-tier-price').val();
            if (q === '' || p === '') { valid = false; return; }
            tiers.push({ q: q, p: p });
        });
        if (!valid || tiers.length === 0) { toastr.error('أكمل بيانات الشرائح (الكمية والسعر)'); return; }

        var common = {
            variation_id: variationId,
            location_id: $('#ego_qty_location').val(),
            price_type: 'override',
            start_date: $('#ego_qty_start').val() || null,
            end_date: $('#ego_qty_end').val() || null,
            is_active: $('#ego_qty_active').val()
        };

        var $btn = $(this).find('button[type=submit]').prop('disabled', true);
        var calls = tiers.map(function(t) {
            return $.post("{{ route('product-offers.store') }}", $.extend({}, common, {
                min_quantity: t.q, offer_price: t.p
            }));
        });

        $.when.apply($, calls).done(function() {
            toastr.success('تم حفظ العرض بنجاح');
            egoQtyReset();
            offers_table.ajax.reload();
        }).fail(function() {
            toastr.error("{{ __('messages.something_went_wrong') }}");
        }).always(function() { $btn.prop('disabled', false); });
    });

    var offers_table = $('#offers_table').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: "{{ route('product-offers.get-data') }}",
            data: function(d) {
                d.location_id = $('#filter_location').val();
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'product', name: 'product', orderable: false },
            { data: 'min_quantity', name: 'min_quantity' },
            { data: 'offer_price', name: 'offer_price' },
            { data: 'price_type', name: 'price_type' },
            { data: 'location_name', name: 'bl.name' },
            { data: null, orderable: false, render: function(d) {
                var s = d.start_date ? d.start_date : '-';
                var en = d.end_date ? d.end_date : '-';
                return s + ' <i class="fa fa-arrow-left text-muted"></i> ' + en;
            }},
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        language: egoDtLang
    });
    $('#filter_location, #filter_status').on('change', function() { offers_table.ajax.reload(); });

    // حذف عرض كمية (نفس مسار النظام القديم)
    $(document).on('click', '#offers_table .delete-btn', function() {
        if (!confirm('حذف هذا العرض؟')) return;
        $.ajax({ url: $(this).data('href'), type: 'DELETE',
            success: function(r){ if(r.success){ toastr.success(r.msg); offers_table.ajax.reload(); } else { toastr.error(r.msg); } }
        });
    });

    // 🆕 تعديل عرض كمية: يحمّل بيانات العرض في النافذة ثم يحفظ بمسار التحديث
    function egoDateOnly(v){ return v ? String(v).substring(0, 10) : ''; }
    $(document).on('click', '#offers_table .edit-btn', function() {
        var id = $(this).data('id');
        $.get("{{ url('product-offers') }}/" + id + "/edit", function(r){
            if (!r.success) { toastr.error(r.msg || 'تعذّر جلب بيانات العرض'); return; }
            var o = r.offer, p = r.product_info || {};
            $('#ego_edit_offer_id').val(id);
            $('#ego_edit_price_type').val(o.price_type || 'override');
            $('#ego_edit_product_name').text((p.product_name || '') + (p.variation_name && p.variation_name !== 'DUMMY' ? ' - ' + p.variation_name : '') + (p.sub_sku ? ' (' + p.sub_sku + ')' : ''));
            $('#ego_edit_min_qty').val(o.min_quantity);
            $('#ego_edit_offer_price').val(o.offer_price);
            $('#ego_edit_start').val(egoDateOnly(o.start_date));
            $('#ego_edit_end').val(egoDateOnly(o.end_date));
            $('#ego_edit_active').val(o.is_active ? '1' : '0');
            $('#ego_edit_offer_modal').modal('show');
        });
    });
    $('#ego_edit_offer_form').on('submit', function(e){
        e.preventDefault();
        var id = $('#ego_edit_offer_id').val();
        var $btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.ajax({
            url: "{{ url('product-offers') }}/" + id,
            type: 'PUT',
            data: {
                min_quantity: $('#ego_edit_min_qty').val(),
                offer_price: $('#ego_edit_offer_price').val(),
                price_type: $('#ego_edit_price_type').val(),
                start_date: $('#ego_edit_start').val() || null,
                end_date: $('#ego_edit_end').val() || null,
                is_active: $('#ego_edit_active').val()
            }
        }).done(function(r){
            if (r.success) { toastr.success(r.msg); $('#ego_edit_offer_modal').modal('hide'); offers_table.ajax.reload(); }
            else { toastr.error(r.msg); }
        }).fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); })
          .always(function(){ $btn.prop('disabled', false); });
    });

    /* ============================================================
       تبويب 2: مجموعة عروض (حزم)
    ============================================================ */
    var egoBundleSeq = 0;
    function egoBundleItemRow() {
        egoBundleSeq++;
        var sid = 'ego_bundle_prod_' + egoBundleSeq;
        return '<div class="ego-rep-row ego-bundle-item">'
            + '<div class="col"><label class="ego-label">المنتج</label>'
            + '<select id="' + sid + '" class="form-control ego-bundle-prod" style="width:100%"></select></div>'
            + '<div class="col sm"><label class="ego-label">الكمية</label>'
            + '<input type="number" step="0.001" min="0.001" value="1" class="form-control ego-bundle-qty"></div>'
            + '<div class="ego-del"><button type="button" class="btn btn-danger ego-rm-item"><i class="fas fa-times"></i></button></div>'
            + '</div>';
    }
    function egoAddBundleItem() {
        var $row = $(egoBundleItemRow());
        $('#ego_bundle_items').append($row);
        egoInitProductSelect($row.find('.ego-bundle-prod'));
    }
    $('#ego_bundle_add_item').on('click', egoAddBundleItem);
    $(document).on('click', '.ego-rm-item', function() { $(this).closest('.ego-bundle-item').remove(); });
    // صفّان افتراضيان
    egoAddBundleItem(); egoAddBundleItem();

    function egoBundleReset() {
        $('#ego_bundle_form')[0].reset();
        $('#ego_bundle_items').empty();
        egoBundleSeq = 0;
        egoAddBundleItem(); egoAddBundleItem();
    }
    $('#ego_bundle_reset').on('click', egoBundleReset);

    $('#ego_bundle_form').on('submit', function(e) {
        e.preventDefault();
        var items = [];
        $('#ego_bundle_items .ego-bundle-item').each(function() {
            var vid = $(this).find('.ego-bundle-prod').val();
            var qty = $(this).find('.ego-bundle-qty').val();
            if (vid && qty) items.push({ variation_id: vid, quantity: qty });
        });
        if (items.length < 2) { toastr.error('اختر منتجين مختلفين على الأقل'); return; }
        if (!$('#ego_bundle_price').val()) { toastr.error('أدخل سعر الحزمة'); return; }

        var data = {
            name: $('#ego_bundle_name').val(),
            location_id: $('#ego_bundle_location').val() || null,
            bundle_price: $('#ego_bundle_price').val(),
            start_date: $('#ego_bundle_start').val() || null,
            end_date: $('#ego_bundle_end').val() || null,
            is_active: $('#ego_bundle_active').val(),
            items: items
        };
        var $btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.post("{{ route('product-offers.bundles.store') }}", data)
            .done(function(r) {
                if (r.success) { toastr.success(r.msg); egoBundleReset(); bundles_table.ajax.reload(); }
                else { toastr.error(r.msg); }
            })
            .fail(function() { toastr.error("{{ __('messages.something_went_wrong') }}"); })
            .always(function() { $btn.prop('disabled', false); });
    });

    var bundles_table = $('#bundles_table').DataTable({
        processing: true, serverSide: true,
        ajax: { url: "{{ route('product-offers.bundles.get-data') }}" },
        columns: [
            { data: 'name', name: 'name', render: function(d){ return d || '<span class="text-muted">—</span>'; } },
            { data: 'products', name: 'products', orderable: false, searchable: false },
            { data: 'bundle_price', name: 'bundle_price' },
            { data: 'location_name', name: 'bl.name', orderable: false },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        language: egoDtLang
    });

    $(document).on('click', '.delete-bundle-btn', function() {
        if (!confirm('حذف هذه الحزمة؟')) return;
        var id = $(this).data('id');
        $.ajax({ url: "{{ url('product-offers/bundles') }}/" + id, type: 'DELETE',
            success: function(r){ if(r.success){ toastr.success(r.msg); bundles_table.ajax.reload(); } else { toastr.error(r.msg); } }
        });
    });

    // 🆕 تعديل الحزمة
    var egoEditBundleSeq = 0;
    function egoEditBundleItemRow(vid, qty, label) {
        egoEditBundleSeq++;
        var sid = 'ego_edit_bundle_prod_' + egoEditBundleSeq;
        var opt = (vid && label) ? '<option value="' + vid + '" selected>' + $('<div>').text(label).html() + '</option>' : '';
        return '<div class="ego-rep-row ego-edit-bundle-item">'
            + '<div class="col"><label class="ego-label">المنتج</label>'
            + '<select id="' + sid + '" class="form-control ego-edit-bundle-prod" style="width:100%">' + opt + '</select></div>'
            + '<div class="col sm"><label class="ego-label">الكمية</label>'
            + '<input type="number" step="0.001" min="0.001" value="' + (qty || 1) + '" class="form-control ego-edit-bundle-qty"></div>'
            + '<div class="ego-del"><button type="button" class="btn btn-danger ego-rm-edit-item"><i class="fas fa-times"></i></button></div>'
            + '</div>';
    }
    function egoAddEditBundleItem(vid, qty, label) {
        var $row = $(egoEditBundleItemRow(vid, qty, label));
        $('#ego_edit_bundle_items').append($row);
        egoInitProductSelect($row.find('.ego-edit-bundle-prod'), $('#ego_edit_bundle_modal'));
    }
    $('#ego_edit_bundle_add_item').on('click', function(){ egoAddEditBundleItem(); });
    $(document).on('click', '.ego-rm-edit-item', function(){ $(this).closest('.ego-edit-bundle-item').remove(); });

    $(document).on('click', '.edit-bundle-btn', function() {
        var id = $(this).data('id');
        $.get("{{ url('product-offers/bundles') }}/" + id + "/edit", function(r){
            if (!r.success) { toastr.error(r.msg || 'تعذّر جلب بيانات الحزمة'); return; }
            var b = r.bundle;
            $('#ego_edit_bundle_id').val(id);
            $('#ego_edit_bundle_name').val(b.name || '');
            $('#ego_edit_bundle_location').val(b.location_id || '');
            $('#ego_edit_bundle_price').val(b.bundle_price);
            $('#ego_edit_bundle_start').val(b.start_date ? String(b.start_date).substring(0,10) : '');
            $('#ego_edit_bundle_end').val(b.end_date ? String(b.end_date).substring(0,10) : '');
            $('#ego_edit_bundle_active').val(b.is_active ? '1' : '0');
            $('#ego_edit_bundle_items').empty();
            egoEditBundleSeq = 0;
            (r.items || []).forEach(function(it){ egoAddEditBundleItem(it.variation_id, it.quantity, it.label); });
            if (!r.items || r.items.length === 0) { egoAddEditBundleItem(); egoAddEditBundleItem(); }
            $('#ego_edit_bundle_modal').modal('show');
        });
    });

    $('#ego_edit_bundle_form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#ego_edit_bundle_id').val();
        var items = [];
        $('#ego_edit_bundle_items .ego-edit-bundle-item').each(function(){
            var vid = $(this).find('.ego-edit-bundle-prod').val();
            var qty = $(this).find('.ego-edit-bundle-qty').val();
            if (vid && qty) items.push({ variation_id: vid, quantity: qty });
        });
        if (items.length < 2) { toastr.error('اختر منتجين مختلفين على الأقل'); return; }
        if (!$('#ego_edit_bundle_price').val()) { toastr.error('أدخل سعر الحزمة'); return; }
        var $btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.ajax({ url: "{{ url('product-offers/bundles') }}/" + id, type: 'PUT', data: {
            name: $('#ego_edit_bundle_name').val(),
            location_id: $('#ego_edit_bundle_location').val() || null,
            bundle_price: $('#ego_edit_bundle_price').val(),
            start_date: $('#ego_edit_bundle_start').val() || null,
            end_date: $('#ego_edit_bundle_end').val() || null,
            is_active: $('#ego_edit_bundle_active').val(),
            items: items
        }}).done(function(r){
            if (r.success) { toastr.success(r.msg); $('#ego_edit_bundle_modal').modal('hide'); bundles_table.ajax.reload(); }
            else { toastr.error(r.msg); }
        }).fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); })
          .always(function(){ $btn.prop('disabled', false); });
    });

    /* ============================================================
       تبويب 3: الباركود البديل
    ============================================================ */
    egoInitProductSelect($('#ego_alt_product'));
    $('#ego_alt_product').on('select2:select', function(e) {
        $('#ego_alt_variation_id').val(e.params.data.id);
    });

    function egoAltCodeRow() {
        return '<div class="ego-rep-row ego-alt-code">'
            + '<div class="col"><input type="text" class="form-control ego-alt-input" placeholder="امسح أو اكتب الباركود"></div>'
            + '<div class="ego-del"><button type="button" class="btn btn-danger ego-rm-code"><i class="fas fa-times"></i></button></div>'
            + '</div>';
    }
    $('#ego_alt_add_code').on('click', function() { $('#ego_alt_codes').append(egoAltCodeRow()); });
    $(document).on('click', '.ego-rm-code', function() { $(this).closest('.ego-alt-code').remove(); });
    $('#ego_alt_codes').append(egoAltCodeRow());

    function egoAltReset() {
        $('#ego_alt_form')[0].reset();
        $('#ego_alt_product').val(null).trigger('change');
        $('#ego_alt_variation_id').val('');
        $('#ego_alt_codes').empty().append(egoAltCodeRow());
    }
    $('#ego_alt_reset').on('click', egoAltReset);

    $('#ego_alt_form').on('submit', function(e) {
        e.preventDefault();
        var vid = $('#ego_alt_variation_id').val();
        if (!vid) { toastr.error('اختر منتجاً أولاً'); return; }
        var codes = [];
        $('#ego_alt_codes .ego-alt-input').each(function() {
            var v = $.trim($(this).val());
            if (v !== '') codes.push(v);
        });
        if (codes.length === 0) { toastr.error('أدخل باركوداً واحداً على الأقل'); return; }

        var $btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.post("{{ route('product-offers.alt-barcodes.store') }}", { variation_id: vid, barcodes: codes })
            .done(function(r) {
                if (r.success) { toastr.success(r.msg); egoAltReset(); alt_table.ajax.reload(); }
                else { toastr.error(r.msg); }
            })
            .fail(function() { toastr.error("{{ __('messages.something_went_wrong') }}"); })
            .always(function() { $btn.prop('disabled', false); });
    });

    var alt_table = $('#alt_table').DataTable({
        processing: true, serverSide: true,
        ajax: { url: "{{ route('product-offers.alt-barcodes.get-data') }}" },
        columns: [
            { data: 'product', name: 'product', orderable: false },
            { data: 'codes_count', name: 'codes_count', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [],
        language: egoDtLang
    });

    $(document).on('click', '.delete-alt-btn', function() {
        if (!confirm('حذف هذا الباركود البديل؟')) return;
        var id = $(this).data('id');
        $.ajax({ url: "{{ url('product-offers/alt-barcodes') }}/" + id, type: 'DELETE',
            success: function(r){ if(r.success){ toastr.success(r.msg); alt_table.ajax.reload(); } else { toastr.error(r.msg); } }
        });
    });

    // 🆕 تعديل الباركود البديل
    $(document).on('click', '.edit-alt-btn', function() {
        $('#ego_edit_alt_id').val($(this).data('id'));
        $('#ego_edit_alt_code').val($(this).data('code'));
        $('#ego_edit_alt_modal').modal('show');
    });
    $('#ego_edit_alt_form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#ego_edit_alt_id').val();
        var code = $('#ego_edit_alt_code').val();
        if (!code || !code.trim()) { toastr.error('أدخل الباركود'); return; }
        var $btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.ajax({ url: "{{ url('product-offers/alt-barcodes') }}/" + id, type: 'PUT', data: { alt_barcode: code } })
            .done(function(r){
                if (r.success) { toastr.success(r.msg); $('#ego_edit_alt_modal').modal('hide'); alt_table.ajax.reload(); }
                else { toastr.error(r.msg); }
            })
            .fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); })
            .always(function(){ $btn.prop('disabled', false); });
    });

    /* ============================================================
       تبويب 4: عروض خاصة
    ============================================================ */
    egoInitProductSelect($('#ego_sp_products'));  // متعدّد (multiple مضبوط في HTML)

    function egoSpUpdateFields() {
        var t = $('#ego_sp_type').val();
        $('.ego-sp-buy, .ego-sp-free, .ego-sp-percent').hide();
        var hint = '';
        if (t === 'bogo') {
            $('.ego-sp-buy, .ego-sp-free').show();
            hint = 'اشترِ العدد المحدّد (X) من الأصناف واحصل على (Y) مجاناً — يُحتسب الأرخص مجاناً.';
        } else if (t === 'nth_percent') {
            $('.ego-sp-buy, .ego-sp-free, .ego-sp-percent').show();
            hint = 'اشترِ (X) قطعة، والـ (Y) التالية عليها نسبة الخصم المحددة.';
        } else {
            $('.ego-sp-percent').show();
            hint = 'خصم بنسبة مئوية على كل الأصناف المحددة في العرض.';
        }
        $('#ego_sp_hint span').text(hint);
    }
    $('#ego_sp_type').on('change', egoSpUpdateFields);
    egoSpUpdateFields();

    function egoSpReset() {
        $('#ego_special_form')[0].reset();
        $('#ego_sp_products').val(null).trigger('change');
        $('#ego_sp_buy').val(1); $('#ego_sp_free').val(1);
        egoSpUpdateFields();
    }
    $('#ego_sp_reset').on('click', egoSpReset);

    $('#ego_special_form').on('submit', function(e) {
        e.preventDefault();
        var items = $('#ego_sp_products').val() || [];
        if (!$('#ego_sp_name').val().trim()) { toastr.error('أدخل اسم العرض'); return; }
        if (items.length < 1) { toastr.error('اختر صنفاً واحداً على الأقل'); return; }

        var data = {
            name: $('#ego_sp_name').val(),
            offer_type: $('#ego_sp_type').val(),
            buy_qty: $('#ego_sp_buy').val() || 1,
            free_qty: $('#ego_sp_free').val() || 1,
            percent: $('#ego_sp_percent').val() || 0,
            location_id: $('#ego_sp_location').val() || null,
            start_date: $('#ego_sp_start').val() || null,
            end_date: $('#ego_sp_end').val() || null,
            is_active: $('#ego_sp_active').val(),
            items: items
        };
        var $btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.post("{{ route('product-offers.special.store') }}", data)
            .done(function(r){
                if (r.success) { toastr.success(r.msg); egoSpReset(); special_table.ajax.reload(); }
                else { toastr.error(r.msg); }
            })
            .fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); })
            .always(function(){ $btn.prop('disabled', false); });
    });

    var special_table = $('#special_table').DataTable({
        processing: true, serverSide: true,
        ajax: { url: "{{ route('product-offers.special.get-data') }}" },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'offer_type', name: 'offer_type' },
            { data: 'details', name: 'details', orderable: false, searchable: false },
            { data: 'products', name: 'products', orderable: false, searchable: false },
            { data: 'location_name', name: 'bl.name', orderable: false },
            { data: 'is_active', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        language: egoDtLang
    });

    $(document).on('click', '.delete-special-btn', function() {
        if (!confirm('حذف هذا العرض الخاص؟')) return;
        var id = $(this).data('id');
        $.ajax({ url: "{{ url('product-offers/special') }}/" + id, type: 'DELETE',
            success: function(r){ if(r.success){ toastr.success(r.msg); special_table.ajax.reload(); } else { toastr.error(r.msg); } }
        });
    });

    // 🆕 تعديل العرض الخاص
    egoInitProductSelect($('#ego_esp_products'), $('#ego_edit_special_modal'));
    function egoEspUpdateFields() {
        var t = $('#ego_esp_type').val();
        $('#ego_edit_special_modal .ego-esp-buy, #ego_edit_special_modal .ego-esp-free, #ego_edit_special_modal .ego-esp-percent').hide();
        if (t === 'bogo') { $('.ego-esp-buy, .ego-esp-free').show(); }
        else if (t === 'nth_percent') { $('.ego-esp-buy, .ego-esp-free, .ego-esp-percent').show(); }
        else { $('.ego-esp-percent').show(); }
    }
    $('#ego_esp_type').on('change', egoEspUpdateFields);

    $(document).on('click', '.edit-special-btn', function() {
        var id = $(this).data('id');
        $.get("{{ url('product-offers/special') }}/" + id + "/edit", function(r){
            if (!r.success) { toastr.error(r.msg || 'تعذّر الجلب'); return; }
            var o = r.offer;
            $('#ego_esp_id').val(id);
            $('#ego_esp_name').val(o.name || '');
            $('#ego_esp_type').val(o.offer_type);
            $('#ego_esp_buy').val(o.buy_qty); $('#ego_esp_free').val(o.free_qty); $('#ego_esp_percent').val(o.percent);
            $('#ego_esp_location').val(o.location_id || '');
            $('#ego_esp_start').val(o.start_date ? String(o.start_date).substring(0,10) : '');
            $('#ego_esp_end').val(o.end_date ? String(o.end_date).substring(0,10) : '');
            $('#ego_esp_active').val(o.is_active ? '1' : '0');
            // املأ المنتجات (خيارات مُسبقة)
            var $sel = $('#ego_esp_products'); $sel.empty();
            (r.items || []).forEach(function(it){
                $sel.append(new Option(it.label, it.variation_id, true, true));
            });
            $sel.trigger('change');
            egoEspUpdateFields();
            $('#ego_edit_special_modal').modal('show');
        });
    });

    $('#ego_edit_special_form').on('submit', function(e) {
        e.preventDefault();
        var id = $('#ego_esp_id').val();
        var items = $('#ego_esp_products').val() || [];
        if (!$('#ego_esp_name').val().trim()) { toastr.error('أدخل اسم العرض'); return; }
        if (items.length < 1) { toastr.error('اختر صنفاً واحداً على الأقل'); return; }
        var $btn = $(this).find('button[type=submit]').prop('disabled', true);
        $.ajax({ url: "{{ url('product-offers/special') }}/" + id, type: 'PUT', data: {
            name: $('#ego_esp_name').val(),
            offer_type: $('#ego_esp_type').val(),
            buy_qty: $('#ego_esp_buy').val() || 1,
            free_qty: $('#ego_esp_free').val() || 1,
            percent: $('#ego_esp_percent').val() || 0,
            location_id: $('#ego_esp_location').val() || null,
            start_date: $('#ego_esp_start').val() || null,
            end_date: $('#ego_esp_end').val() || null,
            is_active: $('#ego_esp_active').val(),
            items: items
        }}).done(function(r){
            if (r.success) { toastr.success(r.msg); $('#ego_edit_special_modal').modal('hide'); special_table.ajax.reload(); }
            else { toastr.error(r.msg); }
        }).fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); })
          .always(function(){ $btn.prop('disabled', false); });
    });

    /* ============================================================
       🆕 فحص التفاصيل (عرض خاص / حزمة) — يعرض المنتجات والأعمدة الخاصة في نافذة
    ============================================================ */
    function egoRenderInspect(r) {
        if (!r || !r.success) { toastr.error((r && r.msg) || 'تعذّر جلب التفاصيل'); return; }
        $('#ego_inspect_title').text(r.title || 'فحص التفاصيل');
        var h = '';
        (r.header || []).forEach(function(x){
            h += '<span class="label label-default" style="margin:2px;font-size:13px;font-weight:600;">' + $('<div>').text(x[0]).html() + ': ' + $('<div>').text(x[1] == null ? '' : x[1]).html() + '</span> ';
        });
        $('#ego_inspect_header').html(h);
        var cols = '';
        (r.columns || []).forEach(function(c){ cols += '<th>' + $('<div>').text(c).html() + '</th>'; });
        $('#ego_inspect_cols').html(cols);
        var body = '';
        (r.rows || []).forEach(function(row){
            body += '<tr>';
            row.forEach(function(cell, ci){
                var isLast = (ci === row.length - 1);
                if (r.rawLast && isLast) { body += '<td>' + (cell == null ? '' : cell) + '</td>'; } // آخر عمود HTML (أزرار)
                else { body += '<td>' + $('<div>').text(cell == null ? '' : cell).html() + '</td>'; }
            });
            body += '</tr>';
        });
        if (!body) { body = '<tr><td colspan="' + ((r.columns || []).length || 1) + '" class="text-center text-muted">لا توجد أصناف</td></tr>'; }
        $('#ego_inspect_rows').html(body);
        $('#ego_inspect_modal').modal('show');
    }
    $(document).on('click', '.inspect-special-btn', function(){
        $.get("{{ url('product-offers/special') }}/" + $(this).data('id') + "/items", egoRenderInspect)
            .fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); });
    });
    $(document).on('click', '.inspect-bundle-btn', function(){
        $.get("{{ url('product-offers/bundles') }}/" + $(this).data('id') + "/items", egoRenderInspect)
            .fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); });
    });
    $(document).on('click', '.inspect-offer-btn', function(){
        $.get("{{ url('product-offers') }}/" + $(this).data('id') + "/items", egoRenderInspect)
            .fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); });
    });
    var egoLastAltVid = null;
    $(document).on('click', '.inspect-alt-btn', function(){
        egoLastAltVid = $(this).data('vid');
        $.get("{{ url('product-offers/alt-barcodes') }}/" + egoLastAltVid + "/items", egoRenderInspect)
            .fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); });
    });
    // حذف كل باركودات المنتج (من صف الجدول المجمّع)
    $(document).on('click', '.delete-alt-group-btn', function(){
        if (!confirm('حذف كل الباركودات البديلة لهذا المنتج؟')) return;
        $.ajax({ url: "{{ url('product-offers/alt-barcodes/group') }}/" + $(this).data('vid'), type: 'DELETE',
            success: function(r){ if(r.success){ toastr.success(r.msg); alt_table.ajax.reload(); } else { toastr.error(r.msg); } } });
    });
    // تعديل باركود مفرد داخل نافذة الفحص (تحرير مباشر في الصف)
    $(document).on('click', '.ego-insp-edit-alt', function(){
        var $tr = $(this).closest('tr');
        var id = $(this).data('id'), vid = $(this).data('vid'), code = $(this).data('code');
        $tr.find('td:first').html('<input type="text" class="form-control input-sm ego-insp-edit-input" value="' + $('<div>').text(code).html() + '">');
        $tr.find('td:last').html('<button class="btn btn-xs btn-success ego-insp-save-alt" data-id="' + id + '" data-vid="' + vid + '"><i class="fa fa-check"></i> حفظ</button> '
            + '<button class="btn btn-xs btn-default ego-insp-cancel-alt" data-vid="' + vid + '">إلغاء</button>');
        $tr.find('.ego-insp-edit-input').focus().select();
    });
    $(document).on('click', '.ego-insp-save-alt', function(){
        var $tr = $(this).closest('tr');
        var val = $.trim($tr.find('.ego-insp-edit-input').val());
        if (!val) { toastr.error('أدخل الباركود'); return; }
        var vid = $(this).data('vid');
        $.ajax({ url: "{{ url('product-offers/alt-barcodes') }}/" + $(this).data('id'), type: 'PUT', data: { alt_barcode: val } })
            .done(function(r){
                if (r.success) { toastr.success(r.msg); alt_table.ajax.reload(); $.get("{{ url('product-offers/alt-barcodes') }}/" + vid + "/items", egoRenderInspect); }
                else { toastr.error(r.msg); }
            }).fail(function(){ toastr.error("{{ __('messages.something_went_wrong') }}"); });
    });
    $(document).on('click', '.ego-insp-cancel-alt', function(){
        $.get("{{ url('product-offers/alt-barcodes') }}/" + $(this).data('vid') + "/items", egoRenderInspect);
    });
    // حذف باركود مفرد من داخل نافذة الفحص ثم تحديث النافذة والجدول
    $(document).on('click', '.ego-insp-del-alt', function(){
        if (!confirm('حذف هذا الباركود؟')) return;
        var vid = $(this).data('vid');
        $.ajax({ url: "{{ url('product-offers/alt-barcodes') }}/" + $(this).data('id'), type: 'DELETE',
            success: function(r){
                if(r.success){
                    toastr.success(r.msg);
                    alt_table.ajax.reload();
                    $.get("{{ url('product-offers/alt-barcodes') }}/" + vid + "/items", egoRenderInspect);
                } else { toastr.error(r.msg); }
            } });
    });

});
</script>
@endsection
