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

        // いいね数を返す
        return response()->json(['like_count' => $item->likeCount()]);
    }

    public function destroy(Item $item)
    {
        // ログインユーザー
        $user = Auth::user();

        // いいねを解除
        $user->likes()->where('item_id', $item->id)->delete();

        // いいね数を返す
        return response()->json(['like_count' => $item->likes->count()]);
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
            $likeCount = $item->likeCount(); // いいねの数を更新
            $isLiked = false; // いいねを削除したので、likedの状態はfalse
        } else {
            // いいねを追加
            $user->likes()->create(['item_id' => $item->id]);
            $message = 'Success';
            $likeCount = $item->likeCount(); // いいねの数を更新
            $isLiked = true; // いいねを追加したので、likedの状態はtrue
        }

        return response()->json([
            'message' => $message,
            'likeCount' => $likeCount,
            'isLiked' => $isLiked, // 状態を返す
        ]);
    }
    
}