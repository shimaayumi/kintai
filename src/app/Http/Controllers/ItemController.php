<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\ItemImage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ExhibitionRequest;
use App\Models\Comment;

class ItemController extends Controller
{
    // --- 共通処理 ---
    private function getCategories()
    {
        return Category::all();
    }

    // --- 商品一覧表示 ---
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $tab = $request->input('page', 'all');

        $query = Item::where('sold_flag', false);

        // 🔍 検索機能
        if ($keyword) {
            $query->where('name', 'LIKE', '%' . $keyword . '%');
        }

        //  マイリスト or すべての商品
        if ($tab === 'mylist') {
            if (Auth::check()) {
                $items = Auth::user()->likes()->with(['item'])->get()->pluck('item');
            } else {
                return redirect()->route('auth.login')->with('message', 'マイリストを表示するにはログインが必要です');
            }
        } else {
            $items = $query->where('user_id', '!=', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // ビューにデータを渡す
        return view('index', [
            'items' => $items,
            'categories' => $this->getCategories(),
            'tab' => $tab,
            'keyword' => $keyword,
        ]);
    }

    // --- 商品出品画面表示 ---
    public function create()
    {

        $item = new Item();
        return view('create', [
            'categories' => $this->getCategories(),
            'item' => $item // 追加: 新しい商品インスタンスをビューに渡す
        ]);
    }

    // --- 商品を保存 ---
    public function store(ExhibitionRequest $request)
    {
        // バリデーション（すでに `ExhibitionRequest` でチェック済み）

        // 商品の情報を保存
        $item = Item::create([
            'user_id' => Auth::id(),
            'items_name' => $request->items_name,
            'brand_name' => $request->brand_name,
            'description' => $request->description,
            'price' => $request->price,
            'category_id' => $request->category_id,
            'sold_flag' => false,
        ]);

        // 商品画像を保存
        if ($request->hasFile('item_images')) {
            foreach ($request->file('item_images') as $file) {
                // ファイル名を生成
                $fileName = time() . '_' . $file->getClientOriginalName();

                // 画像を storage/app/public/images に保存
                $filePath = $file->storeAs('public/images', $fileName);

                // データベースに保存
                ItemImage::create([
                    'item_id' => $item->id,
                    'item_image' =>  $fileName, // フロントエンド用のパス
                ]);
            }
        }

        // 成功メッセージと共に商品一覧ページにリダイレクト
        return redirect()->route('items.index')->with('success', '商品が正常に登録されました');
    }

    // --- 商品詳細表示 ---
    public function show($id)
    {
        $item = Item::findOrFail($id);
        $comments = $item->comments()->with('user')->get(); // 商品に関連するコメントとユーザー情報を取得
        $images = $item->images; // 商品に紐づく画像を取得
        $user = auth()->user(); // 現在ログインしているユーザーを取得
       
        return view('show', compact('item', 'comments', 'images'));
    }

   

    // --- マイリスト取得・表示 ---
    public function myList()
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('auth.login')->with('message', 'ログインしてください');
        }

        $likedItems = $user->likes()->with(['item'])->get()->pluck('item');
        
        return redirect()->route('mylist');
    }

    // --- コメント保存処理 ---
    public function storeComment(Request $request, $itemId)
    {
        // コメントのバリデーション
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        // コメントの保存
        Comment::create([
            'user_id' => auth()->id(),
            'item_id' => $itemId,
            'comment_text' => $request->input('content'),
        ]);

        // 商品詳細ページへリダイレクト
        return redirect()->route('items.show', ['id' => $itemId])->with('success', 'コメントが送信されました！');
    }

  
}
