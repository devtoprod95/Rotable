import AppLayout from "@/Layouts/AppLayout";
import LiarGameBoard from "@/Components/LiarGameBoard";
import echo from "@/echo";

/**
 * 라이어 게임 - 같은 테이블끼리 각자 폰으로 즐기는 실시간 게임. (와인파티 진행중)
 * 테이블 private 채널로 공개 상태를 받는다.
 */
export default function LiarGame({
    eventId,
    tableNo,
    round,
    myId,
    initialState,
    initialSecret,
}) {
    const subscribe = (onMessage) => {
        const channelName = `table-liar.${eventId}.${tableNo}`;
        echo.private(channelName).listen(".table-liar.updated", onMessage);

        return () => echo.leave(`private-${channelName}`);
    };

    return (
        <LiarGameBoard
            Layout={AppLayout}
            actionUrl="/games/liar/action"
            subscribe={subscribe}
            badge={`${tableNo}번 테이블`}
            backHref="/games"
            round={round}
            myId={myId}
            initialState={initialState}
            initialSecret={initialSecret}
        />
    );
}
