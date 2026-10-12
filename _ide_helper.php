<?php

// 이 파일은 VSCode(PHP Intelephense 등) 및 PHPStorm 등의 IDE 자동완성을 돕기 위한 헬퍼 파일입니다.
// 실제 런타임에는 로드되지 않으며 성능에 영향을 주지 않습니다.

namespace {
    class IdeHelperAuthGuard {
        /**
         * @return \App\Models\Admin|\App\Models\User|null
         */
        public function user() {
            return null;
        }
    }
    
    /**
     * @param string|null $guard
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard|IdeHelperAuthGuard
     */
    function auth($guard = null) {
        return new IdeHelperAuthGuard();
    }
}

namespace Illuminate\Support\Facades {
    class Auth {
        /**
         * @return \App\Models\Admin|\App\Models\User|null
         */
        public static function user() {
            return null;
        }
    }
}
