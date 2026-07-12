@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{!! trim($slot) !!}<span class="wordmark-dot">.</span>
</a>
</td>
</tr>
