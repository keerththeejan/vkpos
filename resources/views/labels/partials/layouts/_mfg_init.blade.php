@include('labels.partials.layouts._helpers')
@php
    $labelFields = \App\Utils\LabelFieldEngine::visibleFields($page_product, $print, $business_name);
    $labelFieldsByKey = \App\Utils\LabelFieldEngine::fieldsByKey($labelFields);
@endphp
