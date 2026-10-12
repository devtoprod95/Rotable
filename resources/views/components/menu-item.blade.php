@props(['item', 'activeMenuNames' => []])

@if(isset($item['children']))
    {{-- 3뎁스 이상인 경우: Dropend (또는 Dropdown) --}}
    @php
        $isActive = collect($item['children'])->contains(function($child) use ($activeMenuNames) {
            return in_array($child['name'], $activeMenuNames, true);
        });
    @endphp
    <div class="dropend">
        <a href="#" class="dropdown-item dropdown-toggle {{ $isActive ? 'active' : '' }}"
           data-bs-toggle="dropdown"
           onclick="event.stopPropagation();"
        >
            {{ $item['name'] }}
        </a>
        <div class="dropdown-menu">
            @foreach($item['children'] as $child)
                <x-menu-item :item="$child" :active-menu-names="$activeMenuNames" />
            @endforeach
        </div>
    </div>
@else
    {{-- 2뎁스 이하인 경우: 일반 링크 --}}
    <a href="{{ route($item['route']) }}" class="dropdown-item {{ in_array($item['name'], $activeMenuNames, true) ? 'active' : '' }}">
        {{ $item['name'] }}
    </a>
@endif
