import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Placeholder from '@/Components/Placeholder';

export default function Chats() {
    return (
        <AppLayout>
            <Head title="채팅함" />
            <Placeholder icon="💬" title="채팅함" desc="아직 대화가 없어요. 마음이 통하면 여기서 대화를 시작하세요." />
        </AppLayout>
    );
}
