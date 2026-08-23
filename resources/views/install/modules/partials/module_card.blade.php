@php
    $mm_name = $module['name'] ?? '';
    $mm_desc = $module['description'] ?? '';
    $mm_icon = $mm_icon_map[$mm_name] ?? 'bi-puzzle';
    $mm_installed_flag = !empty($module['is_installed']);
    $mm_version = '';
    if (!empty($module['version']['installed_version'])) {
        $mm_version = $module['version']['installed_version'];
    }
    $mm_update = !empty($module['version']['is_update_available']);
    $mm_requires = [];
    if (!empty($module['requires']) && is_array($module['requires'])) {
        $mm_requires = array_values(array_filter($module['requires']));
    }
    $mm_requires_str = implode(', ', $mm_requires);
    $mm_status_label = $mm_installed_flag ? 'Installed' : 'Not installed';
    $mm_search = strtolower($mm_name . ' ' . $mm_desc . ' ' . $mm_status_label . ' ' . $mm_version . ($mm_update ? ' update' : ''));
@endphp
<article class="mm-card {{ $mm_installed_flag ? '' : 'is-inactive' }}"
    data-mm-kind="{{ $mm_kind }}"
    data-mm-update="{{ $mm_update ? '1' : '0' }}"
    data-mm-name="{{ $mm_name }}"
    data-mm-desc="{{ $mm_desc }}"
    data-mm-version="{{ $mm_version }}"
    data-mm-requires="{{ $mm_requires_str }}"
    data-mm-search="{{ $mm_search }}">
    <div class="mm-card-top">
        <span class="mm-mod-icon" aria-hidden="true"><i class="bi {{ $mm_icon }}"></i></span>
        <span class="mm-badge {{ $mm_installed_flag ? 'is-installed' : 'is-not-installed' }}">{{ $mm_status_label }}</span>
    </div>
    <h3 class="mm-card-name">{{ $mm_name }}</h3>
    <p class="mm-card-desc">{{ $mm_desc }}</p>
    <div class="mm-card-meta">
        @isset($module['version'])
            <span class="mm-ver">@lang('lang_v1.version') {{ $module['version']['installed_version'] }}</span>
        @endisset
        @if($mm_update)
            <span class="mm-badge is-update">Update</span>
        @endif
    </div>
    @if(!empty($module['version']) && $module['version']['is_update_available'])
        <div class="alert alert-warning mt-5">
            <i class="fas fa-sync"></i> @lang('lang_v1.module_new_version', ['module' => $module['name'], 'link' => $module['update_link']])
        </div>
    @endif
    <div class="mm-card-actions">
        @if(!$module['is_installed'])
            <a class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent"
            @if($is_demo)
                href="#"
                title="@lang('lang_v1.disabled_in_demo')"
                disabled
            @else
                href="{{ $module['install_link'] }}"
            @endif
            > @lang('lang_v1.install')</a>
        @else
            <a class="btn btn-warning btn-xs"
                @if($is_demo)
                    href="#"
                    disabled
                    title="@lang('lang_v1.disabled_in_demo')"
                @else
                    href="{{ $module['uninstall_link'] }}"
                @endif
                onclick="return confirm('Do you really want to uninstall the module? Module will be uninstall but the data will not be deleted')"
            >@lang('lang_v1.uninstall')
            </a>

            {{-- Commented Activate/Deactivate
            @if($module['active'] == 1)
                <form
                    action="{{action([\App\Http\Controllers\Install\ModulesController::class, 'update'], ['module_name' => $module['name']])}}"
                    style="display: inline;"
                    method="post">
                    @method('PUT')
                    @csrf
                    <input type="hidden" name="action_type" value="deactivate">
                    <button class="btn btn-warning btn-xs">Deactivate</button>
                </form>
            @else
                <form action="{{action([\App\Http\Controllers\Install\ModulesController::class, 'update'], ['module_name' => $module['name']])}}"
                    style="display: inline;"
                    method="post"
                >
                    @method('PUT')
                    @csrf
                    <input type="hidden" name="action_type" value="activate">
                    <button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent">Activate</button>
                </form>
            @endif
            --}}
        @endif

        <form
            action="{{ action([\App\Http\Controllers\Install\ModulesController::class, 'destroy'], ['module_name' => $module['name']]) }}"
                class="mm-delete-form"
                style="display: inline;"
                method="post"
                onsubmit="return confirm('Do you really want to delete the module? Module code will be deleted but the data will not be deleted')"
            >
                @method('DELETE')
                @csrf
                <button class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-error"
                    @if($is_demo)
                    disabled="disabled"
                    title="@lang('lang_v1.disabled_in_demo')"
                    @endif
                >
                @lang('messages.delete')</button>
            </form>
        <button type="button" class="mm-btn mm-details-btn" aria-label="Details for {{ $mm_name }}">Details</button>
    </div>
</article>
