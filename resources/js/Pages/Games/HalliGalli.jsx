import AppLayout from "@/Layouts/AppLayout";
import HalliGalliBoard from "@/Components/HalliGalliBoard";
import echo from "@/echo";

/**
 * 할리갈리 - 같은 테이블끼리 각자 폰으로 즐기는 실시간 게임. (와인파티 진행중)
 * 테이블 private 채널로 상태를 받는다.
 */
export default function HalliGalli({
    eventId,
    tableNo,
    round,
    myId,
    initialState,
}) {
    const subscribe = (onMessage) => {
        const channelName = `table-halli.${eventId}.${tableNo}`;
        echo.private(channelName).listen(".table-halli.updated", onMessage);

        return () => echo.leave(`private-${channelName}`);
    };

    return (
        <HalliGalliBoard
            Layout={AppLayout}
            actionUrl="/games/halli/action"
            subscribe={subscribe}
            badge={`${tableNo}번 테이블`}
            backHref="/games"
            round={round}
            myId={myId}
            initialState={initialState}
        />
    );
}
