import { Head } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import WorldCupBrowser from "@/Components/WorldCupBrowser";

/** 이상형 월드컵 모음 (참가자용). 목록/동작은 WorldCupBrowser 공통 컴포넌트. */
export default function WorldCup() {
    return (
        <AppLayout>
            <Head title="이상형 월드컵" />
            <WorldCupBrowser backHref="/games" />
        </AppLayout>
    );
}
