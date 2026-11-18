import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { AppShell } from '@/Layouts/AppLayout';

const appName = 'Rotable';

createInertiaApp({
    title: () => appName,
    resolve: async (name) => {
        const module = await resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        );

        // 회원 화면은 지속 레이아웃(AppShell)으로 감싼다. 페이지를 옮겨도 header/하단 nav/실시간 구독이
        // 다시 만들어지지 않아 화면 전환 때 깜빡이지 않는다. (공개 페이지는 자체 PublicLayout 을 쓴다)
        if (!name.startsWith('Public/') && module.default.layout === undefined) {
            module.default.layout = (page) => <AppShell>{page}</AppShell>;
        }

        return module;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    // 기본 상단 로딩바 비활성화 → 커스텀 중앙 스피너(NavigationLoader)로 대체.
    progress: false,
});
