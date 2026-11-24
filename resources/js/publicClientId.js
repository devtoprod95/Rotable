/**
 * 로그인이 없는 공개 게임에서 이 기기를 구분하는 id.
 * localStorage 에 보관해 새로고침해도 같은 참가자로 인식된다.
 */
const KEY = "public-halli-client-id";

export function getPublicClientId() {
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
