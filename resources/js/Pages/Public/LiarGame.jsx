import { useMemo } from "react";
import PublicLayout from "@/Layouts/PublicLayout";
import LiarGameBoard from "@/Components/LiarGameBoard";
import echo from "@/echo";

/** 이 기기를 구분하는 id (localStorage 유지). 할리갈리 공개방과 같은 값을 공유한다. */
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
 * 공개 라이어 게임 (비로그인).
 * 지금 이 페이지에 접속해 참가한 사람들이 한 방에서 즐긴다. public 채널 'public-liar' 로 공개 상태를 받는다.
 */
export default function PublicLiarGame({ initialState }) {
    const clientId = useMemo(() => getClientId(), []);

    const subscribe = (onMessage) => {
        echo.channel("public-liar").listen(".public-liar.updated", onMessage);

        return () => echo.leave("public-liar");
    };

    return (
        <LiarGameBoard
            Layout={PublicLayout}
            actionUrl="/public/games/liar/action"
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
