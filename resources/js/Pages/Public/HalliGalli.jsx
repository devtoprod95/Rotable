import { useMemo } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import HalliGalliBoard from "@/Components/HalliGalliBoard";
import echo from "@/echo";

/** 이 기기를 구분하는 id (localStorage 유지). 로그인이 없는 공개방의 참가자 식별용. */
function getClientId() {
    const KEY = "public-halli-client-id";
    try {
        let id = localStorage.getItem(KEY);
        if (!id) {
            id = Math.random().toString(36).slice(2) + Date.now().toString(36);
            localStorage.setItem(KEY, id);
        }
        return id;
    } catch {
        return Math.random().toString(36).slice(2);
    }
}

/**
 * 공개 할리갈리 (비로그인).
 * 지금 이 페이지에 접속해 참가한 사람들이 한 방에서 즐긴다. public 채널 'public-halli' 로 상태를 받는다.
 */
export default function PublicHalliGalli({ initialState }) {
    const clientId = useMemo(() => getClientId(), []);

    const subscribe = (onMessage) => {
        echo.channel("public-halli").listen(".public-halli.updated", onMessage);

        return () => echo.leave("public-halli");
    };

    return (
        <HalliGalliBoard
            Layout={PublicLayout}
            actionUrl="/public/games/halli/action"
            subscribe={subscribe}
            badge="공개방"
            backHref="/public/games"
            myId={clientId}
            initialState={initialState}
            askNickname
            extraBody={{ client_id: clientId }}
        />
    );
}
