import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Placeholder from '@/Components/Placeholder';

export default function Votes() {
    return (
        <AppLayout>
            <Head title="투표하기" />
            <Placeholder icon="🗳️" title="투표하기" desc="오늘의 투표가 곧 열립니다. 마음에 드는 상대에게 투표해보세요." />
        </AppLayout>
    );
}
