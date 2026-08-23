@extends('layouts.app')
@section('title', __('lang_v1.notification_templates'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/notification-templates-premium.css?v=' . $asset_v) }}">
@endsection

@section('content')
@php
    $nt_general_count = is_array($general_notifications) ? count($general_notifications) : 0;
    $nt_customer_count = is_array($customer_notifications) ? count($customer_notifications) : 0;
    $nt_supplier_count = is_array($supplier_notifications) ? count($supplier_notifications) : 0;
    $nt_total = $nt_general_count + $nt_customer_count + $nt_supplier_count;
    $nt_sms_count = $nt_total;
    if (isset($general_notifications['send_ledger'])) {
        $nt_sms_count = max(0, $nt_total - 1);
    }
    $nt_autosend = 0;
    foreach ([$general_notifications, $customer_notifications, $supplier_notifications] as $nt_group_data) {
        if (!is_array($nt_group_data)) {
            continue;
        }
        foreach ($nt_group_data as $nt_item) {
            if (!empty($nt_item['auto_send']) || !empty($nt_item['auto_send_sms']) || !empty($nt_item['auto_send_wa_notif'])) {
                $nt_autosend++;
            }
        }
    }
@endphp

<section class="content nt-shell" id="nt_shell">

    <div class="nt-header no-print" role="banner">
        <div class="nt-header-left">
            <h1>
                <span class="nt-title-icon" aria-hidden="true"><i class="bi bi-envelope-paper"></i></span>
                {{ __('lang_v1.notification_templates') }}
            </h1>
            <p class="nt-subtitle">Manage system messages, communication templates and notification content</p>
            <nav class="nt-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span aria-hidden="true">/</span>
                <span>Settings</span>
                <span aria-hidden="true">/</span>
                <span>{{ __('lang_v1.notification_templates') }}</span>
            </nav>
        </div>
        <div class="nt-header-actions">
            <div class="nt-search-wrap" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="nt_quick_search" class="form-control" placeholder="{{ __('lang_v1.search') }} templates…" aria-label="Search templates" autocomplete="off">
                <button type="button" class="nt-search-clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <button type="button" class="nt-btn nt-btn-icon" id="nt_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="bi bi-moon-stars"></i>
            </button>
            <button type="button" class="nt-btn nt-btn-icon" id="nt_settings_toggle" title="Settings" aria-label="View settings" aria-expanded="false" aria-controls="nt_settings_panel">
                <i class="bi bi-gear"></i>
            </button>
            <button type="button" class="nt-btn nt-btn-icon" id="nt_fullscreen" title="Fullscreen" aria-label="Toggle fullscreen">
                <i class="bi bi-fullscreen"></i>
            </button>
            <button type="button" class="nt-btn" id="nt_refresh" title="Refresh" aria-label="Refresh templates">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
        <div class="nt-header-meta" aria-label="Template summary">
            <div class="nt-meta"><span>Templates</span> <strong>{{ $nt_total }}</strong></div>
            <div class="nt-meta"><span>Visible</span> <strong id="nt_meta_visible">{{ $nt_total }}</strong></div>
        </div>
        <div class="nt-settings-panel" id="nt_settings_panel" role="dialog" aria-label="View settings" hidden>
            <label><input type="checkbox" id="nt_set_hide_kpis"> Hide summary cards</label>
            <label><input type="checkbox" id="nt_set_hide_preview"> Hide live preview</label>
        </div>
    </div>

    <div class="nt-kpi-grid no-print" aria-label="Template summary">
        <div class="nt-kpi tone-blue">
            <div class="nt-kpi-icon"><i class="bi bi-collection"></i></div>
            <span class="nt-kpi-label">Total templates</span>
            <span class="nt-kpi-value">{{ $nt_total }}</span>
            <span class="nt-kpi-hint">Returned by the system</span>
        </div>
        <div class="nt-kpi tone-green">
            <div class="nt-kpi-icon"><i class="bi bi-people"></i></div>
            <span class="nt-kpi-label">{{ __('lang_v1.customer_notifications') }}</span>
            <span class="nt-kpi-value">{{ $nt_customer_count }}</span>
            <span class="nt-kpi-hint">Customer templates</span>
        </div>
        <div class="nt-kpi tone-orange">
            <div class="nt-kpi-icon"><i class="bi bi-truck"></i></div>
            <span class="nt-kpi-label">{{ __('lang_v1.supplier_notifications') }}</span>
            <span class="nt-kpi-value">{{ $nt_supplier_count }}</span>
            <span class="nt-kpi-hint">Supplier templates</span>
        </div>
        <div class="nt-kpi tone-slate">
            <div class="nt-kpi-icon"><i class="bi bi-bell"></i></div>
            <span class="nt-kpi-label">{{ __('lang_v1.notifications') }}</span>
            <span class="nt-kpi-value">{{ $nt_general_count }}</span>
            <span class="nt-kpi-hint">General templates</span>
        </div>
        <div class="nt-kpi tone-teal">
            <div class="nt-kpi-icon"><i class="bi bi-chat-dots"></i></div>
            <span class="nt-kpi-label">{{ __('lang_v1.sms_body') }} / {{ __('lang_v1.whatsapp_text') }}</span>
            <span class="nt-kpi-value">{{ $nt_sms_count }}</span>
            <span class="nt-kpi-hint">Templates with SMS and WhatsApp fields</span>
        </div>
        @if($nt_autosend > 0)
        <div class="nt-kpi tone-violet">
            <div class="nt-kpi-icon"><i class="bi bi-send-check"></i></div>
            <span class="nt-kpi-label">Auto-send</span>
            <span class="nt-kpi-value">{{ $nt_autosend }}</span>
            <span class="nt-kpi-hint">Templates with auto-send enabled</span>
        </div>
        @endif
    </div>

    <div class="nt-panel no-print">
        <div class="nt-panel-head">
            <div>
                <h2>Find templates</h2>
                <p>Search and filter templates already loaded on this page.</p>
            </div>
        </div>
        <div class="nt-panel-body">
            <div class="nt-global-search" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="nt_global_search" class="form-control" placeholder="Search name, type, subject or tag…" aria-label="Search all templates" autocomplete="off">
                <button type="button" class="nt-search-clear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="nt-chip-row" id="nt_group_chips" role="toolbar" aria-label="Template group">
                <button type="button" class="nt-chip is-active" data-group="all">All <span class="nt-count">{{ $nt_total }}</span></button>
                @if($nt_general_count > 0)
                    <button type="button" class="nt-chip" data-group="general">{{ __('lang_v1.notifications') }} <span class="nt-count">{{ $nt_general_count }}</span></button>
                @endif
                @if($nt_customer_count > 0)
                    <button type="button" class="nt-chip" data-group="customer">{{ __('lang_v1.customer_notifications') }} <span class="nt-count">{{ $nt_customer_count }}</span></button>
                @endif
                @if($nt_supplier_count > 0)
                    <button type="button" class="nt-chip" data-group="supplier">{{ __('lang_v1.supplier_notifications') }} <span class="nt-count">{{ $nt_supplier_count }}</span></button>
                @endif
            </div>
            <div class="nt-chip-row" id="nt_channel_chips" role="toolbar" aria-label="Channel filter">
                <button type="button" class="nt-chip is-active" data-channel="all">All channels</button>
                <button type="button" class="nt-chip" data-channel="email">Email</button>
                <button type="button" class="nt-chip" data-channel="sms">SMS</button>
                <button type="button" class="nt-chip" data-channel="whatsapp">WhatsApp</button>
            </div>
        </div>
    </div>

    <div id="nt_search_empty" class="nt-panel" hidden>
        <div class="nt-empty">
            <div class="nt-empty-icon" aria-hidden="true">✉</div>
            <strong>No matching templates</strong>
            <p>Try another search term.</p>
            <button type="button" class="nt-btn nt-btn-primary" id="nt_clear_search">Clear Search</button>
        </div>
    </div>

    <div id="nt_empty_all" class="nt-panel" @if($nt_total > 0) hidden @endif>
        <div class="nt-empty">
            <div class="nt-empty-icon" aria-hidden="true">✉</div>
            <strong>No notification templates found</strong>
            <p>No template records were returned by the system.</p>
            <button type="button" class="nt-btn nt-btn-primary" onclick="window.location.reload()">Retry</button>
        </div>
    </div>

    {!! Form::open(['url' => action([\App\Http\Controllers\NotificationTemplateController::class, 'store']), 'method' => 'post', 'id' => 'nt_templates_form' ]) !!}

    <div class="nt-workspace">
        <div class="nt-workspace-main">

            <div class="nt-group" data-nt-section="general" @if($nt_general_count < 1) hidden @endif>
                <div class="nt-group-head">
                    <h2>{{ __('lang_v1.notifications') }}</h2>
                    <p>{{ $nt_general_count }} template{{ $nt_general_count == 1 ? '' : 's' }}</p>
                </div>
                @include('notification_template.partials.tabs', ['templates' => $general_notifications, 'nt_group' => 'general'])
            </div>

            <div class="nt-group" data-nt-section="customer" @if($nt_customer_count < 1) hidden @endif>
                <div class="nt-group-head">
                    <h2>{{ __('lang_v1.customer_notifications') }}</h2>
                    <p>{{ $nt_customer_count }} template{{ $nt_customer_count == 1 ? '' : 's' }}</p>
                </div>
                @include('notification_template.partials.tabs', ['templates' => $customer_notifications, 'nt_group' => 'customer'])
            </div>

            <div class="nt-group" data-nt-section="supplier" @if($nt_supplier_count < 1) hidden @endif>
                <div class="nt-group-head">
                    <h2>{{ __('lang_v1.supplier_notifications') }}</h2>
                    <p>{{ $nt_supplier_count }} template{{ $nt_supplier_count == 1 ? '' : 's' }}</p>
                </div>
                @include('notification_template.partials.tabs', ['templates' => $supplier_notifications, 'nt_group' => 'supplier'])
                <div class="callout callout-warning nt-callout">
                    <p>@lang('lang_v1.logo_not_work_in_sms'):</p>
                </div>
            </div>

        </div>

        <aside class="nt-preview-card no-print" id="nt_preview_card" aria-label="Template preview">
            <div class="nt-preview-head">
                <h2>Preview</h2>
                <p>Uses the current editor content. Placeholders are shown as-is.</p>
            </div>
            <div class="nt-chip-row" id="nt_preview_channels" role="tablist" aria-label="Preview channel">
                <button type="button" class="nt-chip is-active" data-preview="email">Email</button>
                <button type="button" class="nt-chip" data-preview="sms" id="nt_preview_sms_chip">SMS</button>
                <button type="button" class="nt-chip" data-preview="whatsapp" id="nt_preview_wa_chip">WhatsApp</button>
            </div>
            <div class="nt-preview-email" data-nt-preview="email">
                <div class="nt-mail">
                    <div class="nt-mail-row"><span>Subject</span> <strong id="nt_preview_subject">—</strong></div>
                    <div class="nt-mail-body" id="nt_preview_email">—</div>
                </div>
            </div>
            <div class="nt-preview-sms" data-nt-preview="sms" hidden>
                <div class="nt-sms-bubble" id="nt_preview_sms">—</div>
            </div>
            <div class="nt-preview-wa" data-nt-preview="whatsapp" hidden>
                <div class="nt-wa-bubble" id="nt_preview_wa">—</div>
            </div>
        </aside>
    </div>

    <div class="nt-save-bar">
        <p>Save writes every template on this page using the existing form.</p>
        <button type="submit" class="tw-dw-btn tw-dw-btn-error tw-dw-btn-lg tw-text-white" id="nt_save_btn">@lang('messages.save')</button>
    </div>
    {!! Form::close() !!}

    <div class="nt-toast-host" id="nt_toast_host" aria-live="polite"></div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $('textarea.ckeditor').each( function(){
        var editor_id = $(this).attr('id');
        tinymce.init({
            selector: 'textarea#'+editor_id,
        });
    });
</script>
<script src="{{ asset('js/notification-templates-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
