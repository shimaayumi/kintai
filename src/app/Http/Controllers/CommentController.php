<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // 追加
class CommentController extends Controller
{
   

    public function store(Request $request, $item_id)
    {
        // リクエストデータをログに記録
        Log::info('コメントリクエスト: ', ['data' => $request->all()]);

        // バリデーション
        $validated = $request->validate([
            'comment_text' => 'required|string',
        ]);

        // コメントを保存
        Comment::create([
            'user_id' => Auth::id(),
            'item_id' => $item_id,
            'comment_text' => $validated['comment_text'],
        ]);

        // コメント保存後のログ
        Log::info('コメント保存完了: ', ['comment' => Comment::latest()->first()]);

        return redirect()->route('items.show', $item_id)->with('success', 'コメントを投稿しました！');
    }

    // コメント数をカウントするAPI
    public function count(Item $item)
    {
        return response()->json(['commentCount' => $item->comments()->count()]);
    }
}