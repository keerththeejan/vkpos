@php
    $nt_icons = [
        'send_ledger' => 'bi-journal-text',
        'new_sale' => 'bi-receipt',
        'payment_received' => 'bi-cash-coin',
        'payment_reminder' => 'bi-bell',
        'new_booking' => 'bi-calendar-check',
        'new_quotation' => 'bi-file-earmark-text',
        'new_order' => 'bi-bag',
        'payment_paid' => 'bi-wallet2',
        'items_received' => 'bi-box-seam',
        'items_pending' => 'bi-hourglass-split',
        'purchase_order' => 'bi-clipboard-check',
    ];
    $nt_group = $nt_group ?? 'general';
@endphp
<!-- Custom Tabs -->
<div class="nav-tabs-custom" data-nt-group="{{ $nt_group }}">
    <ul class="nav nav-tabs">
        @foreach($templates as $key => $value)
            @php
                $nt_tag_flat = [];
                if (!empty($value['extra_tags']) && is_array($value['extra_tags'])) {
                    foreach ($value['extra_tags'] as $nt_row) {
                        if (is_array($nt_row)) {
                            $nt_tag_flat = array_merge($nt_tag_flat, $nt_row);
                        } else {
                            $nt_tag_flat[] = $nt_row;
                        }
                    }
                }
                $nt_search = strtolower(($value['name'] ?? '') . ' ' . $key . ' ' . ($value['subject'] ?? '') . ' ' . implode(' ', $nt_tag_flat) . ' ' . $nt_group);
                $nt_icon = $nt_icons[$key] ?? 'bi-envelope';
                $nt_has_sms = ($key == 'send_ledger') ? '0' : '1';
            @endphp
            <li @if($loop->index == 0) class="active" @endif
                data-nt-key="{{ $key }}"
                data-nt-group="{{ $nt_group }}"
                data-nt-search="{{ $nt_search }}"
                data-nt-sms="{{ $nt_has_sms }}"
                data-nt-auto-email="{{ !empty($value['auto_send']) ? '1' : '0' }}"
                data-nt-auto-sms="{{ !empty($value['auto_send_sms']) ? '1' : '0' }}"
                data-nt-auto-wa="{{ !empty($value['auto_send_wa_notif']) ? '1' : '0' }}">
                <a href="#cn_{{$key}}" data-toggle="tab" aria-expanded="true">
                    <span class="nt-tab-icon" aria-hidden="true"><i class="bi {{ $nt_icon }}"></i></span>
                    <span class="nt-tab-label">{{$value['name']}}</span>
                    @if(!empty($value['auto_send']) || !empty($value['auto_send_sms']) || !empty($value['auto_send_wa_notif']))
                        <span class="nt-tab-auto">Auto</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
    <div class="tab-content">
        @foreach($templates as $key => $value)
            <div class="tab-pane @if($loop->index == 0) active @endif" id="cn_{{$key}}">
                <div class="row">
                <div class="col-md-12">
                    @if(!empty($value['extra_tags']))
                        <div class="nt-tags-panel">
                            <div class="nt-tags-head">
                                <strong>@lang('lang_v1.available_tags'):</strong>
                                <input type="search" class="nt-var-search form-control" placeholder="{{ __('lang_v1.search') }}…" autocomplete="off" aria-label="Search available tags">
                            </div>
                            @include('notification_template.partials.tags', ['tags' => $value['extra_tags']])
                            <p class="nt-tag-hint">Click a tag to copy. Format stays exactly as shown.</p>
                        </div>
                    @endif
                    @if(!empty($value['help_text']))
                    <p class="help-block">{{$value['help_text']}}</p>
                    @endif
                </div>
                <div class="col-md-12 mt-10 nt-channel-email">
                    <div class="form-group">
                        {!! Form::label($key . '_subject',
                        __('lang_v1.email_subject').':') !!}
                        {!! Form::text('template_data[' . $key . '][subject]', 
                        $value['subject'], ['class' => 'form-control nt-sync'
                        , 'placeholder' => __('lang_v1.email_subject'), 'id' => $key . '_subject']); !!}
                    </div>
                </div>
                <div class="col-md-6 nt-channel-email">
                    <div class="form-group">
                        {!! Form::label($key . '_cc',
                        'CC:') !!}
                        {!! Form::email('template_data[' . $key . '][cc]', 
                        $value['cc'], ['class' => 'form-control'
                        , 'placeholder' => 'CC', 'id' => $key . '_cc']); !!}
                    </div>
                </div>
                <div class="col-md-6 nt-channel-email">
                    <div class="form-group">
                        {!! Form::label($key . '_bcc',
                        'BCC:') !!}
                        {!! Form::email('template_data[' . $key . '][bcc]', 
                        $value['bcc'], ['class' => 'form-control'
                        , 'placeholder' => 'BCC', 'id' => $key . '_bcc']); !!}
                    </div>
                </div>
                <div class="col-md-12 nt-channel-email">
                    <div class="form-group">
                        {!! Form::label($key . '_email_body',
                        __('lang_v1.email_body').':') !!}
                        {!! Form::textarea('template_data[' . $key . '][email_body]', 
                        $value['email_body'], ['class' => 'form-control ckeditor nt-sync'
                        , 'placeholder' => __('lang_v1.email_body'), 'id' => $key . '_email_body', 'rows' => 6]); !!}
                    </div>
                </div>
                <div class="col-md-12 @if($key == 'send_ledger') hide @endif nt-channel-sms">
                    <div class="form-group">
                        {!! Form::label($key . '_sms_body',
                        __('lang_v1.sms_body').':') !!}
                        <span class="nt-sms-count" data-nt-for="{{ $key }}_sms_body" aria-live="polite"></span>
                        {!! Form::textarea('template_data[' . $key . '][sms_body]', 
                        $value['sms_body'], ['class' => 'form-control nt-sync'
                        , 'placeholder' => __('lang_v1.sms_body'), 'id' => $key . '_sms_body', 'rows' => 6]); !!}
                    </div>
                </div>
                <div class="col-md-12 @if($key == 'send_ledger') hide @endif nt-channel-wa">
                    <div class="form-group">
                        {!! Form::label($key . '_whatsapp_text',
                        __('lang_v1.whatsapp_text').':') !!}
                        {!! Form::textarea('template_data[' . $key . '][whatsapp_text]', 
                        $value['whatsapp_text'], ['class' => 'form-control nt-sync'
                        , 'placeholder' => __('lang_v1.whatsapp_text'), 'id' => $key . '_whatsapp_text', 'rows' => 6]); !!}
                    </div>
                </div>
                @if($key == 'new_sale' || $key == 'payment_reminder')
                    <div class="col-md-12 mt-15">
                        <div class="form-group nt-autosend">
                            <label class="checkbox-inline">
                                {!! Form::checkbox('template_data[' . $key . '][auto_send]', 1, $value['auto_send'], ['class' => 'input-icheck']); !!} @lang('lang_v1.autosend_email')
                            </label>
                            <label class="checkbox-inline">
                                {!! Form::checkbox('template_data[' . $key . '][auto_send_sms]', 1, $value['auto_send_sms'], ['class' => 'input-icheck']); !!} @lang('lang_v1.autosend_sms')
                            </label>
                            <label class="checkbox-inline">
                                {!! Form::checkbox('template_data[' . $key . '][auto_send_wa_notif]', 1, $value['auto_send_wa_notif'], ['class' => 'input-icheck']); !!} @lang('lang_v1.auto_send_wa_notif')
                            </label>
                        </div>
                        @if($key == 'payment_reminder')
                            <p class="help-block">@lang('lang_v1.payment_reminder_help')</p>

                        @elseif($key == 'new_sale')
                            <p class="help-block">@lang('lang_v1.new_sale_notification_help')</p>
                        @endif
                    </div>
                @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
