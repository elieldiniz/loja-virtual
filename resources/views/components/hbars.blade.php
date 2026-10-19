@props(['rows'])
@php $max = max(1, collect($rows)->max('n') ?? 0); @endphp
@foreach ($rows as $i => $row)
    <div class="hbar" data-tip="{{ $row['tip'] ?? '' }}">
        <div class="nm">
            @if (! empty($row['thumb_url']))<span class="mini"><img src="{{ $row['thumb_url'] }}" alt="" style="width:100%;height:100%;object-fit:cover"></span>@endif
            @if (! empty($row['avatar']))<span class="avatar sm">{{ $row['avatar'] }}</span>@endif
            <span>{{ $row['name'] }}</span>
        </div>
        <div class="val">{{ $row['value'] }}</div>
        <div class="track"><div class="fill" style="width:{{ $row['n'] / $max * 100 }}%;animation-delay:{{ $i * 60 }}ms"></div></div>
    </div>
@endforeach
