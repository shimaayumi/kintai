<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Item;
use App\Models\Category;
use App\Models\ItemImage;
use App\Models\Comment;
use App\Models\Purchase; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use App\Http\Requests\ExhibitionRequest;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    // --- 共通処理 ---


    private function getCategories(Request $request)
    {
        if ($request->category_id) {
            $categories = Category::whereIn('id', explode(',', $request->category_id))->get();
        } else {
            $categories = Category::all();
        }

        return $categories;
    }

    public function index(Request $request)
    {
        
        $keyword = $request->input('keyword');
        $tab = $request->input('page', 'all');

        if ($tab === 'mylist') {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            $user = Auth::user();
            $likedItems = $user->likes()->with('item.images')->get()->pluck('item')->unique('id');

            foreach ($likedItems as $item) {
                $item->sold_image = $item->sold_flag ? asset('images/sold.png') : asset('images/available.png');
            }

            // ✅ ビューに items を渡してる
            return view('index', [
                'items' => $likedItems,
                'categories' => $this->getCategories($request),
                'tab' => $tab,
                'keyword' => $keyword,
            ]);
        } else {
            // ✅ 通常表示用の items をここで定義！
            $query = Item::query();

            if ($keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            }

            $items = $query->with('images')->get();

            foreach ($items as $item) {
                $item->sold_image = $item->sold_flag ? asset('images/sold.png') : asset('images/available.png');
            }

            // ✅ ビューに渡す
            return view('index', [
                'items' => $items,
                'categories' => $this->getCategories($request),
                'tab' => $tab,
                'keyword' => $keyword,
            ]);
        }
    }

    // --- 商品詳細表示 ---

    public function show($id)
    {
        // 商品情報をIDで取得、存在しない場合は404エラーを返す
        $item = Item::findOrFail($id);

        // 商品に関連するコメントとユーザー情報を取得
        $comments = $item->comments()->with('user')->get();

        // 商品に紐づく画像を取得（item_imagesテーブルの画像データ）
      
        $images = $item->images ?? collect();

        // 現在ログインしているユーザーを取得
        $user = auth()->user();

        // JSONデータを配列に変換
       
        $categoryIds = json_decode($item->categories, true) ?? [];
      
        // カテゴリIDに基づいてカテゴリ情報を取得
        $categories = Category::whereIn('id', $categoryIds)->get();

        // 商品詳細ビューにデータを渡す
        return view('show', compact('item', 'comments', 'images', 'user', 'categories'));
    }

    public function create()
    {
        return view('create', [
            'categories' => Category::all(),
        ]);
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




    // --- マイリスト表示 ---
    public function showMyList(Request $request)
    {
        // ユーザーがログインしていなければ、ログインページへリダイレクト
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // ログインユーザーを取得
        $user = Auth::user();

        // ユーザーの「いいね」した商品を取得（likes を使って、item と item_images を同時にロード）
        $likedItems = $user->likes()->with('item.images')->get()->pluck('item')->unique('id'); // 重複を排除

        // フォームからの送信データを取得（例: ソートの選択）
        $sortBy = $request->input('sort_by', 'created_at'); // デフォルトは 'created_at' でソート

        // 商品の並べ替え（例: created_at または price で並べ替え）
        $likedItems = $likedItems->sortByDesc($sortBy);

        // 購入された商品は "Sold" と表示
        foreach ($likedItems as $item) {
            if ($item->sold_flag) {
                $item->sold = 'Sold';
            } else {
                $item->sold = null;  // "Sold" がない場合は null
            }
        }

        // URL パラメータでページが指定されている場合、そのページをビュー名として使う
        $page = $request->query('page', 'mylist'); // デフォルトは 'mylist'

        return view('index', [
            'items' => $likedItems, // ← likedItems を items として渡す
            'categories' => $this->getCategories($request), // 他と合わせるならこれも追加
            'tab' => $page,
            'keyword' => '', // キーワード検索していないので空でOK
        ]);
    }
    



    // --- 商品出品画面表示 ---
    public function store(ExhibitionRequest $request)
    {
        // リクエストのバリデーション
        $validated = $request->validate([
           
        ]);

        DB::transaction(function () use ($request) {
            // 商品を作成
            $item = new Item();
            $item->user_id = Auth::id();
            $item->item_name = $request->item_name;
            $item->price = $request->price;
            $item->description = $request->description;
            $item->brand_name = $request->brand_name;
            $item->sold_flag = 0; // 出品時は未販売
            $item->categories = json_encode($request->category_id); // 複数カテゴリ選択の場合はJSONで保存
            $item->save();

            // 画像保存
            if ($request->hasFile('item_image')) {
                $image = $request->file('item_image');

                // 元のファイル名を取得
                $originalFileName = $image->getClientOriginalName();

                // images/ディレクトリ内に元のファイル名で保存
                $path = $image->storeAs('images', $originalFileName, 'public');

                // 画像情報をデータベースに保存
                ItemImage::create([
                    'item_id' => $item->id,
                    'item_image' => $originalFileName, // 元のファイル名を保存（images/なし）
                ]);
            }
        });

        return redirect()->route('sell')->with('success', '商品が出品されました！');
    }



    // --- 商品購入画面表示 ---
    public function purchaseItem(Request $request, $itemId)
    {
        $user = Auth::user();  // ユーザー取得
        $item = Item::findOrFail($itemId);  // 商品取得

        DB::transaction(function () use ($user, $item, $request) {
            // 購入の作成
            $purchase = new Purchase();
            $purchase->user_id = $user->id;
            $purchase->item_id = $item->id;
            $purchase->address_id = $request->address_id;
            $purchase->price = $item->price;
            $purchase->payment_method = $request->payment_method;
            $purchase->status = 'completed';
            $purchase->save();

            // 購入後に sold_flag を更新
            $item->sold_flag = 1;
            $item->save();
        });

        return redirect()->route('purchases.index')->with('message', '購入が完了しました');
    }
}