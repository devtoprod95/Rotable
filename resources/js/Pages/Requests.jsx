import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Placeholder from '@/Components/Placeholder';

export default function Requests() {
    return (
        <AppLayout>
            <Head title="Rotable" />
            <Placeholder icon="✋" title="요청하기" desc="보낸/받은 요청이 여기에 표시됩니다." />
        </AppLayout>
    );
}
