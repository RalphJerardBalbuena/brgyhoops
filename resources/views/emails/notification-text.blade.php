{{-- Plain-text part: output is not HTML-escaped because it is never rendered as HTML. --}}
BRGYHOOPS — {!! $heading !!}

{!! $greeting !!}

@foreach ($lines as $line)
{!! $line !!}

@endforeach
@foreach ($details as $label => $value)
{!! $label !!}: {!! $value !!}
@endforeach
@if (!empty($actionText) && !empty($actionUrl))

{!! $actionText !!}: {!! $actionUrl !!}
@endif

@foreach ($outro as $line)
{!! $line !!}
@endforeach

— The BrgyHoops Team
