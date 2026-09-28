@if($items->isEmpty())
    <p>Новостей пока нет.</p>
@else
    <ul>
        @foreach($items as $item)
            <li>
                <a href="{{ url('/news/'.$item->slug.'.html') }}">{{ $item->title }}</a>
                @if($item->published_at)
                    <span> — {{ $item->published_at->format('d.m.Y') }}</span>
                @endif
            </li>
        @endforeach
    </ul>
    {{ $items->links() }}
@endif
