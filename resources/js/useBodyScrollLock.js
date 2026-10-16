import { useEffect } from 'react';

/**
 * 모달 등이 열려 있는 동안 배경(페이지) 스크롤을 잠근다.
 *
 * 이 앱은 body(및 html)가 스크롤 컨테이너라 둘 다 overflow 를 잠근다.
 * 모바일 Chrome 에서 overflow:hidden 만으로는 배경 스크롤이 완전히 막히지 않으므로,
 * body 를 position:fixed 로 고정했다가 해제 시 원래 스크롤 위치로 복원한다.
 *
 * @param {boolean} active 잠금 활성화 여부 (true 일 때만 잠근다)
 */
export default function useBodyScrollLock(active) {
    useEffect(() => {
        if (!active) return undefined;

        const scrollY = window.scrollY;
        const html = document.documentElement;
        const body = document.body;
        const prev = {
            htmlOverflow: html.style.overflow,
            bodyOverflow: body.style.overflow,
            bodyPosition: body.style.position,
            bodyTop: body.style.top,
            bodyWidth: body.style.width,
        };

        html.style.overflow = 'hidden';
        body.style.overflow = 'hidden';
        body.style.position = 'fixed';
        body.style.top = `-${scrollY}px`;
        body.style.width = '100%';

        return () => {
            html.style.overflow = prev.htmlOverflow;
            body.style.overflow = prev.bodyOverflow;
            body.style.position = prev.bodyPosition;
            body.style.top = prev.bodyTop;
            body.style.width = prev.bodyWidth;
            window.scrollTo(0, scrollY);
        };
    }, [active]);
}
