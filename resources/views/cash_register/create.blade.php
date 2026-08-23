@extends('layouts.app')
@section('title',  __('cash_register.open_cash_register'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/repair-register-premium.css?v=' . $asset_v) }}">
@endsection

@php
    $is_repair = !empty($sub_type) && $sub_type === 'repair';
@endphp

@section('content')
<section class="content rr-shell {{ $is_repair ? 'is-repair-mode' : '' }}">
    <div class="rr-header" role="banner">
        <div class="rr-header-left">
            <h1>
                @if($is_repair)
                    Repair Service Register
                @else
                    @lang('cash_register.open_cash_register')
                @endif
            </h1>
            <p class="rr-subtitle">
                @if($is_repair)
                    Open your cash register to start repair billing &amp; checkout
                @else
                    Enter cash in hand and open the register for POS sales
                @endif
            </p>
            <div class="rr-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span>/</span>
                @if($is_repair)
                    <span>@lang('repair::lang.repair')</span>
                    <span>/</span>
                @endif
                <span>@lang('cash_register.open_cash_register')</span>
            </div>
        </div>
        <div class="rr-header-actions">
            <button type="button" class="rr-btn rr-btn-ghost" id="rr_dark_mode_toggle" title="Dark Mode" aria-label="Toggle dark mode">
                <i class="fas fa-moon"></i>
            </button>
            @if($is_repair)
                <a class="rr-btn" href="{{ action([\Modules\Repair\Http\Controllers\DashboardController::class, 'index']) }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="rr-btn" href="{{ action([\Modules\Repair\Http\Controllers\RepairController::class, 'index']) }}">
                    <i class="fas fa-file-invoice"></i> Orders
                </a>
            @endif
        </div>
    </div>

    @if($is_repair)
        <div class="rr-kpi-grid" aria-label="Service checkout overview">
            <div class="rr-kpi tone-blue">
                <div class="rr-kpi-icon"><i class="fas fa-cash-register"></i></div>
                <span class="rr-kpi-label">Register</span>
                <span class="rr-kpi-value">Closed</span>
                <span class="rr-kpi-hint">Open to enter Service POS</span>
            </div>
            <div class="rr-kpi tone-green">
                <div class="rr-kpi-icon"><i class="fas fa-wrench"></i></div>
                <span class="rr-kpi-label">Mode</span>
                <span class="rr-kpi-value">Repair</span>
                <span class="rr-kpi-hint">Service billing enabled</span>
            </div>
            <div class="rr-kpi tone-teal">
                <div class="rr-kpi-icon"><i class="fas fa-shield-alt"></i></div>
                <span class="rr-kpi-label">Warranty</span>
                <span class="rr-kpi-value">Ready</span>
                <span class="rr-kpi-hint">Available on checkout</span>
            </div>
            <div class="rr-kpi tone-orange">
                <div class="rr-kpi-icon"><i class="fas fa-mobile-alt"></i></div>
                <span class="rr-kpi-label">IMEI / Serial</span>
                <span class="rr-kpi-value">Ready</span>
                <span class="rr-kpi-hint">Tracked on repair invoices</span>
            </div>
        </div>

        <div class="rr-progress-track" aria-label="Checkout workflow">
            <div class="rr-stage active"><span>1. Open Register</span></div>
            <div class="rr-stage"><span>2. Service POS</span></div>
            <div class="rr-stage"><span>3. Repair Details</span></div>
            <div class="rr-stage"><span>4. Charges</span></div>
            <div class="rr-stage"><span>5. Payment</span></div>
            <div class="rr-stage"><span>6. Receipt</span></div>
        </div>

        <div class="rr-future-strip" aria-label="Coming soon">
            <span class="rr-chip"><i class="fas fa-qrcode"></i> QR Pay</span>
            <span class="rr-chip"><i class="fas fa-wifi"></i> NFC Tap</span>
            <span class="rr-chip"><i class="fab fa-apple-pay"></i> Apple Pay</span>
            <span class="rr-chip"><i class="fab fa-google-pay"></i> Google Pay</span>
            <span class="rr-chip muted">UI placeholders — not connected to backend</span>
        </div>
    @endif

    <div class="rr-main-grid">
        <div class="rr-main-col">
            {!! Form::open(['url' => action([\App\Http\Controllers\CashRegisterController::class, 'store']), 'method' => 'post',
            'id' => 'add_cash_register_form' ]) !!}
            <input type="hidden" name="sub_type" value="{{$sub_type}}">

            <div class="rr-card">
                <div class="rr-card-head">
                    <div>
                        <h3>
                            @if($is_repair)
                                <i class="fas fa-cash-register"></i> Open Repair Cash Register
                            @else
                                <i class="fas fa-cash-register"></i> @lang('cash_register.open_cash_register')
                            @endif
                        </h3>
                        <p>Enter opening cash float, then continue to {{ $is_repair ? 'Service POS' : 'POS' }}</p>
                    </div>
                </div>
                <div class="rr-card-body">
                    @if($business_locations->count() > 0)
                        <div class="rr-form-stack">
                            <div class="rr-field">
                                {!! Form::label('amount', __('cash_register.cash_in_hand') . ':*') !!}
                                <div class="rr-amount-wrap">
                                    <span class="rr-currency-badge"><i class="fas fa-coins"></i></span>
                                    {!! Form::text('amount', null, ['class' => 'form-control input_number rr-amount-input',
                                      'placeholder' => __('cash_register.enter_amount'), 'required', 'autocomplete' => 'off', 'aria-label' => __('cash_register.cash_in_hand')]); !!}
                                </div>
                                <div class="rr-quick-amounts" role="group" aria-label="Quick cash amounts">
                                    <button type="button" class="rr-quick-btn" data-amount="500">500</button>
                                    <button type="button" class="rr-quick-btn" data-amount="1000">1,000</button>
                                    <button type="button" class="rr-quick-btn" data-amount="2000">2,000</button>
                                    <button type="button" class="rr-quick-btn" data-amount="5000">5,000</button>
                                    <button type="button" class="rr-quick-btn" data-amount="10000">10,000</button>
                                    <button type="button" class="rr-quick-btn rr-quick-clear" data-amount="">Clear</button>
                                </div>
                            </div>

                            @if(count($business_locations) > 1)
                                <div class="rr-field">
                                    {!! Form::label('location_id', __('business.business_location') . ':') !!}
                                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                                    'placeholder' => __('lang_v1.select_location'), 'style' => 'width:100%']); !!}
                                </div>
                            @else
                                {!! Form::hidden('location_id', array_key_first($business_locations->toArray()) ); !!}
                                <div class="rr-location-pill">
                                    <i class="fas fa-store"></i>
                                    <span>{{ $business_locations->first() }}</span>
                                </div>
                            @endif

                            <div class="rr-submit-row">
                                <button type="submit" class="rr-btn rr-btn-primary rr-btn-lg">
                                    <i class="fas fa-unlock"></i> @lang('cash_register.open_register')
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="rr-empty">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>@lang('lang_v1.no_location_access_found')</h3>
                        </div>
                    @endif
                </div>
            </div>
            {!! Form::close() !!}

            @if($is_repair)
                <div class="rr-card">
                    <div class="rr-card-head">
                        <div>
                            <h3><i class="fas fa-credit-card"></i> Payment Methods (at checkout)</h3>
                            <p>Available after register opens — existing POS payment logic</p>
                        </div>
                    </div>
                    <div class="rr-card-body">
                        <div class="rr-pay-grid">
                            <div class="rr-pay-card"><i class="fas fa-money-bill-wave"></i><span>Cash</span></div>
                            <div class="rr-pay-card"><i class="fas fa-credit-card"></i><span>Card</span></div>
                            <div class="rr-pay-card"><i class="fas fa-university"></i><span>Bank Transfer</span></div>
                            <div class="rr-pay-card"><i class="fas fa-mobile-alt"></i><span>Mobile Wallet</span></div>
                            <div class="rr-pay-card"><i class="fas fa-money-check"></i><span>Cheque</span></div>
                            <div class="rr-pay-card"><i class="fas fa-gift"></i><span>Gift / Custom</span></div>
                            <div class="rr-pay-card"><i class="fas fa-random"></i><span>Split Payment</span></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <aside class="rr-side-col">
            @if($is_repair)
                <div class="rr-card">
                    <div class="rr-card-head">
                        <div>
                            <h3>Service POS Checklist</h3>
                            <p>What happens after you open</p>
                        </div>
                    </div>
                    <div class="rr-card-body">
                        <ul class="rr-checklist">
                            <li><i class="fas fa-check-circle"></i> Customer &amp; device details</li>
                            <li><i class="fas fa-check-circle"></i> IMEI / serial &amp; warranty</li>
                            <li><i class="fas fa-check-circle"></i> Repair status &amp; due date</li>
                            <li><i class="fas fa-check-circle"></i> Labour &amp; parts charges</li>
                            <li><i class="fas fa-check-circle"></i> Touch-friendly payments</li>
                            <li><i class="fas fa-check-circle"></i> Print / email receipt</li>
                        </ul>
                    </div>
                </div>

                <div class="rr-card">
                    <div class="rr-card-head">
                        <div>
                            <h3>Quick Links</h3>
                            <p>Existing repair routes</p>
                        </div>
                    </div>
                    <div class="rr-card-body">
                        <div class="rr-quick-links">
                            <a href="{{ action([\Modules\Repair\Http\Controllers\JobSheetController::class, 'index']) }}"><i class="fas fa-clipboard-list"></i> Job Sheets</a>
                            <a href="{{ action([\Modules\Repair\Http\Controllers\RepairController::class, 'index']) }}"><i class="fas fa-file-invoice"></i> Repair Invoices</a>
                            <a href="{{ url('/contacts') }}"><i class="fas fa-users"></i> Customers</a>
                            <a href="{{ url('/products') }}"><i class="fas fa-box"></i> Parts / Products</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="rr-card">
                    <div class="rr-card-head">
                        <div>
                            <h3>Tips</h3>
                            <p>Register opening</p>
                        </div>
                    </div>
                    <div class="rr-card-body">
                        <ul class="rr-checklist">
                            <li><i class="fas fa-info-circle"></i> Count physical cash before opening</li>
                            <li><i class="fas fa-info-circle"></i> Select the correct business location</li>
                            <li><i class="fas fa-info-circle"></i> You will be redirected to POS after open</li>
                        </ul>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/repair-register-premium-ui.js?v=' . $asset_v) }}"></script>
@endsection
