@extends('layouts.admin')

@section('title', trans('general.title.edit', ['type' => trans_choice('general.bills', 1)]))

@section('content')
    <div class="card">
        {!! Form::model($bill, [
            'id' => 'bill',
            'method' => 'PATCH',
            'route' => ['bills.update', $bill->id],
            '@submit.prevent' => 'onSubmit',
            '@keydown' => 'form.errors.clear($event.target.name)',
            'files' => true,
            'role' => 'form',
            'class' => 'form-loading-button',
            'novalidate' => true
        ]) !!}

            <div class="card-body">
                <div class="row">
                    {{ Form::selectAddNewGroup('contact_id', trans_choice('general.vendors', 1), 'user', $vendors, $bill->contact_id, ['required' => 'required', 'path' => route('modals.vendors.create'), 'change' => 'onChangeContact']) }}

                    {{ Form::selectGroup('currency_code', trans_choice('general.currencies', 1), 'exchange', $currencies, $bill->currency_code, ['required' => 'required', 'change' => 'onChangeCurrency']) }}

                    {{ Form::dateGroup('billed_at', trans('bills.bill_date'), 'calendar', ['id' => 'billed_at', 'class' => 'form-control datepicker', 'required' => 'required', 'date-format' => 'Y-m-d', 'autocomplete' => 'off'], Date::parse($bill->billed_at)->toDateString()) }}

                    {{ Form::dateGroup('due_at', trans('bills.due_date'), 'calendar', ['id' => 'due_at', 'class' => 'form-control datepicker', 'required' => 'required', 'date-format' => 'Y-m-d', 'autocomplete' => 'off'], Date::parse($bill->due_at)->toDateString()) }}

                    {{ Form::textGroup('bill_number', trans('bills.bill_number'), 'file-text-o') }}

                    {{ Form::textGroup('order_number', trans('bills.order_number'), 'shopping-cart',[]) }}

                    <div class="col-md-12 mb-4">
                        {!! Form::label('items', trans_choice('general.items', 2), ['class' => 'control-label']) !!}
                        <div class="table-responsive overflow-x-scroll overflow-y-hidden">
                            <table class="table table-bordered" id="items">
                                <thead class="thead-light">
                                    <tr class="d-flex flex-nowrap">
                                        @stack('actions_th_start')
                                            <th class="col-md-1 action-column border-right-0 border-bottom-0">{{ trans('general.actions') }}</th>
                                        @stack('actions_th_end')

                                        @stack('name_th_start')
                                            <th class="col-md-3 text-left border-right-0 border-bottom-0">{{ trans('general.name') }}</th>
                                        @stack('name_th_end')

                                        @stack('quantity_th_start')
                                            <th class="col-md-1 text-center border-right-0 border-bottom-0">{{ trans('bills.quantity') }}</th>
                                        @stack('quantity_th_end')

                                        @stack('price_th_start')
                                            <th class="col-md-2 text-right border-right-0 border-bottom-0">{{ trans('bills.price') }}</th>
                                        @stack('price_th_end')

                                        @stack('taxes_th_start')
                                            <th class="col-md-3 text-right border-right-0 border-bottom-0">{{ trans_choice('general.taxes', 1) }}</th>
                                        @stack('taxes_th_end')

                                        @stack('total_th_start')
                                            <th class="col-md-2 text-right border-bottom-0">{{ trans('bills.total') }}</th>
                                        @stack('total_th_end')
                                    </tr>
                                </thead>
                                <tbody>
                                    @include('purchases.bills.item')

                                    @stack('add_item_td_start')
                                        <tr class="row" id="addItem">
                                            <td class="col-md-1 action-column border-right-0 border-bottom-0">
                                                <button type="button" @click="onAddItem" id="button-add-item" data-toggle="tooltip" title="{{ trans('general.add') }}" class="btn btn-icon btn-outline-success btn-lg" data-original-title="{{ trans('general.add') }}"><i class="fa fa-plus"></i>
                                                </button>
                                            </td>
                                            <td class="col-md-11 text-right border-bottom-0"></td>
                                        </tr>
                                    @stack('add_item_td_end')

                                    @stack('sub_total_td_start')
                                        <tr class="row" id="tr-subtotal">
                                            <td class="col-md-10 text-right border-right-0 border-bottom-0">
                                                <strong>{{ trans('bills.sub_total') }}</strong>
                                            </td>
                                            <td class="col-md-2 text-right border-bottom-0 long-texts">
                                                {{ Form::moneyGroup('sub_total', '', '', ['disabled' => 'disabled', 'required' => 'required', 'v-model' => 'totals.sub', 'currency' => $currency, 'dynamic-currency' => 'currency', 'masked' => 'true'], 0.00, 'text-right d-none') }}
                                                <span id="sub-total" v-html="totals.sub">0</span>
                                            </td>
                                        </tr>
                                    @stack('sub_total_td_end')

                                    @stack('add_discount_td_start')
                                        <tr class="row" id="tr-discount">
                                            <td class="col-md-10 text-right border-right-0 border-bottom-0">
                                                <el-popover
                                                    popper-class="p-0 h-0"
                                                    placement="bottom"
                                                    width="300"
                                                    v-model="discount">
                                                    <div class="card">
                                                        <div class="discount card-body">
                                                            <div class="row align-items-center">
                                                                <div class="col-md-6">
                                                                    <div class="input-group input-group-merge">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text" id="input-discount">
                                                                                <i class="fa fa-percent"></i>
                                                                            </span>
                                                                        </div>
                                                                        {!! Form::number('pre_discount', null, ['id' => 'pre-discount', 'class' => 'form-control text-right']) !!}
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="discount-description">
                                                                        <strong>{{ trans('invoices.discount_desc') }}</strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="discount card-footer">
                                                            <div class="row text-center">
                                                                <div class="col-md-12">
                                                                    <a href="javascript:void(0)" @click="discount = false" class="btn btn-icon btn-outline-secondary">
                                                                        <span class="btn-inner--icon"><i class="fas fa-times"></i></span>
                                                                        <span class="btn-inner--text">{{ trans('general.cancel') }}</span>
                                                                    </a>
                                                                    {!! Form::button('<span class="fa fa-save"></span> &nbsp;' . trans('general.save'), ['type' => 'button', 'id' => 'save-discount', '@click' => 'onAddDiscount', 'class' => 'btn btn-success']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <el-link class="cursor-pointer text-info" slot="reference" type="primary" v-if="!totals.discount_text">{{ trans('invoices.add_discount') }}</el-link>
                                                    <el-link slot="reference" type="primary" v-if="totals.discount_text" v-html="totals.discount_text"></el-link>
                                                </el-popover>
                                            </td>
                                            <td class="col-md-2 text-right border-bottom-0">
                                                <span id="discount-total" v-html="totals.discount"></span>
                                                {!! Form::hidden('discount', null, ['id' => 'discount', 'class' => 'form-control text-right', 'v-model' => 'form.discount']) !!}
                                            </td>
                                        </tr>
                                    @stack('add_discount_td_end')

                                    @stack('tax_total_td_start')
                                        <tr class="row" id="tr-tax">
                                            <td class="col-md-10 text-right border-right-0 border-bottom-0">
                                                <strong>{{ trans_choice('general.taxes', 1) }}</strong>
                                            </td>
                                            <td class="col-md-2 text-right border-bottom-0 long-texts">
                                                {{ Form::moneyGroup('tax_total', '', '', ['disabled' => 'disabled', 'required' => 'required', 'v-model' => 'totals.tax', 'currency' => $currency, 'dynamic-currency' => 'currency', 'masked' => 'true'], 0.00, 'text-right d-none') }}
                                                <span id="tax-total" v-html="totals.tax">0</span>
                                            </td>
                                        </tr>
                                    @stack('tax_total_td_end')

                                    @stack('grand_total_td_start')
                                        <tr class="row" id="tr-total">
                                            <td class="col-md-10 text-right border-right-0">
                                                <strong>{{ trans('bills.total') }}</strong>
                                            </td>
                                            <td class="col-md-2 text-right long-texts">
                                                {{ Form::moneyGroup('grand_total', '', '', ['disabled' => 'disabled', 'required' => 'required', 'v-model' => 'totals.total', 'currency' => $currency, 'dynamic-currency' => 'currency', 'masked' => 'true'], 0.00, 'text-right d-none') }}
                                                <span id="grand-total" v-html="totals.total">0</span>
                                            </td>
                                        </tr>
                                    @stack('grand_total_td_end')
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{ Form::textareaGroup('notes', trans_choice('general.notes', 2)) }}

                    {{ Form::selectAddNewGroup('category_id', trans_choice('general.categories', 1), 'folder', $categories, $bill->category_id, ['required' => 'required', 'path' => route('modals.categories.create') . '?type=expense']) }}

                    {{ Form::recurring('edit', $bill) }}

                    {{ Form::fileGroup('attachment', trans('general.attachment')) }}

                    {{ Form::hidden('contact_name', old('contact_name'), ['id' => 'contact_name', 'v-model' => 'form.contact_name']) }}
                    {{ Form::hidden('contact_email', old('contact_email'), ['id' => 'contact_email', 'v-model' => 'form.contact_email']) }}
                    {{ Form::hidden('contact_tax_number', old('contact_tax_number'), ['id' => 'contact_tax_number', 'v-model' => 'form.contact_tax_number']) }}
                    {{ Form::hidden('contact_phone', old('contact_phone'), ['id' => 'contact_phone', 'v-model' => 'form.contact_phone']) }}
                    {{ Form::hidden('contact_address', old('contact_address'), ['id' => 'contact_address', 'v-model' => 'form.contact_address']) }}
                    {{ Form::hidden('currency_rate', old('currency_rate', 1), ['id' => 'currency_rate', 'v-model' => 'form.contact_rate']) }}
                    {{ Form::hidden('status', old('status', 'draft'), ['id' => 'status', 'v-model' => 'form.status']) }}
                    {{ Form::hidden('amount', old('amount', '0'), ['id' => 'amount', 'v-model' => 'form.amount']) }}
                </div>
            </div>

            @permission('update-purchases-bills')
                <div class="card-footer">
                    <div class="row float-right">
                        {{ Form::saveButtons('bills.index') }}
                    </div>
                </div>
            @endpermission
        {!! Form::close() !!}
    </div>
@endsection

@push('scripts_start')
    <script type="text/javascript">
        var bill_items = {!! json_encode($bill->items()->get()) !!};
    </script>

    <script src="{{ asset('public/js/purchases/bills.js?v=' . version('short')) }}"></script>
@endpush
