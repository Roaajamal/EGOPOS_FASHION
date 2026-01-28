<div class="modal fade" tabindex="-1" role="dialog" id="import_new_quantity_products_modal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="import_new_quantity_form" action="{{ route('quantity_entry.import') }}"
                                                method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">@lang('quantity_entry.import_quantities_entry')</h4>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <strong>@lang('product.file_to_import'):</strong>
                            <input type="file" name="file" id="import_new_quantity_file" class="form-control" required>
                        </div>
                        <div class="col-md-12 mt-10">
                            <a href="{{ asset('files/import_new_quantity_template.xls') }}" class="tw-dw-btn tw-dw-btn-success tw-text-white" download>
                                <i class="fa fa-download"></i> @lang('lang_v1.download_template_file')
                            </a>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="import_new_quantity_products">
                        @lang('lang_v1.import')
                    </button>
                    <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">
                        @lang('messages.close')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>