import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Laravel Reverb(WebSocket) 연결용 Echo 인스턴스.
 * - 접속 정보는 Vite 환경변수(VITE_REVERB_*)에서 읽는다.
 * - Reverb 는 pusher 프로토콜과 호환되므로 broadcaster: 'reverb' 를 사용한다.
 */
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // private 채널 구독 시 /broadcasting/auth 로 세션 인증. CSRF 토큰 동봉.
    auth: {
        headers: {
            'X-CSRF-TOKEN':
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
    },
});

export default echo;
