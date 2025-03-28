<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;
use App\Http\Controllers\ProfileController;

// --- 商品関連 ---
Route::get('/', [ItemController::class, 'index'])->name('items.index'); // 商品一覧
Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('items.show'); // 商品詳細

// 商品出品・編集・削除（認証ユーザー専用）
Route::middleware('auth')->group(function () {
    // 商品出品・編集・削除
    Route::get('/sell', [ItemController::class, 'create'])->name('sell');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::get('/items/{item_id}/edit', [ItemController::class, 'edit'])->name('items.edit');
    Route::post('/items/{item_id}/update', [ItemController::class, 'update'])->name('items.update');
    Route::post('/items/{item_id}/delete', [ItemController::class, 'destroy'])->name('items.delete');

    // --- 購入関連 ---
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::get('/{item_id}', [PurchaseController::class, 'show'])->name('show'); // 商品購入ページ
        Route::post('/{item_id}/confirm', [PurchaseController::class, 'confirmPurchase'])->name('confirm'); // 購入確認
        Route::get('/complete', [PurchaseController::class, 'complete'])->name('complete'); // 購入完了ページ
        Route::get('/failed', [PurchaseController::class, 'failed'])->name('failed'); // 購入失敗ページ
        Route::get('/', [PurchaseController::class, 'index'])->name('index'); // 購入一覧ページ

        // --- 住所変更関連（購入の一部として扱う） ---
        Route::get('/address/{item_id}', [PurchaseController::class, 'changeAddress'])->name('changeAddress'); // 住所変更ページ
        Route::post('/address/{item_id}/update', [PurchaseController::class, 'updateAddress'])->name('updateAddress'); // 住所更新処理
    });

    // --- プロフィール関連 ---
    Route::prefix('mypage')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'showProfile'])->name('profile'); // プロフィール表示
        Route::get('/edit', [UserController::class, 'editProfile'])->name('editProfile'); // プロフィール編集画面
        Route::put('/update', [UserController::class, 'updateProfile'])->name('updateProfile'); // プロフィール更新処理
        Route::get('/history/buy', [UserController::class, 'purchaseHistory'])->name('purchaseHistory'); // 購入履歴
        Route::get('/history/sell', [UserController::class, 'listingHistory'])->name('listingHistory'); // 出品履歴
    });
});

// --- コメント投稿 ---
Route::post('item/{item_id}/comments', [CommentController::class, 'store'])->name('comments.store');

// ログインページを指定
Fortify::loginView(fn() => view('auth.login'));

// 認証関連
Route::get('auth/register', [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('auth/register', [AuthController::class, 'register'])->name('register');
Route::get('auth/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('auth/login', [AuthController::class, 'login'])->name('login');
Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');






Route::get('/mypage', [UserController::class, 'listingHistory'])->name('user.mypage');



// ユーザープロフィールページ
Route::get('/mypage', [UserController::class, 'showProfile'])->name('user.profile');
Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');



Route::middleware(['auth'])->group(function () {
    // プロフィール編集画面の表示 (GET)
    Route::get('/mypage/update', [UserController::class, 'editProfile'])->name('mypage.update');

    // プロフィール更新処理 (PUT)
    Route::put('/mypage/update', [UserController::class, 'updateProfile'])->name('mypage.update.submit');
});