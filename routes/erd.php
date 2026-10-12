<?php

use Illuminate\Support\Facades\Route;

Route::middleware('superAdmin')->group(function () {

    Route::view('/', 'erd.adRequest', ['title' => '게재신청서'])->name('index');

    // ERD 섹션 정의
    $erdSections = [
        'adRequest' => '게재신청서',
        'inventory' => '지면',
        'campaign'  => '캠페인',
        'sojae'     => '소재',
        'admin'     => '관리자',
    ];

    // 섹션별 라우트 자동 생성
    foreach ($erdSections as $section => $title) {
        Route::view("/{$section}", "erd.{$section}", ['title' => $title])->name($section);
    }
});
