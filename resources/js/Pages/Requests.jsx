import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import echo from '@/echo';

/** 내가 보낸 요청 한 건 */
function RequestItem({ request }) {
    return (
        <div className="rounded-2xl border border-primary/15 bg-primary/5 p-3.5">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <span className="text-base">✋</span>
                    <span className="font-bold text-secondary-700">관리자에게</span>
                    <span
                        className={
                            'rounded-full px-1.5 py-0.5 text-[10px] font-bold ' +
                            (request.is_read
                                ? 'bg-green-100 text-green-700'
                                : 'bg-gray-100 text-gray-500')
                        }
                    >
                        {request.is_read ? '확인됨' : '확인 대기'}
                    </span>
                </div>
                {request.created_at && (
                    <span className="text-[11px] font-medium text-gray-400">{request.created_at}</span>
                )}
            </div>
            <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">
                {request.body}
            </p>
        </div>
    );
}

export default function Requests() {
    const { auth, requests = [], eventClosed = false, flash = {} } = usePage().props;
    const [list, setList] = useState(requests);
    const [cooldown, setCooldown] = useState(false);

    // props(requests)가 바뀌면(부분 갱신/재방문) 목록 동기화.
    useEffect(() => {
        setList(requests);
    }, [requests]);

    // 관리자가 내 요청을 확인하면 실시간으로 '확인됨' 반영.
    useEffect(() => {
        const memberId = auth?.user?.id;
        if (!memberId) {
            return undefined;
        }
        const channelName = `chat.${memberId}`;
        const channel = echo.private(channelName);
        const handler = ({ request_ids: ids = [] }) => {
            setList((prev) =>
                prev.map((req) =>
                    ids.includes(req.id) ? { ...req, is_read: true } : req,
                ),
            );
        };
        channel.listen('.request.read', handler);
        return () => {
            // 같은 chat.{id} 채널을 BottomNav 등이 공유하므로 채널 자체를 leave 하지 않고
            // 이 이벤트 리스너만 해제한다.
            channel.stopListening('.request.read', handler);
        };
    }, [auth?.user?.id]);

    const { data, setData, post, processing, errors, reset } = useForm({ body: '' });

    function submit(e) {
        e.preventDefault();
        if (!data.body.trim() || processing || cooldown) {
            return;
        }
        post('/requests', {
            preserveScroll: true,
            preserveState: true,
            only: ['requests', 'flash'],
            onSuccess: () => reset('body'),
            // 성공/실패와 무관하게 전송 직후 짧은 쿨다운으로 연타를 1차 차단한다.
            onFinish: () => {
                setCooldown(true);
                setTimeout(() => setCooldown(false), 2000);
            },
        });
    }

    return (
        <AppLayout>
            <Head title="Rotable" />

            <div className="min-h-full bg-gray-50 px-4 py-5">
                <h1 className="text-xl font-extrabold">요청하기 ✋</h1>
                <p className="mt-1.5 text-sm text-gray-500">
                    관리자에게 궁금한 점이나 요청사항을 보내보세요.
                </p>

                {/* 요청 입력 폼 (행사 종료 시 안내문구로 대체) */}
                {eventClosed ? (
                    <div className="mt-4 flex items-start gap-2 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm leading-relaxed text-gray-500">
                        <span className="text-base">🔒</span>
                        <span>
                            종료된 행사예요. 더 이상 요청을 보낼 수 없어요.
                            <br />
                            아래에서 지난 요청 내역은 계속 확인할 수 있어요.
                        </span>
                    </div>
                ) : (
                    <form onSubmit={submit} className="mt-4">
                        <textarea
                            value={data.body}
                            onChange={(e) => setData('body', e.target.value)}
                            rows={4}
                            maxLength={1000}
                            placeholder="요청 내용을 입력하세요. (예: 물이 필요해요.)"
                            className="w-full resize-none rounded-2xl border border-secondary-100 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-primary"
                        />
                        {errors.body && (
                            <p className="mt-1 text-xs font-medium text-red-500">{errors.body}</p>
                        )}
                        {flash.error && (
                            <p className="mt-1 text-xs font-medium text-red-500">{flash.error}</p>
                        )}
                        {flash.success && (
                            <p className="mt-1 text-xs font-medium text-green-600">{flash.success}</p>
                        )}
                        <div className="mt-2 flex items-center justify-between">
                            <span className="text-[11px] text-gray-400">{data.body.length}/1000</span>
                            <button
                                type="submit"
                                disabled={!data.body.trim() || processing || cooldown}
                                className="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing || cooldown ? '전송 중…' : '요청 보내기'}
                            </button>
                        </div>
                    </form>
                )}

                {/* 내가 보낸 요청 목록 */}
                <div className="mt-8">
                    <h2 className="text-lg font-extrabold">내 요청 내역</h2>
                    <div className="mt-4 space-y-2.5">
                        {list.length > 0 ? (
                            list.map((req) => <RequestItem key={req.id} request={req} />)
                        ) : (
                            <div className="rounded-2xl border border-dashed border-gray-200 bg-white py-10 text-center text-sm text-gray-400">
                                아직 보낸 요청이 없어요.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
