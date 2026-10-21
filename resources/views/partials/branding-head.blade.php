@php($brandIcon = \App\Support\Branding::url())
@if($brandIcon)
    <link rel="icon" href="{{ $brandIcon }}">
@endif
