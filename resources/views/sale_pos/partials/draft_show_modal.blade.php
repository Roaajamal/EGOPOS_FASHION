<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                <i class="fas fa-file-alt"></i> 
                @if($draft->sub_status == 'quotation')
                    عرض سعر
                @elseif($draft->sub_status == 'proforma')
                    فاتورة أولية
                @else
                    مسودة
                @endif
                : <strong>{{ $draft->invoice_no }}</strong>
            </h4>
        </div>

        <div class="modal-body">
            
            {{-- Status Badges --}}
            @if($draft->is_converted)
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> 
                    <strong>تم تحويل هذه المسودة إلى فاتورة نهائية</strong>
                    @if($final_transaction)
                        <br>
                        <a href="{{ action([\App\Http\Controllers\SellController::class, 'show'], [$final_transaction->id]) }}" 
                           class="btn btn-sm btn-success btn-modal" data-container=".view_modal">
                            <i class="fa fa-external-link"></i> عرض الفاتورة النهائية: {{ $final_transaction->invoice_no }}
                        </a>
                    @endif
                </div>
            @endif

            {{-- Draft Information --}}
            <div class="row">
                <div class="col-sm-6">
                    <p><strong><i class="fa fa-user margin-r-5"></i> العميل:</strong></p>
                    <p class="text-muted">{{ $draft->contact->name ?? 'N/A' }}</p>
                    @if($draft->contact && $draft->contact->mobile)
                        <p class="text-muted"><i class="fa fa-phone"></i> {{ $draft->contact->mobile }}</p>
                    @endif
                </div>
                <div class="col-sm-6">
                    <p><strong><i class="fa fa-calendar margin-r-5"></i> التاريخ:</strong></p>
                    <p class="text-muted">{{ \Carbon\Carbon::parse($draft->transaction_date)->format('Y-m-d H:i') }}</p>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6">
                    <p><strong><i class="fa fa-map-marker margin-r-5"></i> الفرع:</strong></p>
                    <p class="text-muted">{{ $draft->location->name ?? 'N/A' }}</p>
                </div>
                <div class="col-sm-6">
                    <p><strong><i class="fa fa-user-circle margin-r-5"></i> أضيفت بواسطة:</strong></p>
                    <p class="text-muted">{{ $draft->created_by_user->username ?? 'N/A' }}</p>
                </div>
            </div>

            <hr>

            {{-- Products Table --}}
            <h4><i class="fa fa-list"></i> المنتجات</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr class="bg-gray">
                            <th width="5%">#</th>
                            <th>المنتج</th>
                            <th width="10%">الكمية</th>
                            <th width="15%">السعر</th>
                            <th width="15%">الضريبة</th>
                            <th width="15%">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $counter = 1; @endphp
                        @foreach($draft->sell_lines as $line)
                            @if(empty($line->parent_sell_line_id))
                                <tr>
                                    <td>{{ $counter++ }}</td>
                                    <td>
                                        {{ $line->product->name ?? 'N/A' }}
                                        @if($line->variations && $line->variations->product_variation)
                                            <br><small class="text-muted">
                                                {{ $line->variations->product_variation->name ?? '' }}: 
                                                {{ $line->variations->name ?? '' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($line->quantity, 2) }}
                                        @if($line->product && $line->product->unit)
                                            {{ $line->product->unit->short_name }}
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($line->unit_price_inc_tax, 2) }}</td>
                                    <td class="text-right">{{ number_format($line->item_tax * $line->quantity, 2) }}</td>
                                    <td class="text-right">
                                        <strong>{{ number_format($line->unit_price_inc_tax * $line->quantity, 2) }}</strong>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="row">
                <div class="col-sm-6 col-sm-offset-6">
                    <table class="table">
                        <tr>
                            <th>الإجمالي قبل الضريبة:</th>
                            <td class="text-right">{{ number_format($draft->total_before_tax, 2) }}</td>
                        </tr>
                        @if($draft->discount_amount > 0)
                        <tr>
                            <th>الخصم:</th>
                            <td class="text-right">(-) {{ number_format($draft->discount_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>الضريبة:</th>
                            <td class="text-right">(+) {{ number_format($draft->tax_amount, 2) }}</td>
                        </tr>
                        @if($draft->shipping_charges > 0)
                        <tr>
                            <th>الشحن:</th>
                            <td class="text-right">(+) {{ number_format($draft->shipping_charges, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="bg-gray">
                            <th><strong>الإجمالي الكلي:</strong></th>
                            <th class="text-right"><strong>{{ number_format($draft->final_total, 2) }}</strong></th>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Notes --}}
            @if($draft->additional_notes)
            <hr>
            <div class="row">
                <div class="col-sm-12">
                    <p><strong><i class="fa fa-sticky-note margin-r-5"></i> ملاحظات:</strong></p>
                    <div class="well well-sm">{{ $draft->additional_notes }}</div>
                </div>
            </div>
            @endif

        </div>

        <div class="modal-footer no-print">
            <button type="button" class="btn btn-default" data-dismiss="modal">
                <i class="fa fa-times"></i> إغلاق
            </button>
            
            @if(!$draft->is_converted)
                <a href="{{ route('drafts.convert', [$draft->id]) }}" class="btn btn-success" 
                   onclick="return confirm('هل تريد تحويل هذه المسودة إلى فاتورة نهائية؟\n\nسيتم:\n- إنشاء فاتورة نهائية\n- خصم المخزون\n- تعليم المسودة كمحولة');">
                    <i class="fa fa-sync-alt"></i> تحويل لفاتورة
                </a>
                
                @if($draft->is_direct_sale)
                    <a href="{{ action([\App\Http\Controllers\SellController::class, 'edit'], [$draft->id]) }}" 
                       class="btn btn-primary" target="_blank">
                        <i class="fa fa-edit"></i> تعديل
                    </a>
                @else
                    <a href="{{ action([\App\Http\Controllers\SellPosController::class, 'edit'], [$draft->id]) }}" 
                       class="btn btn-primary" target="_blank">
                        <i class="fa fa-edit"></i> تعديل
                    </a>
                @endif
            @endif
        </div>
    </div>
</div>
