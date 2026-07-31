@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" class="brand" aria-label="LidUp home">
<img src="{{ rtrim(config('app.url'), '/') }}/app-icon.png" class="logo" alt="LidUp">
<span class="brand-name">LidUp</span>
</a>
<div class="awake-signal"><span></span> Your Mac, still working</div>
</td>
</tr>
