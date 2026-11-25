import { Head } from "@inertiajs/react";
import PublicLayout from "@/Layouts/PublicLayout";
import WorldCupBrowser from "@/Components/WorldCupBrowser";

/** 이상형 월드컵 모음 (공개 버전). 목록/동작은 WorldCupBrowser 공통 컴포넌트. */
export default function WorldCup() {
    return (
        <PublicLayout>
            <Head title="이상형 월드컵" />
            <WorldCupBrowser backHref="/public/games" />
        </PublicLayout>
    );
}
