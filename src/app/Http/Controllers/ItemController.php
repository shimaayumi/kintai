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
                    return view('index', [
                        'items' => collect(),
                        'categories' => $this->getCategories($request),
                        'tab' => $tab,
                        'keyword' => $keyword,
                    ]);
                }

                $user = Auth::user();



            $likedItems = $user->likes()
                ->with('item.images')
                ->get()
                ->pluck('item')
                ->filter(fn($item) => !is_null($item)) // null除外
                ->groupBy('id') // item_id でグルーピング
                ->map(fn($group) => $group->first()) // 最初の1つだけ使う
                ->values(); // 再インデックス化

            // 🔽🔽🔽 キーワードでフィルタ
            if ($keyword) {
                $likedItems = $likedItems->filter(function ($item) use ($keyword) {
                    return mb_stripos($item->item_name, $keyword) !== false;
                })->values();
            }

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
              
                $query = Item::query();

                if ($keyword) {
                    $query->where('item_name', 'like', "%{$keyword}%");
                }
            
                if (Auth::check()) {
                    $query->where('user_id', '!=', Auth::id());
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



    }