@extends('layouts.app')
@section('title', __('lang_v1.add_opening_stock'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.add_opening_stock')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action([\App\Http\Controllers\OpeningStockController::class, 'save']), 'method' => 'post', 'id' => 'add_opening_stock_form' ]) !!}
	{!! Form::hidden('product_id', $product->id); !!}
	@include('opening_stock.form-part')
	<div class="row">
		<div class="col-sm-12 text-center">
			<button type="submit" class="btn btn-primary btn-big">🖨️ حفظ وطباعة</button>
		</div>
	</div>

	{!! Form::close() !!}
</section>
@stop
@section('javascript')
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
	<script type="text/javascript">
		$(document).ready( function(){
			$('.os_date').datetimepicker({
		        format: moment_date_format + ' ' + moment_time_format,
		        ignoreReadonly: true,
		        widgetPositioning: {
		            horizontal: 'right',
		            vertical: 'bottom'
		        }
		    });

			// إرسال عبر AJAX ثم iframe مخفي للطباعة (مثل المتباين) حتى يصل الأمر للطابعة
			$('#add_opening_stock_form').on('submit', function(e) {
				e.preventDefault();
				var form = $(this);
				var btn = form.find('button[type="submit"]');
				btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');
				$.ajax({
					url: form.attr('action'),
					type: 'POST',
					data: new FormData(this),
					processData: false,
					contentType: false,
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
					success: function(data) {
						if (data.success) {
							if (data.print_url && data.redirect_url) {
								var iframe = document.createElement('iframe');
								iframe.setAttribute('style', 'position:absolute;left:-9999px;top:0;width:1px;height:1px;visibility:hidden;border:0');
								document.body.appendChild(iframe);
								iframe.src = data.print_url;
								setTimeout(function() {
									try { if (iframe.parentNode) iframe.parentNode.removeChild(iframe); } catch (err) {}
									window.location.href = data.redirect_url;
								}, 8000);
							} else if (data.redirect_url) {
								window.location.href = data.redirect_url;
							} else {
								window.location.href = '{{ url("products") }}';
							}
						} else {
							toastr.error(data.msg || 'حدث خطأ');
							btn.prop('disabled', false).html('🖨️ حفظ وطباعة');
						}
					},
					error: function(xhr) {
						var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'حدث خطأ أثناء الحفظ';
						toastr.error(msg);
						btn.prop('disabled', false).html('🖨️ حفظ وطباعة');
					}
				});
				return false;
			});
		});
	</script>
@endsection
