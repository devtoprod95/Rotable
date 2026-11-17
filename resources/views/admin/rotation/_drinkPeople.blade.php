{{-- 음료 선택 현황의 남/여 구분 목록. 남·여 2열, 번호표 순. 모바일에서도 2열 유지.
     $people   : 번호표 순으로 정렬된 [{id, gender, no, tableText, served, servedAt}] 컬렉션
     $servable : true 면 각 사람을 눌러 "전달 완료"를 체크/해제할 수 있다. (미선택 목록은 false) --}}
@php
    $servable = $servable ?? false;
    $groups   = [
        ['label' => '남', 'color' => 'blue', 'list' => $people->where('gender', 'M')->values()],
        ['label' => '여', 'color' => 'pink', 'list' => $people->where('gender', 'F')->values()],
    ];
@endphp
<div class="row g-2">
    @foreach ($groups as $group)
        <div class="col-6">
            <div class="d-flex align-items-center gap-1 mb-1">
                <span class="badge bg-{{ $group['color'] }}-lt">{{ $group['label'] }}</span>
                <span class="text-muted" style="font-size:0.8em;">{{ $group['list']->count() }}명</span>
            </div>
            @forelse ($group['list'] as $w)
                @if ($servable)
                    {{-- 눌러서 전달 완료 체크/해제. 완료면 초록 + 체크, 시각 표시 --}}
                    <button type="button"
                            class="drink-serve-btn w-100 d-flex align-items-center justify-content-between gap-1 rounded px-2 py-2 mb-1 border-0 text-start {{ $w['served'] ? 'is-served' : 'bg-' . $group['color'] . '-lt' }}"
                            data-member-id="{{ $w['id'] }}" data-served="{{ $w['served'] ? 1 : 0 }}"
                            aria-pressed="{{ $w['served'] ? 'true' : 'false' }}"
                            title="{{ $w['served'] ? '전달 완료 (누르면 해제)' : '눌러서 전달 완료로 체크' }}">
                        <span class="d-flex align-items-center gap-1 text-nowrap">
                            <span class="drink-serve-check">{!! $w['served'] ? '<i class="ti ti-circle-check-filled"></i>' : '<i class="ti ti-circle"></i>' !!}</span>
                            <span class="fw-bold">{{ $w['no'] }}호</span>
                        </span>
                        <span class="text-end lh-sm">
                            <span class="d-block text-dark fw-semibold text-nowrap" style="font-size:0.9em;">{{ $w['tableText'] }}</span>
                            @if ($w['served'])
                                <span class="d-block text-nowrap" style="font-size:0.75em;">전달 {{ $w['servedAt'] }}</span>
                            @endif
                        </span>
                    </button>
                @else
                    <div class="d-flex align-items-center justify-content-between gap-1 rounded px-2 py-1 mb-1 bg-{{ $group['color'] }}-lt">
                        <span class="fw-bold text-nowrap">{{ $w['no'] }}호</span>
                        <span class="text-dark fw-semibold text-nowrap" style="font-size:0.9em;">{{ $w['tableText'] }}</span>
                    </div>
                @endif
            @empty
                <div class="text-muted" style="font-size:0.85em;">-</div>
            @endforelse
        </div>
    @endforeach
</div>
