@php
    $pt_checked = !empty($enable_sr_no_checked);
@endphp
<div class="pt-tracking-card {{ $pt_checked ? 'is-enabled' : '' }}" id="pt_tracking_card">
    <div class="pt-tracking-head">
        <div class="pt-tracking-title-wrap">
            <div class="pt-tracking-icon" aria-hidden="true">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <div>
                <h4 class="pt-tracking-title">
                    Product Tracking
                    <i class="fa fa-info-circle text-info hover-q text-muted"
                       aria-hidden="true"
                       data-container="body"
                       data-toggle="popover"
                       data-placement="bottom"
                       data-content="@lang('lang_v1.tooltip_sr_no')"
                       data-html="true"
                       data-trigger="hover"></i>
                </h4>
                <p class="pt-tracking-subtitle">
                    @lang('lang_v1.enable_imei_or_sr_no')
                </p>
            </div>
        </div>

        <div class="pt-switch-wrap">
            <span class="pt-switch-label-text" id="pt_switch_state_label">{{ $pt_checked ? 'Enabled' : 'Disabled' }}</span>
            <div class="pt-switch" role="switch" tabindex="0" aria-checked="{{ $pt_checked ? 'true' : 'false' }}" aria-label="@lang('lang_v1.enable_imei_or_sr_no')" id="pt_switch_control">
                {!! Form::checkbox(
                    'enable_sr_no',
                    1,
                    $pt_checked,
                    [
                        'class' => 'input-icheck',
                        'id' => 'enable_sr_no',
                        'aria-label' => __('lang_v1.enable_imei_or_sr_no'),
                    ]
                ) !!}
                <span class="pt-switch-slider" aria-hidden="true"></span>
            </div>
        </div>
    </div>

    <div class="pt-info-box">
        <h5><i class="fas fa-fingerprint"></i> Serial Number Tracking</h5>
        <p>
            Track each product individually using its IMEI or Serial Number.
            When enabled, sales can capture unique identifiers for complete traceability.
        </p>
        <ul class="pt-recommend-list">
            <li><i class="fas fa-mobile-alt"></i> Mobile Phones</li>
            <li><i class="fas fa-laptop"></i> Laptops</li>
            <li><i class="fas fa-tablet-alt"></i> Tablets</li>
            <li><i class="fas fa-tv"></i> TVs</li>
            <li><i class="fas fa-video"></i> CCTV Cameras</li>
            <li><i class="fas fa-heartbeat"></i> Medical Equipment</li>
            <li><i class="fas fa-car"></i> Vehicle Parts</li>
            <li><i class="fas fa-shield-alt"></i> Warranty Products</li>
            <li><i class="fas fa-microchip"></i> Premium Electronics</li>
        </ul>
    </div>

    <div class="pt-badges" id="pt_feature_badges">
        <span class="pt-badge"><i class="fas fa-check"></i> Warranty Tracking</span>
        <span class="pt-badge"><i class="fas fa-check"></i> Product Authentication</span>
        <span class="pt-badge"><i class="fas fa-check"></i> Theft Prevention</span>
        <span class="pt-badge"><i class="fas fa-check"></i> Asset Management</span>
        <span class="pt-badge"><i class="fas fa-check"></i> Service History</span>
        <span class="pt-badge"><i class="fas fa-check"></i> Inventory Accuracy</span>
    </div>

    <div class="pt-preview" id="pt_serial_preview">
        <h5><i class="fas fa-eye"></i> Serial Number Example</h5>
        <div class="pt-preview-grid">
            <div class="pt-preview-item">
                <span class="k">IMEI</span>
                <span class="v">356784521547896</span>
            </div>
            <div class="pt-preview-item">
                <span class="k">Serial Number</span>
                <span class="v">SN-2026-000254</span>
            </div>
            <div class="pt-preview-item">
                <span class="k">Warranty</span>
                <span class="v">24 Months</span>
            </div>
            <div class="pt-preview-item">
                <span class="k">Status</span>
                <span class="v is-ok">Available</span>
            </div>
        </div>
    </div>
</div>
