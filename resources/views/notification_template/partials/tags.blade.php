@foreach($tags as $tag)
@php
    $nt_items = is_array($tag) ? $tag : [$tag];
@endphp
<p class="help-block nt-tag-row">
	@foreach($nt_items as $t)
		<span class="nt-tag" data-nt-tag="{{ $t }}">{{ $t }}</span>@if(!$loop->last)<span class="nt-tag-sep">, </span>@endif
	@endforeach
</p>
@endforeach
