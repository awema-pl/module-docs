<nav class="bd-links">
    @foreach($navs as $nav)
        <div class="bd-toc-item{{($nav['active'] ?? false) ? ' active' : ''}}">
            @if(array_key_exists('link', $nav))
                <a href="{{$nav['link']}}" class="bd-toc-link">
                    {{ $nav['name'] }}
                </a>
            @else
                <span class="bd-toc-link">
                    {{ $nav['name'] }}
                </span>
            @endif
            @if(!empty($nav['children']))
                <ul class="bd-sidenav">
                    @foreach($nav['children'] as $nav)
                        <li class="d-block {{($nav['active'] ?? false) ? 'active' : ''}}">
                            <a href="{{$nav['link']}}">
                                {{ $nav['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</nav>
