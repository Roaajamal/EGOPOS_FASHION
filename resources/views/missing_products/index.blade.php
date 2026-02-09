@section('css')
<style>
    /* حاوية التحكم بالجدول */
    .table-controls {
        display: flex;
        justify-content: space-between; /* توزيع العناصر على الأطراف */
        align-items: center;
        margin-bottom: 15px;
    }

    /* جعل الأزرار في المنتصف */
    .dt-buttons {
        flex-grow: 1;
        text-align: center;
        margin-left: 180px; /* موازنة لإزاحة خانة البحث */
    }

    /* جعل خانة البحث على اليسار */
    .dataTables_filter {
        text-align: left;
    }

    .dataTables_filter input {
        margin-right: 10px;
        display: inline-block;
        width: auto;
    }
</style>
@endsection

@extends('layouts.app')
@section('title', __('missing_product.missing_product_report') )

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black"> {{ __('missing_product.missing_product_report') }}  </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {!! Form::open(['url' => action([\App\Http\Controllers\MissingProductController::class, 'getMissingProducts']), 'method' => 'get', 'id' => 'missing_products_filter_form']) !!}
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id_1',  __('missing_product.source_branch') ) !!}
                        {!! Form::select('location_id_1', $business_locations, request()->get('location_id_1'), ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'required']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id_2',  __('missing_product.target_branch') ) !!}
                        {!! Form::select('location_id_2', $business_locations, request()->get('location_id_2'), ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'required']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary" style="margin-top: 25px;">
                        <i class="fa fa-filter"></i> {{__('missing_product.view_results') }}
                    </button>
                </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary','title' => __('missing_product.missing_product_report') ])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="missing_products_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>{{__('missing_product.product_name') }} </th>
                                <th> (SKU) </th>
                               <th>{{ __('missing_product.quantity_in_location', ['location' => $loc1_name]) }}</th>
                               <th>{{ __('missing_product.quantity_in_location', ['location' => $loc2_name]) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($missingProducts as $product)
                                <tr>
        <td>{{ $product->name }}</td>
        <td>{{ $product->sku }}</td>
        <td class="text-success text-bold">
            {{ number_format($product->qty_in_loc1, 2) }}
        </td>
        <td>
            @if(is_null($product->qty_in_loc2))
                {{-- الحالة 1: المنتج ليس له سجل نهائياً في الفرع الثاني --}}
                <span class="label label-default">غير متوفر</span>
            @elseif($product->qty_in_loc2 == 0)
                {{-- الحالة 2: المنتج موجود وسجله صفر --}}
                <span class="text-bold">0.00</span>
            @elseif($product->qty_in_loc2 < 0)
                {{-- الحالة 3: المنتج موجود وقيمته سالبة (عجز) --}}
                <span class="text-danger text-bold">
                    {{ number_format($product->qty_in_loc2, 2) }} 
                    <small>(عجز)</small>
                </span>
            @endif
        </td>
    </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // تعريف DataTable مع أزرار التصدير
        if ($('#missing_products_table').length) {
            $('#missing_products_table').DataTable({
                dom: '<"row"<"col-md-12"<"table-controls"Bf>>>rtip', // تفعيل الأزرار
               
                
            });
        }
    });
</script>
@endsection