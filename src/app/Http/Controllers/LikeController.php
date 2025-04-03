<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class LikeController extends Controller
{
    public function store(Item $item)
    {


        // ログインユーザー
        $user = Auth::user();

        // すでにいいねしている場合は何もしない
        if ($user->likes()->where('item_id', $item->id)->exists()) {
            return response()->json(['message' => 'すでにいいねしています'], 400);
        }

        // 新規いいね
        $user->likes()->create(['item_id' => $item->id]);

        return response()->json(['like_count' => $item->likeCount()]);
    }

    

    public function destroy(Item $item)
    {
        // ログインユーザー
        $user = Auth::user();

        // いいねを解除
        $user->likes()->where('item_id', $item->id)->delete();

        return response()->json(['like_count' => $item->likeCount()]);
    }

    public function toggleLike(Item $item)
    {
        $user = auth()->user();
        // すでにいいねをしているかどうか
        $like = $user->likes()->where('item_id', $item->id)->first();

        if ($like) {
            // いいねを削除
            $like->delete();
            $message = 'Success';
            $likeCount = $item->likes()->count(); // いいねの数を更新
            $isLiked = false; // いいねを削除したので、likedの状態はfalse
        } else {
            // いいねを追加
            $user->likes()->create(['item_id' => $item->id]);
            $message = 'Success';
            $likeCount = $item->likes()->count(); // いいねの数を更新
            $isLiked = true; // いいねを追加したので、likedの状態はtrue
        }

        return response()->json([
            'message' => $message,
            'likeCount' => $likeCount,
            'isLiked' => $isLiked, // 状態を返す
        ]);
    }

    public function likeItem(Request $request, $id)
    {
        // ユーザーがログインしていない場合はエラーレスポンスを返す
        if (!auth()->check()) {
            return response()->json(['message' => 'ログインが必要です'], 401);
        }

        // アイテムが存在するか確認
        $item = Item::find($id);
        if (!$item) {
            return response()->json(['message' => 'アイテムが見つかりません'], 404);
        }

        // いいね処理
        $user = auth()->user();

        // ユーザーがすでにそのアイテムに「いいね」をしているか確認
        $like = $user->likes()->where('item_id', $id)->first();

        if ($like) {
            // 既に「いいね」をしている場合、解除する
            $like->delete();
            $message = 'いいねを解除しました';
        } else {
            // 「いいね」を追加する
            $user->likes()->create(['item_id' => $id]);
            $message = 'いいねしました';
        }

        // アイテムの「いいね」数を更新して返す
        $likeCount = $item->likes()->count();

        return response()->json([
            'message' => $message,
            'likeCount' => $likeCount
        ]);
    }
}