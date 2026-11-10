import { useState } from 'react';
import TextField from '@/Components/TextField';

/** CSRF 토큰 (meta 태그에서) */
function csrfToken() {
    return (
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    );
}

/**
 * 관리자 문의 폼.
 * 입장 가능한 회차가 없을 때(입장/등록 화면) 게스트가 운영진에게 남기는 문의.
 * 제출하면 /contact 로 전송되어 텔레그램으로 전달된다.
 */
export default function ContactForm() {
    const [name, setName] = useState('');
    const [contact, setContact] = useState('');
    const [message, setMessage] = useState('');
    const [sending, setSending] = useState(false);
    const [done, setDone] = useState(false);
    const [error, setError] = useState('');

    async function submit(e) {
        e.preventDefault();
        setError('');

        if (!contact.trim()) {
            setError('연락 받으실 휴대폰 번호를 입력해주세요.');
            return;
        }
        // 숫자만 남긴다. (하이픈/공백 제거)
        const phone = contact.replace(/\D+/g, '');
        if (!/^010\d{8}$/.test(phone)) {
            setError('휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.');
            return;
        }
        if (!message.trim()) {
            setError('문의 내용을 입력해주세요.');
            return;
        }

        setSending(true);
        try {
            const res = await fetch('/contact', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ name, contact: phone, message }),
            });
            if (res.ok) {
                setDone(true);
            } else {
                setError('잠시 후 다시 시도해주세요.');
            }
        } catch {
            setError('네트워크 오류예요. 잠시 후 다시 시도해주세요.');
        } finally {
            setSending(false);
        }
    }

    if (done) {
        return (
            <div className="mt-6">
                <div className="flex flex-col items-center gap-4 rounded-3xl border border-primary-100 bg-gradient-to-b from-primary-50/80 to-white px-6 py-8 text-center">
                    <div className="flex h-20 w-20 items-center justify-center rounded-full bg-white text-4xl shadow-sm shadow-primary/10">
                        💌
                    </div>
                    <div>
                        <p className="text-lg font-extrabold text-secondary-800">접수 완료!</p>
                        <p className="mt-2 text-sm leading-relaxed text-secondary-500">
                            남겨주신 연락처
                            {contact.trim() && (
                                <>
                                    {' '}
                                    <span className="font-bold text-primary">{contact.trim()}</span>
                                </>
                            )}
                            로<br />운영진이 <b className="text-secondary-700">확인 후 빠르게</b> 연락드릴게요.
                        </p>
                    </div>

                </div>

                <button
                    type="button"
                    onClick={() => {
                        setDone(false);
                        setName('');
                        setContact('');
                        setMessage('');
                    }}
                    className="mt-4 w-full rounded-full border border-secondary-200 bg-white py-2.5 text-sm font-semibold text-secondary-500 transition hover:bg-secondary-50"
                >
                    문의 다시 남기기
                </button>
            </div>
        );
    }

    return (
        <form onSubmit={submit} autoComplete="off" className="mt-6 flex flex-col gap-4 text-left">
            <TextField
                label="이름 (선택)"
                value={name}
                onChange={setName}
                placeholder="이름"
                maxLength={50}
            />
            <TextField
                label="휴대폰 번호"
                type="tel"
                value={contact}
                onChange={(v) => setContact(v.replace(/\D+/g, '').slice(0, 11))}
                placeholder="01012345678"
                maxLength={11}
            />
            <label className="flex flex-col gap-1.5">
                <span className="text-sm font-semibold text-gray-700">문의 내용</span>
                <textarea
                    autoComplete="off"
                    value={message}
                    onChange={(e) => setMessage(e.target.value.slice(0, 1000))}
                    rows={4}
                    placeholder="예: 다음 회차는 언제 열리나요?"
                    maxLength={1000}
                    className="resize-none rounded-lg border border-gray-300 px-3.5 py-3 text-base outline-none transition focus:ring-2 focus:ring-primary-200"
                />
            </label>

            {error && <span className="text-sm text-red-500">{error}</span>}

            <button
                type="submit"
                disabled={sending}
                className="mt-1 rounded-full bg-primary py-3 text-base font-bold text-white transition hover:bg-primary-600 disabled:opacity-60"
            >
                {sending ? '보내는 중…' : '문의 보내기'}
            </button>
        </form>
    );
}
