{{-- 음료 선택 (1:1 소개팅 전용) — 선택지 설정 + 참가자 선택/전달 현황. 체크인 화면에서 사용한다.
     필요한 값: $event (drinks[withCount members] · members 로드), $memberTableNos (회원 id => 기준 테이블 번호) --}}
        @if ($event->isOneToOne())
            @php
                $drinkNoneMembers = $event->members->whereNull('event_drink_id');
                $drinkMembersById = $event->members->whereNotNull('event_drink_id')->groupBy('event_drink_id');
                // 음료 전달 현황: 음료를 고른 사람 중 전달 완료/대기 인원
                $drinkChosenCount = $event->members->whereNotNull('event_drink_id')->count();
                $drinkServedCount = $event->members->whereNotNull('event_drink_id')->whereNotNull('drink_served_at')->count();
                // 음료 현황 표기: 성별(남/여 칸) · 번호표 · 기준 테이블만 (닉네임/플랫폼 없음)
                $drinkWho = function ($m) use ($memberTableNos) {
                    $tableNo = $memberTableNos[$m->id] ?? null;

                    return [
                        'id'        => $m->id,
                        'gender'    => $m->gender,
                        'no'        => $m->name,
                        'tableText' => $tableNo !== null ? $tableNo . '번 테이블' : '미배정',
                        // 음료 전달 완료 (관리자 체크)
                        'served'    => $m->drink_served_at !== null,
                        'servedAt'  => $m->drink_served_at?->format('H:i'),
                    ];
                };
                // 번호표 순 (성별 칸은 파샬에서 나눈다)
                $drinkSorted = fn ($members) => $members
                    ->sortBy(fn ($m) => (int) $m->name)
                    ->map($drinkWho)
                    ->values();
            @endphp
            <div class="col-12">
                <div class="card">
                    {{-- 카드 전체 접기/펼치기 (기본 접힘, 상태 기억). 접어도 헤더의 선택·전달 현황 배지는 보인다. --}}
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 drink-card-head"
                         role="button" tabindex="0" data-bs-toggle="collapse" data-bs-target="#drinkCardCollapse"
                         aria-expanded="false" aria-controls="drinkCardCollapse">
                        <h3 class="card-title mb-0 d-flex align-items-center gap-1"><i class="ti ti-glass-full text-red me-1"></i> 음료 선택 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
                        <div id="drinkSummaryBadges" class="d-flex gap-2 flex-wrap justify-content-end">
                            <span class="badge bg-green-lt">선택 {{ $event->members->count() - $drinkNoneMembers->count() }}명</span>
                            <span class="badge bg-secondary-lt">미선택 {{ $drinkNoneMembers->count() }}명</span>
                            <span class="badge bg-teal text-white"><i class="ti ti-circle-check me-1"></i>전달 {{ $drinkServedCount }}/{{ $drinkChosenCount }}</span>
                            @if ($drinkChosenCount - $drinkServedCount > 0)
                                <span class="badge bg-orange-lt">전달 대기 {{ $drinkChosenCount - $drinkServedCount }}명</span>
                            @endif
                        </div>
                    </div>
                    <div class="collapse" id="drinkCardCollapse">
                    <div class="card-body">
                        <div class="row g-4">
                            {{-- 음료 목록 설정 — 접기/펼치기(기본 접힘, 상태 기억) --}}
                            <div class="col-12">
                                <div class="drink-setting border rounded">
                                    <div class="drink-setting-head d-flex align-items-center justify-content-between gap-2 px-3 py-2"
                                         role="button" tabindex="0" data-bs-toggle="collapse" data-bs-target="#drinkSettingCollapse"
                                         aria-expanded="false" aria-controls="drinkSettingCollapse">
                                        <span class="fw-bold d-flex align-items-center gap-1"><i class="ti ti-list-details me-1"></i>음료 목록 설정
                                            <span class="badge bg-red-lt ms-1" id="drinkCountBadge">{{ $event->drinks->count() }}개</span>
                                            <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i>
                                        </span>
                                    </div>
                                    <div class="collapse" id="drinkSettingCollapse">
                                        <div class="px-3 pb-3 pt-1">
                                            <div class="input-group">
                                                <input type="text" id="drinkNameInput" class="form-control" maxlength="50"
                                                       placeholder="음료 이름 입력 후 추가" enterkeyhint="done" autocomplete="off">
                                                <button type="button" class="btn btn-primary" id="drinkAddBtn"><i class="ti ti-plus me-1"></i>추가</button>
                                            </div>
                                            <div class="text-muted mt-1" style="font-size:0.82em;">최대 {{ \App\Models\EventDrink::MAX_PER_EVENT }}개 · 가나다 순으로 보여요</div>
                                            <div id="drinkChips" class="drink-chip-grid mt-2"></div>
                                            <div class="drink-setting-notes mt-2">
                                                <div><i class="ti ti-point me-1"></i>음료 카드의 ✕ 를 누르면 목록에서 빠져요.</div>
                                                <div><i class="ti ti-point me-1"></i>지운 음료를 고른 참가자의 선택과 전달 체크는 해제돼요.</div>
                                                <div><i class="ti ti-point me-1"></i>참가자는 입장등록 때 이 목록에서 하나를 골라요. (등록 후 변경 불가)</div>
                                                <div><i class="ti ti-point me-1"></i>바꾼 내용은 <b>음료 목록 저장</b>을 눌러야 반영돼요.</div>
                                            </div>
                                            {{-- 하단 버튼: 모바일은 위아래, PC 는 같은 폭으로 나란히 --}}
                                            <div class="row g-2 mt-2">
                                                <div class="col-12 col-md-6">
                                                    <button type="button" class="btn btn-outline-primary w-100" id="drinkDefaultBtn"
                                                            title="{{ implode(', ', \App\Models\EventDrink::DEFAULT_NAMES) }}">
                                                        <i class="ti ti-sparkles me-1"></i>기본 음료 추가 ({{ count(\App\Models\EventDrink::DEFAULT_NAMES) }}종)
                                                    </button>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <button type="button" class="btn btn-primary w-100" id="drinkSaveBtn"><i class="ti ti-device-floppy me-1"></i>음료 목록 저장</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- 선택 현황 --}}
                            <div class="col-12" id="drinkStatus">
                                <label class="form-label mb-1">선택 현황</label>
                                <div class="text-muted mb-2" style="font-size:0.85em;"><i class="ti ti-hand-finger me-1"></i>음료를 가져다준 사람을 누르면 전달 완료로 체크돼요. 다시 누르면 해제돼요.</div>
                                <div class="row g-2">
                                @forelse ($event->drinks as $drink)
                                    <div class="col-12 col-xl-6">
                                    <div class="border rounded p-2 h-100">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold">{{ $drink->name }}</span>
                                            @php
                                                $drinkPeople   = $drinkMembersById->get($drink->id, collect());
                                                $drinkServedOf = $drinkPeople->whereNotNull('drink_served_at')->count();
                                            @endphp
                                            <span class="d-flex gap-1">
                                                <span class="badge bg-red-lt">{{ $drink->members_count }}명</span>
                                                @if ($drink->members_count > 0)
                                                    <span class="badge {{ $drinkServedOf === $drinkPeople->count() ? 'bg-teal text-white' : 'bg-orange-lt' }}">전달 {{ $drinkServedOf }}/{{ $drinkPeople->count() }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if ($drinkMembersById->has($drink->id))
                                            <div class="mt-2">
                                                @include('admin.rotation._drinkPeople', ['people' => $drinkSorted($drinkMembersById[$drink->id]), 'servable' => true])
                                            </div>
                                        @endif
                                    </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted py-3" style="font-size:0.9em;">등록된 음료가 없어요. 위의 <b>음료 목록 설정</b>에서 음료를 추가해 주세요.</div>
                                @endforelse
                                </div>
                                @if ($drinkNoneMembers->isNotEmpty() && $event->drinks->isNotEmpty())
                                    <div class="border rounded p-2 bg-light mt-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-muted">미선택</span>
                                            <span class="badge bg-secondary-lt">{{ $drinkNoneMembers->count() }}명</span>
                                        </div>
                                        <div class="mt-2">
                                            @include('admin.rotation._drinkPeople', ['people' => $drinkSorted($drinkNoneMembers)])
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            <style>
    /* 음료 목록 설정 (접기/펼치기) */
    .drink-setting-head{ cursor:pointer; user-select:none; min-height:48px; }
    .drink-setting-notes > div{ display:block; font-size:.85rem; line-height:1.65; color:#495057; word-break:keep-all; }
    /* 음료 카드 그리드: 모바일 2열 / 태블릿 3열 / 데스크톱 4열 */
    .drink-chip-grid{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem; }
    @media (min-width: 576px){ .drink-chip-grid{ grid-template-columns:repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 992px){ .drink-chip-grid{ grid-template-columns:repeat(4, minmax(0, 1fr)); } }
    .drink-card{ display:flex; align-items:center; gap:.5rem; padding:.55rem .6rem; border:1px solid #f1d4da; border-radius:12px; background:#fff7f8; min-height:48px; }
    .drink-card-emoji{ font-size:1.3rem; line-height:1; flex:0 0 auto; }
    .drink-card-name{ flex:1 1 auto; min-width:0; font-weight:700; font-size:.92rem; overflow-wrap:anywhere; line-height:1.3; }
    .drink-card-del{ flex:0 0 auto; width:32px; height:32px; border:0; border-radius:50%; background:transparent; color:#d63939; display:inline-flex; align-items:center; justify-content:center; }
    .drink-card-del:hover, .drink-card-del:focus{ background:#fde2e2; }
    .drink-chip-empty{ grid-column:1 / -1; color:#868e96; font-size:.9rem; padding:.5rem 0; }

    /* 음료 전달 완료 체크 버튼 */
    .drink-serve-btn{ cursor:pointer; transition:background .12s ease, color .12s ease; min-height:44px; }
    .drink-serve-btn .drink-serve-check{ font-size:1.15rem; line-height:1; color:#94a3b8; }
    .drink-serve-btn.is-served{ background:#0ca678 !important; color:#fff; }
    .drink-serve-btn.is-served .text-dark{ color:#fff !important; }
    .drink-serve-btn.is-served .drink-serve-check{ color:#fff; }
    .drink-serve-btn:disabled{ opacity:.6; }
    /* 음료 카드 전체 접기/펼치기 */
    .drink-card-head{ cursor:pointer; user-select:none; min-height:52px; }
            </style>
            <script>
$(function(){
        // ===== 상세: 음료 목록 편집/저장 (1:1 소개팅) — 입력 후 추가, 칩을 눌러 삭제 =====
        var drinkList = @json($event->drinks->pluck('name')->values());
        var drinkMax  = {{ \App\Models\EventDrink::MAX_PER_EVENT }};

        // 음료 이름에서 어울리는 이모지 (입장등록 화면과 같은 규칙)
        function drinkEmoji(name){
            if(/아메리카노|커피|라떼/.test(name)){ return /ICE/i.test(name) ? '🧊' : '☕'; }
            if(name.indexOf('물') !== -1){ return /HOT/i.test(name) ? '♨️' : '💧'; }
            if(/카모마일|캐모마일|케모마일|차/.test(name)){ return '🌼'; }
            return '🥤';
        }

        // 음료 목록은 가나다 순으로 보여준다. (서버도 이름순으로 내려준다)
        function sortDrinkList(){
            drinkList.sort(function(a, b){ return a.localeCompare(b, 'ko'); });
        }

        function renderDrinkChips(){
            sortDrinkList();
            var $box = $('#drinkChips').empty();
            $('#drinkCountBadge').text(drinkList.length + '개');
            if(!drinkList.length){
                $box.append('<div class="drink-chip-empty">추가된 음료가 없어요.</div>');
                return;
            }
            drinkList.forEach(function(name, i){
                var $card = $('<div class="drink-card"></div>');
                $card.append($('<span class="drink-card-emoji" aria-hidden="true"></span>').text(drinkEmoji(name)));
                $card.append($('<span class="drink-card-name"></span>').text(name));
                $card.append(
                    $('<button type="button" class="drink-card-del drink-chip"><i class="ti ti-x"></i></button>')
                        .attr('data-index', i)
                        .attr('aria-label', name + ' 삭제')
                );
                $box.append($card);
            });
        }

        // 음료 목록 설정 접힘 상태 기억 (기본: 접힘)
        (function(){
            var KEY = 'rotable.eventDetail.drinkSettingOpen';
            var el = document.getElementById('drinkSettingCollapse');
            if(!el){ return; }
            var open = false;
            try { open = localStorage.getItem(KEY) === '1'; } catch(e) {}
            if(open){
                el.classList.add('show');
                $('[data-bs-target="#drinkSettingCollapse"]').attr('aria-expanded', 'true');
            }
            function save(v){ try { localStorage.setItem(KEY, v ? '1' : '0'); } catch(e) {} }
            el.addEventListener('shown.bs.collapse', function(e){ if(e.target === el){ save(true); } });
            el.addEventListener('hidden.bs.collapse', function(e){ if(e.target === el){ save(false); } });
        })();

        async function addDrink(){
            var $input = $('#drinkNameInput');
            var name = $input.val().trim();
            if(!name){ $input.focus(); return; }
            if(drinkList.indexOf(name) !== -1){
                await salert({text: '이미 추가된 음료입니다.', cancel: false});
                return;
            }
            if(drinkList.length >= drinkMax){
                await salert({text: '음료는 최대 ' + drinkMax + '개까지 등록할 수 있습니다.', cancel: false});
                return;
            }
            drinkList.push(name);
            $input.val('').focus();
            renderDrinkChips();
        }

        $('#drinkAddBtn').on('click', addDrink);
        $('#drinkNameInput').on('keydown', function(e){
            if(e.key === 'Enter' && !e.originalEvent?.isComposing){ e.preventDefault(); addDrink(); }
        });
        $(document).on('click', '.drink-chip', function(){
            drinkList.splice($(this).data('index'), 1);
            renderDrinkChips();
        });
        renderDrinkChips();

        // ===== 음료 선택 현황 실시간 갱신 =====
        // 참가자가 입장등록하며 음료를 고르면(event-drinks.{id}) 이 페이지를 다시 받아 현황 영역만 교체한다.
        // (목록 편집 중인 입력값은 건드리지 않는다.)
        (function(){
            var cfg = {!! json_encode([
                'key'    => config('broadcasting.connections.reverb.key'),
                'host'   => env('VITE_REVERB_HOST', env('REVERB_HOST')),
                'port'   => (int) env('VITE_REVERB_PORT', env('REVERB_PORT', 443)),
                'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
            ], JSON_HEX_TAG | JSON_HEX_AMP) !!};
            // 전달 완료 체크 후 즉시 갱신에도 쓰므로 실시간 연결 여부와 관계없이 먼저 노출한다. (함수 선언은 호이스팅됨)
            if($('#drinkStatus').length){ window.refreshDrinkStatus = refreshDrinkStatus; }
            if(typeof Pusher === 'undefined' || !cfg.key || !cfg.host || !$('#drinkStatus').length){ return; }

            var timer = null;
            function refreshDrinkStatus(){
                $.get(window.location.href, function(html){
                    var $doc = $('<div>').append($.parseHTML(html));
                    ['#drinkStatus', '#drinkSummaryBadges'].forEach(function(sel){
                        var $new = $doc.find(sel);
                        if($new.length){ $(sel).html($new.html()); }
                    });
                });
            }

            var pusher = new Pusher(cfg.key, {
                wsHost: cfg.host,
                wsPort: cfg.port || 80,
                wssPort: cfg.port || 443,
                forceTLS: cfg.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                cluster: '',
            });
            var drinkChannel = pusher.subscribe('event-drinks.{{ $event->id }}');
            var scheduleRefresh = function(){
                clearTimeout(timer);
                timer = setTimeout(refreshDrinkStatus, 300);
            };
            // 참가자 음료 선택 + 관리자 전달 완료 체크(다른 관리자 화면 포함)
            drinkChannel.bind('drink.chosen', scheduleRefresh);
            drinkChannel.bind('drink.served', scheduleRefresh);
        })();

        // ===== 음료 전달 완료 체크/해제 =====
        // 현황 영역은 실시간 갱신으로 통째로 바뀌므로 document 에 위임한다.
        var drinkServedUrlTpl = "{{ route('admin.rotation.event.members.drinkServed', ['id' => $event->id, 'memberId' => '__MID__']) }}";
        $(document).on('click', '.drink-serve-btn', function(){
            var $btn = $(this);
            if($btn.prop('disabled')){ return; }
            var next = String($btn.data('served')) !== '1';
            $btn.prop('disabled', true);
            $.ajax({
                url: drinkServedUrlTpl.replace('__MID__', $btn.data('member-id')),
                type: 'PATCH',
                data: { served: next ? 1 : 0 },
                success: function(res){
                    if(res?.status !== 200){ alert(res?.msg ?? '처리하지 못했습니다.'); return; }
                    // 실시간 연결이 없어도 바로 반영되도록 현황을 다시 불러온다. (연결돼 있으면 신호로도 한 번 더 갱신)
                    if(typeof window.refreshDrinkStatus === 'function'){ window.refreshDrinkStatus(); } else { location.reload(); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); },
            }).always(function(){ $btn.prop('disabled', false); });
        });

        // 음료 목록을 서버에 저장한다. (저장 후 새로고침해 현황까지 서버 값으로 다시 그린다)
        function saveDrinkList(drinks){
            $.ajax({
                url: "{{ route('admin.rotation.event.drinks.save', $event->id) }}",
                type: 'PUT',
                data: { drinks: drinks },
                success: function(res){
                    alert(res?.msg);
                    if(res?.status === 200){ location.reload(); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        }

        $('#drinkSaveBtn').on('click', async function(){
            // 입력창에 적어두고 추가를 안 누른 값도 함께 담는다.
            var pending = $('#drinkNameInput').val().trim();
            if(pending && drinkList.indexOf(pending) === -1 && drinkList.length < drinkMax){ drinkList.push(pending); }

            if(await salert({text: '음료 목록을 저장하시겠습니까? 목록에서 빠진 음료의 선택은 해제됩니다.'})){
                saveDrinkList(drinkList.slice());
            }
        });

        // 기본 음료(EventDrink::DEFAULT_NAMES) 한꺼번에 추가 + 저장.
        //  - 이미 있는 음료는 건너뛰고, 기존 목록 뒤에 순서대로 붙인다. (기존 음료·참가자 선택은 그대로)
        //  - 최대 개수를 넘으면 들어갈 수 있는 만큼만 넣는다.
        var DEFAULT_DRINKS = @json(\App\Models\EventDrink::DEFAULT_NAMES);
        $('#drinkDefaultBtn').on('click', async function(){
            var missing = DEFAULT_DRINKS.filter(function(n){ return drinkList.indexOf(n) === -1; });
            if(!missing.length){
                await salert({text: '기본 음료가 이미 모두 목록에 있어요.', cancel: false});
                return;
            }
            var room = drinkMax - drinkList.length;
            if(room <= 0){
                await salert({text: '음료는 최대 ' + drinkMax + '개까지 등록할 수 있어요. 먼저 음료를 지워 주세요.', cancel: false});
                return;
            }
            var adding = missing.slice(0, room);
            var esc = function(v){ return $('<div>').text(v).html(); };
            var html = '<div>기본 음료 <b>' + adding.length + '개</b>를 추가하고 저장할까요?</div>'
                + '<div class="mt-2">' + adding.map(esc).join('<br>') + '</div>'
                + (adding.length < missing.length ? '<div class="mt-2 text-muted">최대 개수 때문에 ' + (missing.length - adding.length) + '개는 넣지 못해요.</div>' : '');
            if(!(await salert({html: html, icon: 'question'}))){ return; }

            saveDrinkList(drinkList.concat(adding));
        });

        // 음료 카드 전체 접힘 상태 기억 (기본: 접힘)
        (function(){
            var KEY = 'rotable.checkin.drinkCardOpen';
            var el = document.getElementById('drinkCardCollapse');
            if(!el){ return; }
            var open = false;
            try { open = localStorage.getItem(KEY) === '1'; } catch(e) {}
            if(open){
                el.classList.add('show');
                $('[data-bs-target="#drinkCardCollapse"]').attr('aria-expanded', 'true');
            }
            function save(v){ try { localStorage.setItem(KEY, v ? '1' : '0'); } catch(e) {} }
            el.addEventListener('shown.bs.collapse', function(e){ if(e.target === el){ save(true); } });
            el.addEventListener('hidden.bs.collapse', function(e){ if(e.target === el){ save(false); } });
        })();
});
            </script>
        @endif
