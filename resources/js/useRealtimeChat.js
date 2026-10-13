import { useEffect, useRef } from 'react';
import echo from '@/echo';

/**
 * 로그인한 회원의 개인 채널(chat.{memberId})을 구독한다.
 * - 'message.sent': 새 메시지 수신 → onMessage(message)
 * - 'message.read': 내가 보낸 메시지가 읽힘 → onRead({ message_ids })
 *
 * @param {number|null|undefined} memberId  로그인 회원 id (없으면 구독 안 함)
 * @param {(message: object) => void} onMessage  새 메시지 수신 콜백
 * @param {(payload: { message_ids: number[] }) => void} [onRead]  읽음 수신 콜백
 */
export default function useRealtimeChat(memberId, onMessage, onRead) {
    // 최신 콜백을 ref 로 유지해 재구독 없이 항상 최신 핸들러를 쓴다.
    const messageRef = useRef(onMessage);
    const readRef = useRef(onRead);
    messageRef.current = onMessage;
    readRef.current = onRead;

    useEffect(() => {
        if (!memberId) {
            return undefined;
        }

        const channelName = `chat.${memberId}`;
        const channel = echo.private(channelName);

        channel.listen('.message.sent', (message) => {
            messageRef.current?.(message);
        });
        channel.listen('.message.read', (payload) => {
            readRef.current?.(payload);
        });

        return () => {
            echo.leave(`private-${channelName}`);
        };
    }, [memberId]);
}
