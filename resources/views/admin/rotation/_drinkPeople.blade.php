{{-- 음료 선택 현황의 남/여 구분 목록. 남·여 2열, 번호표 순. 모바일에서도 2열 유지.
     $people: 번호표 순으로 정렬된 [{gender, no, tableText}] 컬렉션 --}}
@php
    $groups = [
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
                <div class="d-flex align-items-center justify-content-between gap-1 rounded px-2 py-1 mb-1 bg-{{ $group['color'] }}-lt">
                    <span class="fw-bold text-nowrap">{{ $w['no'] }}호</span>
                    <span class="text-dark fw-semibold text-nowrap" style="font-size:0.9em;">{{ $w['tableText'] }}</span>
                </div>
            @empty
                <div class="text-muted" style="font-size:0.85em;">-</div>
            @endforelse
        </div>
    @endforeach
</div>
