<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Item;
use App\Models\Category;
use App\Models\ItemImage;
use App\Models\Comment;
use App\Models\Purchase; // 追加
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // 追加
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

    // --- 商品一覧表示 ---
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $tab = $request->input('page', 'all');

        // 商品データを取得
        $query = Item::query();

        // ログインユーザーが出品した商品を除外
        $query->where('user_id', '!=', auth()->id());

        // 🔍 検索機能
        if ($keyword) {
            $query->where('item_name', 'LIKE', '%' . $keyword . '%');
        }

        // すべての商品を表示
        if ($tab === 'all') {
            $items = $query->with('images')->orderBy('created_at', 'desc')->get();
        } else {
            $items = []; // 他のタブがあれば追加の処理を行う
        }

        // 商品ごとの状態を判定し、表示用に画像パスを追加
        foreach ($items as $item) {
            if ($item->sold_flag) {
                $item->sold_image = asset('images/sold.png'); // 売れた商品には「sold.png」を表示
            } else {
                $item->sold_image = asset('images/available.png'); // 売れていない商品には「available.png」を表示
            }
        }

        // ビューにデータを渡す
        return view('index', [
            'items' => $items,
            'categories' => $this->getCategories($request), // 修正：$requestを渡す
            'tab' => $tab,
            'keyword' => $keyword,
        ]);
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
        

        // 購入された商品は "Sold" と表示
        foreach ($likedItems as $item) {
            if ($item->sold_flag) {
                $item->sold = 'Sold';
            } else {
                $item->sold = null;  // "Sold" がない場合は null
            }
        }

        // URL パラメータでページが指定されている場合、そのページをビュー名として使う
        $page = $request->query('page', 'mylist'); // デフォルトは 'mylist.index'

        // ビューに渡す変数名を$itemsに統一
        return view($page, compact('likedItems'));
    }


    



    // --- 商品出品画面表示 ---
    public function store(Request $request)
    {
        // リクエストのバリデーション
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'category_id' => 'required|array|min:1', // 少なくとも1つのカテゴリが選択されているか
            'category_id.*' => 'exists:categories,id', // 存在するカテゴリIDであることを確認
            'description' => 'nullable|string',
            'brand_name' => 'nullable|string|max:255',

            'item_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 画像のバリデーション（1枚のみ）
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