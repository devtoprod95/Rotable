import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import MessageSender from '@/Components/MessageSender';
import Placeholder from '@/Components/Placeholder';
import useRealtimeChat from '@/useRealtimeChat';

/** 받은 어필 한 건 (보낸 사람 번호표를 보여 준다) */
function ReceivedNote({ note }) {
    const female = note.sender_gender === 'F';

    // 관리자가 보낸 메시지는 "관리자"로 표시한다.
    if (note.is_admin) {
        return (
            <div className="rounded-2xl border border-amber-200 bg-amber-50 p-3.5">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <span className="text-base">📢</span>
                        <span className="font-bold text-secondary-700">관리자</span>
                        {!note.is_read && (
                            <span className="rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-bold text-white">NEW</span>
                        )}
                    </div>
                    {note.sent_at && <span className="text-[11px] font-medium text-gray-400">{note.sent_at}</span>}
                </div>
                <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">{note.body}</p>
            </div>
        );
    }

    return (
        <div className="rounded-2xl border border-secondary-100 bg-secondary-50 p-3.5">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="text-base">{female ? '👩' : '👨'}</span>
                    <span className="font-bold text-secondary-700">
                        {note.sender_name ? `${note.sender_name}호` : '상대'}님의 어필
                    </span>
                    {!note.is_read && (
                        <span className="rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-bold text-white">NEW</span>
                    )}
                </div>
                {note.sent_at && <span className="text-[11px] font-medium text-gray-400">{note.sent_at}</span>}
            </div>
            <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">{note.body}</p>
        </div>
    );
}

/** 보낸 어필 한 건 */
function SentNote({ note }) {
    return (
        <div className="rounded-2xl border border-primary/15 bg-primary/5 p-3.5">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="text-base">💌</span>
                    <span className="font-bold text-secondary-700">
                        {note.receiver_name ? `${note.receiver_name}호` : '상대'}님에게
                    </span>
                    <span
                        className={
                            'rounded-full px-1.5 py-0.5 text-[10px] font-bold ' +
                            (note.is_read ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')
                        }
                    >
                        {note.is_read ? '읽음' : '안읽음'}
                    </span>
                </div>
                {note.sent_at && <span className="text-[11px] font-medium text-gray-400">{note.sent_at}</span>}
            </div>
            <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">{note.body}</p>
        </div>
    );
}

/**
 * 어필 (1:1 소개팅, 매칭 결과 뒤).
 * 서로 매칭되지 않은 이성에게 딱 한 번, 내 번호표가 보이는 메시지를 보낸다.
 * 서버는 기존 메시지 기능(messages)을 그대로 쓰고, 이 화면은 받은/보낸 어필과 보내기를 보여준다.
 */
export default function Notes() {
    const { auth, available = true, received = [], sent = [], partners = [], partnerGender, sendLimit } = usePage().props;

    const [tab, setTab] = useState('received'); // 'received' | 'sent'
    const markedRef = useRef(new Set());

    const unreadCount = received.filter((n) => !n.is_read).length;

    // 받은 탭이 보이는 동안 안읽은 어필을 읽음 처리한다.
    useEffect(() => {
        if (tab !== 'received') return;

        const ids = received.filter((n) => !n.is_read && !markedRef.current.has(n.id)).map((n) => n.id);
        if (ids.length === 0) return;

        ids.forEach((id) => markedRef.current.add(id));
        router.post('/chats/messages/read', { message_ids: ids }, { preserveScroll: true, preserveState: true });
    }, [tab, received]);

    // 새 어필이 오면(또는 내 어필이 읽히면) 목록을 서버에서 다시 받는다.
    useRealtimeChat(
        auth?.user?.id,
        () => router.reload({ only: ['received', 'sent', 'unreadCount'] }),
        () => router.reload({ only: ['sent'] }),
    );

    const list = tab === 'received' ? received : sent;

    // 매칭이 종료되기 전에는 이용할 수 없다. (탭은 늘 보이고, 종료되면 이 화면이 자동으로 다시 열린다)
    if (!available) {
        return (
            <AppLayout center>
                <Head title="Rotable" />
                <div className="flex w-full flex-col bg-gray-50 p-4">
                    {/* 매칭 결과 화면의 "아직 열리지 않음" 안내와 같은 모양으로, 화면 정중앙에 둔다. */}
                    <Placeholder
                        icon="🔒"
                        title="아직 어필할 수 없어요"
                        desc={
                            <>
                                어필은 <b>매칭이 종료된 뒤</b>에 이용할 수 있어요.
                                <br />
                                매칭 결과를 확인한 뒤,
                                <br />
                                이어지지 않은 상대에게 마음을 전해보세요.
                            </>
                        }
                    />
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Rotable" />

            <div className="min-h-full bg-gray-50 px-4 py-5">
                <h1 className="text-xl font-extrabold">어필 💌</h1>
                <p className="mt-1.5 text-sm text-gray-500">
                    매칭이 이어지지 않은 상대에게 마음을 한 번 전해보세요. 내 번호표가 함께 보여요.
                </p>

                {/* 받은/보낸 탭 */}
                <div className="mt-4 flex gap-1 rounded-2xl bg-secondary-100 p-1">
                    {[
                        { key: 'received', label: '받은 어필', badge: unreadCount },
                        { key: 'sent', label: '보낸 어필', badge: 0 },
                    ].map((t) => (
                        <button
                            key={t.key}
                            type="button"
                            onClick={() => setTab(t.key)}
                            className={
                                'flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-sm font-bold transition ' +
                                (tab === t.key ? 'bg-white text-primary shadow-sm' : 'text-secondary-400 hover:text-secondary-600')
                            }
                        >
                            {t.label}
                            {t.badge > 0 && (
                                <span className="rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                    {t.badge > 99 ? '99+' : t.badge}
                                </span>
                            )}
                        </button>
                    ))}
                </div>

                <div className="mt-4 space-y-2.5">
                    {list.length > 0 ? (
                        list.map((n) => (tab === 'received' ? <ReceivedNote key={n.id} note={n} /> : <SentNote key={n.id} note={n} />))
                    ) : (
                        <div className="rounded-2xl border border-dashed border-gray-200 bg-white py-10 text-center text-sm text-gray-400">
                            {tab === 'received' ? '아직 받은 어필이 없어요.' : '아직 보낸 어필이 없어요.'}
                        </div>
                    )}
                </div>

                {/* 매칭되지 않은 이성 리스트 + 전달 모달 (메시지 보내기 공통 컴포넌트, 익명 아님·1회) */}
                <div className="mt-8">
                    <MessageSender
                        partners={partners}
                        partnerGender={partnerGender}
                        sendLimit={sendLimit}
                        canSend
                        endpoint="/notes"
                        only={['received', 'sent', 'sendLimit', 'flash']}
                        anonymous={false}
                        heading="어필 보내기"
                        description="매칭되지 않은 상대에게 딱 한 번, 내 번호표가 보이는 어필을 전할 수 있어요."
                        remainingLabel="남은 횟수"
                        limitText="어필은 한 번만 보낼 수 있어요. 이미 보냈어요."
                    />
                </div>
            </div>
        </AppLayout>
    );
}
