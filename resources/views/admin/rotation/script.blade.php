@extends('layouts.app')

@section('title', '발표 멘트 편집')

@section('content')
{{--
    호스트 발표 멘트(대본) 편집 페이지.
    - 유형(와인파티/1:1)별로 섹션(번호·제목·줄)을 편집하고 [저장]하면 DB(event_scripts)에 저장된다.
    - 공개 페이지(public.script)는 저장값이 있으면 그걸, 없으면 코드 기본 멘트를 쓴다.
--}}
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-microphone text-pink me-1"></i> 발표 멘트 편집</h3>
                <span class="text-muted fs-5">— 호스트가 현장에서 읽는 진행 멘트를 유형별로 편집합니다.</span>

                <div class="ms-auto d-flex gap-2">
                    <a href="#" target="_blank" rel="noopener" class="btn btn-outline-pink" id="scriptPreviewBtn">
                        <i class="ti ti-microphone me-1"></i> 발표 멘트
                    </a>
                    <a href="#" target="_blank" rel="noopener" class="btn btn-outline-azure" id="guideSlideBtn">
                        <i class="ti ti-presentation me-1"></i> 안내 슬라이드
                    </a>
                    <button type="button" class="btn btn-success" id="scriptSaveBtn">
                        <i class="ti ti-device-floppy me-1"></i> 저장
                    </button>
                </div>
            </div>

            <div class="card-body">
                {{-- 유형 토글 --}}
                <div class="btn-group mb-3" role="group" id="scriptType">
                    <button type="button" class="btn btn-outline-pink {{ $type === 'wine' ? 'active' : '' }}" data-type="wine">🍷 와인파티</button>
                    <button type="button" class="btn btn-outline-pink {{ $type === 'solo' ? 'active' : '' }}" data-type="solo">💗 1:1 소개팅</button>
                </div>

                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label mb-1">멘트 제목</label>
                        <input type="text" class="form-control" id="scriptTitle" placeholder="예: 와인파티 발표 멘트">
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" id="scriptAddSection">
                            <i class="ti ti-plus me-1"></i> 섹션 추가
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="scriptResetDefault">
                            <i class="ti ti-rotate me-1"></i> 기본값으로 되돌리기
                        </button>
                    </div>
                </div>

                <div class="alert alert-info py-2 mb-3">
                    <div class="text-secondary" style="font-size:0.92em;">
                        <i class="ti ti-info-circle me-1"></i>
                        각 섹션은 <b>번호 · 제목 · 여러 줄</b>로 구성됩니다. 줄은 한 줄에 하나씩 입력하세요.
                        <b>저장</b>해야 공개 페이지에 반영됩니다. (되돌리기는 저장 전까지 화면에만 적용)
                    </div>
                </div>

                <div id="scriptSections"></div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // 서버에서 받은 현재 멘트(저장값 또는 기본값) + 기본값(되돌리기용)
        var SCRIPTS = {
            wine: @json($wine),
            solo: @json($solo),
        };
        var DEFAULTS = @json($defaults);
        var scriptSaveUrl = "{{ route('admin.rotation.guide.script.save') }}";
        var publicBase = "{{ url('/public/script') }}";
        var guideBase  = "{{ url('/public/guide') }}";

        var currentType = @json($type);

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }

        // 순번 → 원형 숫자(①②③…). 20 초과는 그냥 "N" 으로.
        function circledNo(n){
            var circled = ['①','②','③','④','⑤','⑥','⑦','⑧','⑨','⑩','⑪','⑫','⑬','⑭','⑮','⑯','⑰','⑱','⑲','⑳'];
            return circled[n - 1] || String(n);
        }

        // 섹션 카드 HTML (번호는 자동 부여 → 읽기 전용 표시, 제목/줄만 입력)
        function sectionCard(sec, idx){
            var linesText = (sec.lines || []).join('\n');
            return ''
              + '<div class="card mb-2" data-section>'
              +   '<div class="card-body p-3">'
              +     '<div class="row g-2 align-items-center mb-2">'
              +       '<div class="col-auto">'
              +         '<span class="s-no-badge badge bg-pink-lt" style="font-size:1.3rem;min-width:2.2rem;"></span>'
              +       '</div>'
              +       '<div class="col">'
              +         '<label class="form-label mb-1 small">제목</label>'
              +         '<input type="text" class="form-control form-control-sm s-heading" value="' + esc(sec.heading) + '" placeholder="섹션 제목">'
              +       '</div>'
              +       '<div class="col-auto d-flex gap-1" style="padding-top:1.4rem;">'
              +         '<button type="button" class="btn btn-outline-secondary s-up" title="위로"><i class="ti ti-arrow-up"></i></button>'
              +         '<button type="button" class="btn btn-outline-secondary s-down" title="아래로"><i class="ti ti-arrow-down"></i></button>'
              +         '<button type="button" class="btn btn-outline-danger s-del"><i class="ti ti-trash me-1"></i>삭제</button>'
              +       '</div>'
              +     '</div>'
              +     '<label class="form-label mb-1 small">멘트 줄 (한 줄에 하나씩)</label>'
              +     '<textarea class="form-control s-lines" rows="5" style="line-height:1.6;">' + esc(linesText) + '</textarea>'
              +   '</div>'
              + '</div>';
        }

        // 섹션 순번(번호 뱃지)을 위치 순서대로 다시 매긴다.
        function renumberSections(){
            $('#scriptSections [data-section]').each(function(i){
                $(this).find('.s-no-badge').text(circledNo(i + 1));
            });
        }

        // 데이터 → 화면 렌더
        function renderSections(script){
            $('#scriptTitle').val(script.title || '');
            var html = (script.sections || []).map(sectionCard).join('');
            $('#scriptSections').html(html || '<div class="text-muted text-center py-4">섹션이 없습니다. [섹션 추가]를 눌러 시작하세요.</div>');
            renumberSections();
        }

        // 화면 → 데이터 수집 (번호는 위치 순서대로 자동 부여)
        function collect(){
            var sections = [];
            $('#scriptSections [data-section]').each(function(i){
                var $s = $(this);
                var lines = ($s.find('.s-lines').val() || '')
                    .split('\n').map(function(l){ return l.trim(); }).filter(function(l){ return l !== ''; });
                sections.push({
                    no:      circledNo(i + 1),   // 자동 번호
                    heading: ($s.find('.s-heading').val() || '').trim(),
                    lines:   lines,
                });
            });
            return { title: ($('#scriptTitle').val() || '').trim(), sections: sections };
        }

        // 현재 편집 내용을 메모리(SCRIPTS)에 반영 (유형 전환 시 유실 방지)
        function stash(){
            var d = collect();
            SCRIPTS[currentType] = { title: d.title, sections: d.sections };
        }

        function updatePreviewLink(){
            $('#scriptPreviewBtn').attr('href', publicBase + '/' + currentType);
            $('#guideSlideBtn').attr('href', guideBase + '/' + currentType);
        }

        // 유형 전환
        $('#scriptType').on('click', 'button', function(){
            var type = $(this).data('type');
            if(type === currentType){ return; }
            stash();
            $('#scriptType button').removeClass('active');
            $(this).addClass('active');
            currentType = type;
            renderSections(SCRIPTS[currentType]);
            updatePreviewLink();
        });

        // 섹션 추가
        $('#scriptAddSection').on('click', function(){
            var $wrap = $('#scriptSections');
            if($wrap.find('[data-section]').length === 0){ $wrap.empty(); }
            $wrap.append(sectionCard({ no: '', heading: '', lines: [] }));
            renumberSections();
        });

        // 섹션 삭제/이동 (위임)
        $('#scriptSections').on('click', '.s-del', function(){
            $(this).closest('[data-section]').remove();
            renumberSections();
        });
        $('#scriptSections').on('click', '.s-up', function(){
            var $card = $(this).closest('[data-section]');
            var $prev = $card.prev('[data-section]');
            if($prev.length){ $card.insertBefore($prev); renumberSections(); }
        });
        $('#scriptSections').on('click', '.s-down', function(){
            var $card = $(this).closest('[data-section]');
            var $next = $card.next('[data-section]');
            if($next.length){ $card.insertAfter($next); renumberSections(); }
        });

        // 기본값으로 되돌리기 (화면만, 저장 전까지)
        $('#scriptResetDefault').on('click', async function(){
            if(!(await salert({ text: '현재 유형의 멘트를 기본값으로 되돌릴까요? (저장 전까지는 화면에만 적용돼요)' }))){ return; }
            var def = DEFAULTS[currentType];
            renderSections({ title: def.title, sections: def.sections });
        });

        // 저장
        $('#scriptSaveBtn').on('click', async function(){
            var d = collect();
            if(!(await salert({ text: '현재 유형(' + (currentType === 'wine' ? '와인파티' : '1:1 소개팅') + ') 발표 멘트를 저장할까요?' }))){ return; }
            $.ajax({
                url: scriptSaveUrl,
                type: 'POST',
                data: { type: currentType, title: d.title, sections: d.sections },
                success: function(res){
                    SCRIPTS[currentType] = { title: d.title, sections: d.sections };
                    alert(res?.msg || '저장되었습니다.');
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 최초 렌더
        renderSections(SCRIPTS[currentType]);
        updatePreviewLink();
    });
</script>
@endsection
