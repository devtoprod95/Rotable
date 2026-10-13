import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import MessageSender from '@/Components/MessageSender';
import useRealtimeChat from '@/useRealtimeChat';

/** 받은 메시지 한 건 (발신자 정보는 익명 처리) */
function ReceivedMessage({ message }) {
    return (
        <div className="rounded-2xl border border-secondary-100 bg-secondary-50 p-3.5">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="text-base">🙈</span>
                    <span className="font-bold text-secondary-700">익명의 상대</span>
                    {!message.is_read && (
                        <span className="rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-bold text-white">
                            NEW
                        </span>
                    )}
                </div>
                {message.sent_at && (
                    <span className="text-[11px] font-medium text-gray-400">{message.sent_at}</span>
                )}
            </div>
            <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">
                {message.body}
            </p>
        </div>
    );
}

/** 보낸 메시지 한 건 (받는 대상은 내가 선택했으므로 이름 노출) */
function SentMessage({ message }) {
    return (
        <div className="rounded-2xl border border-primary/15 bg-primary/5 p-3.5">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="text-base">💌</span>
                    <span className="font-bold text-secondary-700">
                        {message.receiver_name ?? '상대'}님에게
                    </span>
                    <span
                        className={
                            'rounded-full px-1.5 py-0.5 text-[10px] font-bold ' +
                            (message.is_read ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')
                        }
                    >
                        {message.is_read ? '읽음' : '안읽음'}
                    </span>
                </div>
                {message.sent_at && (
                    <span className="text-[11px] font-medium text-gray-400">{message.sent_at}</span>
                )}
            </div>
            <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">
                {message.body}
            </p>
        </div>
    );
}

/** 받은/보낸 탭 버튼 */
function TabButton({ active, onClick, children, badge }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                'flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-sm font-bold transition ' +
                (active ? 'bg-white text-primary shadow-sm' : 'text-secondary-400 hover:text-secondary-600')
            }
        >
            {children}
            {badge > 0 && (
                <span className="rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                    {badge > 99 ? '99+' : badge}
                </span>
            )}
        </button>
    );
}

export default function Chats() {
    const { auth, received = [], sent = [], partners = [], partnerGender, sendLimit } = usePage().props;

    const [tab, setTab] = useState('received'); // 'received' | 'sent'
    const [messages, setMessages] = useState(received);
    const [sentMessages, setSentMessages] = useState(sent);

    // props(received)가 바뀌면(부분 갱신/재방문 등) 목록 동기화.
    useEffect(() => {
        setMessages(received);
    }, [received]);

    // props(sent)가 바뀌면 보낸 목록 동기화.
    useEffect(() => {
        setSentMessages(sent);
    }, [sent]);

    const unreadInList = received.some((m) => !m.is_read);
    const unreadCount = messages.filter((m) => !m.is_read).length;

    // 메시지함 진입 시, 받은 메시지를 읽음 처리 (nav 뱃지 초기화).
    useEffect(() => {
        if (unreadInList) {
            router.post('/chats/messages/read', {}, { preserveScroll: true, preserveState: true });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // 소켓 구독:
    // - 받은 메시지: 목록 맨 위 추가 + 즉시 읽음 처리
    // - 보낸 메시지 읽음: 해당 메시지들을 '읽음'으로 갱신
    useRealtimeChat(
        auth?.user?.id,
        (message) => {
            setMessages((prev) => {
                if (prev.some((m) => m.id === message.id)) {
                    return prev;
                }
                return [{ ...message, is_read: true }, ...prev];
            });
            router.post('/chats/messages/read', {}, { preserveScroll: true, preserveState: true });
        },
        ({ message_ids: ids = [] }) => {
            setSentMessages((prev) =>
                prev.map((m) => (ids.includes(m.id) ? { ...m, is_read: true } : m)),
            );
        },
    );

    const list = tab === 'received' ? messages : sentMessages;

    return (
        <AppLayout>
            <Head title="Rotable" />

            <div className="min-h-full bg-gray-50 px-4 py-5">
                <h1 className="text-xl font-extrabold">메시지함 💌</h1>
                <p className="mt-1.5 text-sm text-gray-500">받은/보낸 익명 메시지를 확인하세요.</p>

                {/* 받은/보낸 탭 */}
                <div className="mt-4 flex gap-1 rounded-2xl bg-secondary-100 p-1">
                    <TabButton
                        active={tab === 'received'}
                        onClick={() => setTab('received')}
                        badge={unreadCount}
                    >
                        받은 메시지
                    </TabButton>
                    <TabButton active={tab === 'sent'} onClick={() => setTab('sent')}>
                        보낸 메시지
                    </TabButton>
                </div>

                {/* 목록 */}
                <div className="mt-4 space-y-2.5">
                    {list.length > 0 ? (
                        tab === 'received'
                            ? list.map((m) => <ReceivedMessage key={m.id} message={m} />)
                            : list.map((m) => <SentMessage key={m.id} message={m} />)
                    ) : (
                        <div className="rounded-2xl border border-dashed border-gray-200 bg-white py-10 text-center text-sm text-gray-400">
                            {tab === 'received' ? '아직 받은 메시지가 없어요.' : '아직 보낸 메시지가 없어요.'}
                        </div>
                    )}
                </div>

                {/* 이성 회원 리스트 + 전달 모달 (공통 컴포넌트) */}
                <div className="mt-8">
                    <MessageSender
                        partners={partners}
                        partnerGender={partnerGender}
                        sendLimit={sendLimit}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
