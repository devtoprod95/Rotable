<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var \App\Models\Member|null $member */
        $member = $request->user('web');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $member ? [
                    'id'       => $member->id,
                    'name'     => $member->name,
                    'phone'    => $member->phone,
                    'gender'   => $member->gender,
                    'event_id' => $member->event_id,
                ] : null,
            ],
            // 상단 header 표시용 - 로그인 회원이 참여한 행사명.
            'eventName' => fn () => $member
                ? \App\Models\EventRound::whereKey($member->event_id)->value('title')
                : null,
            // 프론트 nav/문구 분기용 - 행사 타입(wine: 와인파티, one: 1:1).
            'eventType' => fn () => $member
                ? \App\Models\EventRound::whereKey($member->event_id)->value('type')
                : null,
            // 하단 nav 메시지함 뱃지용 - 나에게 온 안읽은 메시지 개수.
            'unreadCount' => fn () => $member
                ? \App\Models\Message::where('receiver_id', $member->id)->whereNull('read_at')->count()
                : 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                // 동일 메시지를 연속으로 flash 할 때도 프론트에서 매번 토스트를 띄울 수 있도록
                // success/error 가 있을 때마다 바뀌는 고유 토큰을 함께 내려준다.
                'ts'      => fn () => ($request->session()->get('success') || $request->session()->get('error'))
                    ? (string) \Illuminate\Support\Str::uuid()
                    : null,
            ],
            'bypassRedirectName' => \App\Models\Member::BYPASS_REDIRECT_NAME,
            'isProduction'       => app()->environment('production'),
        ];
    }
}
