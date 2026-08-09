@props(['items' => []])
<div class="dth-insight-grid">
    @foreach($items as $row)
        <article class="dth-insight-card" data-tone="{{ $row['tone'] ?? 'primary' }}">
            <div class="dth-insight-card__icon"><x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5" /></div>
            <div><strong>{{ $row['title'] ?? '' }}</strong><p>{{ $row['text'] ?? '' }}</p></div>
        </article>
    @endforeach
</div>
