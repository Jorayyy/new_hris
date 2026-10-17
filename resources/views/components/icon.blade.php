@props(['name', 'size' => 19])

@php($path = \App\Support\Icons::path($name))

@if($path)
    <svg class="ico" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $path !!}</svg>
@endif
