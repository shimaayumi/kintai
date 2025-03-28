<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\ItemImage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ExhibitionRequest;

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
            $query->where('items_name', 'LIKE', '%' . $keyword . '%');
        }

        // ❤️ マイリスト or すべての商品
        if ($tab === 'mylist') {
            if (Auth::check()) {
                $items = Auth::user()->likes()->with('item')->get()->pluck('item');
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
        return view('create', ['categories' => $this->getCategories()]);
    }

    // --- 商品登録処理 ---
    public function store(ExhibitionRequest $request)
    {
        // 商品登録処理
        $item = Item::create([
            'user_id' => Auth::id(),
            'items_name' => $request->items_name,
            'brand_name' => $request->brand_name,
            'description' => $request->description,
            'price' => $request->price,
            'category_id' => $request->category_id,
            'sold_flag' => false,
        ]);

        // 画像がアップロードされている場合
        if ($request->hasFile('product_images')) {
            foreach ($request->file('product_images') as $image) {
                // 画像をストレージに保存
                $path = $image->store('product_images', 'public'); // product_imagesに保存

                // ItemImageとして保存
                ItemImage::create([
                    'item_id' => $item->id,  // 関連するアイテムIDを指定
                    'image_url' => str_replace('public/', 'storage/', $path) // 'public' -> 'storage'に変換
                ]);
            }
        }

        return redirect()->route('items.index')->with('success', '商品を出品しました！');
    }

    // --- 商品詳細表示 ---
    public function show($id)
    {
        $item = Item::findOrFail($id);
        return view('show', compact('item'));
    }

    // --- マイリスト取得・表示 ---
    public function myList()
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('auth.login')->with('message', 'ログインしてください');
        }

        $likedItems = $user->likes()->with('item')->get()->pluck('item');
        return view('mylist', compact('likedItems'));
    }
}
