@php
    $psn_list = isset($serial_numbers) ? $serial_numbers : collect();
    if ($psn_list instanceof \Illuminate\Support\Collection) {
        $psn_json = $psn_list->map(function ($row) {
            return [
                'value' => $row->serial_number,
                'status' => $row->status ?? 'available',
                'locked' => ($row->status ?? 'available') !== 'available',
            ];
        })->values();
    } else {
        $psn_json = collect();
    }
@endphp

<div class="psn-mgmt-card" id="psn_management_card" style="display: none;" aria-hidden="true">
    <div class="psn-mgmt-head">
        <div class="psn-mgmt-title-wrap">
            <div class="psn-mgmt-icon" aria-hidden="true">
                <i class="fas fa-barcode"></i>
            </div>
            <div>
                <h4 class="psn-mgmt-title">IMEI / Serial Number Management</h4>
                <p class="psn-mgmt-subtitle">Add one or many unique identifiers for this product. Each serial is stored separately for inventory and sales tracking.</p>
            </div>
        </div>
        <div class="psn-status-pill" id="psn_status_pill">
            <span class="dot"></span>
            Serial Tracking ON
        </div>
    </div>

    <div class="psn-toolbar">
        <div class="psn-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control" id="psn_search" placeholder="Search serial / IMEI..." autocomplete="off">
        </div>
        <div class="psn-actions">
            <button type="button" class="btn btn-success btn-sm psn-btn" id="psn_add_row">
                <i class="fas fa-plus"></i> Add Another
            </button>
            <button type="button" class="btn btn-info btn-sm psn-btn" id="psn_paste_btn" data-toggle="modal" data-target="#psn_paste_modal">
                <i class="fas fa-paste"></i> Paste Multiple
            </button>
            <button type="button" class="btn btn-primary btn-sm psn-btn" id="psn_import_btn">
                <i class="fas fa-file-excel"></i> Import from Excel
            </button>
            <input type="file" id="psn_import_file" accept=".csv,.xlsx,.xls,.txt" style="display:none;">
        </div>
    </div>

    <div class="psn-alert" id="psn_alert" style="display:none;" role="alert"></div>

    <div class="psn-list-header">
        <span>IMEI / Serial Numbers</span>
        <span class="psn-progress" id="psn_progress_label">Ready</span>
    </div>

    <div class="psn-list" id="psn_list" role="list">
        {{-- Rows rendered by JS --}}
    </div>

    <div class="psn-footer">
        <div class="psn-counters">
            <span><strong id="psn_total_count">0</strong> Total</span>
            <span class="sep">·</span>
            <span class="is-ok"><strong id="psn_available_count">0</strong> Available</span>
            <span class="sep">·</span>
            <span class="is-warn"><strong id="psn_locked_count">0</strong> Locked</span>
            <span class="sep">·</span>
            <span class="is-danger"><strong id="psn_invalid_count">0</strong> Invalid</span>
        </div>
        <button type="button" class="btn btn-default btn-xs" id="psn_clear_available" title="Remove all available (unlocked) serials">
            Clear available
        </button>
    </div>

    {{-- Bulk JSON payload (avoids PHP max_input_vars limits for 1000+ rows) --}}
    <input type="hidden" name="product_serial_numbers_json" id="product_serial_numbers_json" value="">
</div>

{{-- Paste modal (Bootstrap 3) --}}
<div class="modal fade" id="psn_paste_modal" tabindex="-1" role="dialog" aria-labelledby="psn_paste_modal_title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="psn_paste_modal_title">
                    <i class="fas fa-paste"></i> Paste Multiple Serial Numbers
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="margin-bottom:10px;">Paste one IMEI / serial number per line. Blank lines are ignored.</p>
                <textarea id="psn_paste_textarea" class="form-control" rows="12" placeholder="356897451236589&#10;356897451236590&#10;SN-2026-000001"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="psn_paste_apply">
                    <i class="fas fa-check"></i> Import Pasted
                </button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="psn_initial_data">@json($psn_json)</script>
