@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="{{ config('app.url') . '/favicon.png' }}" class="logo" alt="Klele.si logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
