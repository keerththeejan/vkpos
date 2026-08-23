@extends('layouts.app')
@section('title', __('business.business_settings'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/business-settings-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')

<section class="content bs-shell" id="bs_shell">

    <div class="bs-header no-print" role="banner">
        <div class="bs-header-left">
            <h1>
                <span class="bs-title-icon" aria-hidden="true"><i class="bi bi-sliders"></i></span>
                @lang('business.business_settings')
            </h1>
            <p class="bs-subtitle">Configure VKPOS business, POS, sales, purchasing, inventory and financial preferences</p>
            <nav class="bs-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ url('/home') }}"><i class="bi bi-house" aria-hidden="true"></i> {{ __('home.home') }}</a>
                <span class="bs-crumb-sep" aria-hidden="true">›</span>
                <span>Settings</span>
                <span class="bs-crumb-sep" aria-hidden="true">›</span>
                <span class="bs-crumb-current" aria-current="page">@lang('business.business_settings')</span>
            </nav>
        </div>
        <div class="bs-header-actions">
            <div class="bs-search-host" role="search">
                @include('layouts.partials.search_settings')
            </div>
            <button type="button" class="bs-btn bs-btn-icon" id="bs_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="bs-btn bs-btn-primary" id="bs_header_save" aria-label="@lang('business.update_settings')">
                <i class="bi bi-check2-circle"></i> @lang('business.update_settings')
            </button>
        </div>
        <label class="bs-mobile-nav-wrap" for="bs_mobile_nav">
            <span class="bs-mobile-nav-label">Section</span>
            <select id="bs_mobile_nav" class="form-control" aria-label="Settings section">
                <option value="0">@lang('business.business')</option>
                <option value="1">@lang('business.tax')</option>
                <option value="2">@lang('business.product')</option>
                <option value="3">@lang('contact.contact')</option>
                <option value="4">@lang('business.sale')</option>
                <option value="5">@lang('sale.pos_sale')</option>
                <option value="6">@lang('purchase.purchases')</option>
                <option value="7">@lang('lang_v1.payment')</option>
                <option value="8">@lang('business.dashboard')</option>
                <option value="9">@lang('business.system')</option>
                <option value="10">@lang('lang_v1.prefixes')</option>
                <option value="11">@lang('lang_v1.email_settings')</option>
                <option value="12">@lang('lang_v1.sms_settings')</option>
                <option value="13">@lang('lang_v1.reward_point_settings')</option>
                <option value="14">@lang('lang_v1.modules')</option>
                <option value="15">@lang('lang_v1.custom_labels')</option>
            </select>
        </label>
        <div class="bs-header-meta" aria-label="Status">
            <div class="bs-meta" id="bs_dirty_meta" hidden><span>Unsaved changes</span></div>
        </div>
    </div>

{!! Form::open(['url' => action([\App\Http\Controllers\BusinessController::class, 'postBusinessSettings']), 'method' => 'post', 'id' => 'bussiness_edit_form',
           'files' => true ]) !!}
    <div class="row">
        <div class="col-xs-12">
        @component('components.widget', ['class' =>  'pos-tab-container'])
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 pos-tab-menu tw-rounded-lg">
                <div class="list-group">
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base  active"><i class="bi bi-building" aria-hidden="true"></i> @lang('business.business')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-percent" aria-hidden="true"></i> @lang('business.tax') @show_tooltip(__('tooltip.business_tax'))</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-box-seam" aria-hidden="true"></i> @lang('business.product')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-people" aria-hidden="true"></i> @lang('contact.contact')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-receipt" aria-hidden="true"></i> @lang('business.sale')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-cart" aria-hidden="true"></i> @lang('sale.pos_sale')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-bag" aria-hidden="true"></i> @lang('purchase.purchases')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-credit-card" aria-hidden="true"></i> @lang('lang_v1.payment')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-speedometer2" aria-hidden="true"></i> @lang('business.dashboard')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-gear" aria-hidden="true"></i> @lang('business.system')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-hash" aria-hidden="true"></i> @lang('lang_v1.prefixes')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-envelope" aria-hidden="true"></i> @lang('lang_v1.email_settings')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-chat-dots" aria-hidden="true"></i> @lang('lang_v1.sms_settings')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-star" aria-hidden="true"></i> @lang('lang_v1.reward_point_settings')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-puzzle" aria-hidden="true"></i> @lang('lang_v1.modules')</a>
                    <a href="#" class="list-group-item text-center tw-font-bold tw-text-sm md:tw-text-base"><i class="bi bi-tags" aria-hidden="true"></i> @lang('lang_v1.custom_labels')</a>
                </div>
            </div>
            <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                <!-- tab 1 start -->
                @include('business.partials.settings_business')
                <!-- tab 1 end -->
                <!-- tab 2 start -->
                @include('business.partials.settings_tax')
                <!-- tab 2 end -->
                <!-- tab 3 start -->
                @include('business.partials.settings_product')

                @include('business.partials.settings_contact')
                <!-- tab 3 end -->
                <!-- tab 4 start -->
                @include('business.partials.settings_sales')
                @include('business.partials.settings_pos')
                <!-- tab 4 end -->
                <!-- tab 5 start -->
                @include('business.partials.settings_purchase')

                @include('business.partials.settings_payment')
                <!-- tab 5 end -->
                <!-- tab 6 start -->
                @include('business.partials.settings_dashboard')
                <!-- tab 6 end -->
                <!-- tab 7 start -->
                @include('business.partials.settings_system')
                <!-- tab 7 end -->
                <!-- tab 8 start -->
                @include('business.partials.settings_prefixes')
                <!-- tab 8 end -->
                <!-- tab 9 start -->
                @include('business.partials.settings_email')
                <!-- tab 9 end -->
                <!-- tab 10 start -->
                @include('business.partials.settings_sms')
                <!-- tab 10 end -->
                <!-- tab 11 start -->
                @include('business.partials.settings_reward_point')
                <!-- tab 11 end -->
                <!-- tab 12 start -->
                @include('business.partials.settings_modules')
                <!-- tab 12 end -->
                @include('business.partials.settings_custom_labels')
            </div>
        @endcomponent
        </div>
    </div>

    <div class="bs-save-bar">
        <p id="bs_save_hint" data-default="Changes are saved to VKPOS business configuration">Changes are saved to VKPOS business configuration</p>
        <div class="bs-save-actions">
            <button type="button" class="bs-btn" id="bs_cancel_btn">@lang('messages.cancel')</button>
            <button class="tw-dw-btn tw-dw-btn-error tw-dw-btn-lg tw-text-white" type="submit" id="bs_save_btn">
                <span class="bs-save-label">@lang('business.update_settings')</span>
            </button>
        </div>
    </div>
{!! Form::close() !!}

    <div class="bs-toast-host" id="bs_toast_host" aria-live="polite"></div>
</section>
@endsection
@section('javascript')
<script type="text/javascript">
    __page_leave_confirmation('#bussiness_edit_form');
    $(document).on('ifToggled', '#use_superadmin_settings', function() {
        if ($('#use_superadmin_settings').is(':checked')) {
            $('#toggle_visibility').addClass('hide');
            $('.test_email_btn').addClass('hide');
        } else {
            $('#toggle_visibility').removeClass('hide');
            $('.test_email_btn').removeClass('hide');
        }
    });

    $(document).ready(function(){

    
        $('#test_email_btn').click( function() {
            var data = {
                mail_driver: $('#mail_driver').val(),
                mail_host: $('#mail_host').val(),
                mail_port: $('#mail_port').val(),
                mail_username: $('#mail_username').val(),
                mail_password: $('#mail_password').val(),
                mail_encryption: $('#mail_encryption').val(),
                mail_from_address: $('#mail_from_address').val(),
                mail_from_name: $('#mail_from_name').val(),
            };
            $.ajax({
                method: 'post',
                data: data,
                url: "{{ action([\App\Http\Controllers\BusinessController::class, 'testEmailConfiguration']) }}",
                dataType: 'json',
                success: function(result) {
                    if (result.success == true) {
                        swal({
                            text: result.msg,
                            icon: 'success'
                        });
                    } else {
                        swal({
                            text: result.msg,
                            icon: 'error'
                        });
                    }
                },
            });
        });

        $('#test_sms_btn').click( function() {
            var test_number = $('#test_number').val();
            if (test_number.trim() == '') {
                toastr.error('{{__("lang_v1.test_number_is_required")}}');
                $('#test_number').focus();

                return false;
            }

            var data = {
                url: $('#sms_settings_url').val(),
                send_to_param_name: $('#send_to_param_name').val(),
                msg_param_name: $('#msg_param_name').val(),
                request_method: $('#request_method').val(),
                param_1: $('#sms_settings_param_key1').val(),
                param_2: $('#sms_settings_param_key2').val(),
                param_3: $('#sms_settings_param_key3').val(),
                param_4: $('#sms_settings_param_key4').val(),
                param_5: $('#sms_settings_param_key5').val(),
                param_6: $('#sms_settings_param_key6').val(),
                param_7: $('#sms_settings_param_key7').val(),
                param_8: $('#sms_settings_param_key8').val(),
                param_9: $('#sms_settings_param_key9').val(),
                param_10: $('#sms_settings_param_key10').val(),

                param_val_1: $('#sms_settings_param_val1').val(),
                param_val_2: $('#sms_settings_param_val2').val(),
                param_val_3: $('#sms_settings_param_val3').val(),
                param_val_4: $('#sms_settings_param_val4').val(),
                param_val_5: $('#sms_settings_param_val5').val(),
                param_val_6: $('#sms_settings_param_val6').val(),
                param_val_7: $('#sms_settings_param_val7').val(),
                param_val_8: $('#sms_settings_param_val8').val(),
                param_val_9: $('#sms_settings_param_val9').val(),
                param_val_10: $('#sms_settings_param_val10').val(),
                test_number: test_number
            };

            $.ajax({
                method: 'post',
                data: data,
                url: "{{ action([\App\Http\Controllers\BusinessController::class, 'testSmsConfiguration']) }}",
                dataType: 'json',
                success: function(result) {
                    if (result.success == true) {
                        swal({
                            text: result.msg,
                            icon: 'success'
                        });
                    } else {
                        swal({
                            text: result.msg,
                            icon: 'error'
                        });
                    }
                },
            });

        });

        $('select.custom_labels_products').change(function(){
            value = $(this).val();
            textarea = $(this).parents('div.custom_label_product_div').find('div.custom_label_product_dropdown');
            if(value == 'dropdown'){
                textarea.removeClass('hide');
            } else{
                textarea.addClass('hide');
            }
        })
    });
</script>
<script src="{{ asset('js/business-settings-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
