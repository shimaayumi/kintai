<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>おすすめ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/create.css') }}" />
</head>

<body>
    <header>
        <div class="header">
            <div class="header__inner">
                <a class="header__logo" href="/">
                    <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" />
                </a>
            </div>
            <!-- 🛠️ 検索フォーム -->
            <form action="{{ route('items.index') }}" method="GET" class="search-form">
                <input type="text" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="なにをお探しですか？" />
                <input type="hidden" name="page" value="{{ request('page', 'all') }}" />
            </form>
            <!-- 🛠️ ヘッダーメニュー -->
            <div class="header__menu">
                @if(Auth::check())
                <!-- ログイン時のメニュー -->
                <a href="{{ route('logout') }}" class="btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <a href="{{ route('mypage.show') }}" class="btn">マイページ</a>
                <a href="{{ route('sell') }}" class="btn btn-outlet">出品</a>
                @else
                <!-- 未ログイン時のメニュー -->
                <a href="{{ route('auth.login') }}" class="btn">ログイン</a>
                <a href="{{ route('auth.register') }}" class="btn">会員登録</a>
                @endif
            </div>
        </div>
    </header>
    <div class="container">
        <h1>商品の出品</h1>

        <!-- バリデーションエラーの表示 -->
        @if ($errors->any())
        <div>
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
            @csrf


            <div class="form-group-row">

                <div class="item-image">
                    <img id="preview"
                        src=""
                        alt="画像プレビュー"
                        style="max-width: 150px; margin-top: 10px; display: none;">

                    <div class="no-image-text">商品画像</div>

                    <!-- 画像アップロード用の入力フィールド (プレビューと出品用を統一) -->
                    <div class="file-input">
                        <input type="file" name="item_images[]" id="item_image" class="form-control" accept="image/*" multiple>
                    </div>
                </div>





                <!-- 商品の詳細 -->
                <h2>商品の詳細</h2>

                <!-- カテゴリ選択 -->
                <div class="form-group">
                    <label for="category_id">カテゴリ</label>
                    <div id="category-buttons">
                        @foreach(['ファッション', '家電', 'インテリア', 'レディース', 'メンズ', 'コスメ', '本', 'ゲーム', 'スポーツ', 'キッチン', 'ハンドメイド', 'アクセサリー', 'おもちゃ', 'ベビー・キッズ'] as $index => $category)
                        <button type="button" class="category-btn" data-category-id="{{ $index + 1 }}">
                            {{ $category }}
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="category_id" id="category-id" value="{{ old('category_id') }}">
                    @error('category_id')
                    <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 商品の状態 -->
                <div class="form-group">
                    <label for="status">商品状態</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="" disabled selected>選択してください</option>
                        <option value="new" {{ old('status') == 'new' ? 'selected' : '' }}>新品</option>
                        <option value="used" {{ old('status') == 'used' ? 'selected' : '' }}>中古</option>
                    </select>
                    @error('status')
                    <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 商品名 -->
                <label for="items_name">商品名</label>
                <input type="text" class="form-control" id="items_name" name="items_name" value="{{ old('items_name') }}" required>
                @error('items_name')
                <div class="error-message">{{ $message }}</div>
                @enderror

                <!-- ブランド名 -->
                <label for="brand_name">ブランド名</label>
                <input type="text" class="form-control" id="brand_name" name="brand_name" value="{{ old('brand_name') }}" required>
                @error('brand_name')
                <div class="error-message">{{ $message }}</div>
                @enderror

                <!-- 商品説明 -->
                <div class="form-group">
                    <label for="description">商品の説明</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                    @error('description')
                    <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <!-- 価格 -->
                <div class="input-wrapper">
                    <label for="price">商品価格</label>
                    <span class="currency-symbol">¥</span>
                    <input type="number" class="form-control price-input" id="price" name="price" value="{{ old('price') }}" required>
                    @error('price')
                    <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">出品する</button>
        </form>
    </div>

    <script>
        document.getElementById("item_image").addEventListener("change", function(event) {
            let file = event.target.files[0]; // 最初の画像のみプレビュー
            let preview = document.getElementById("preview");
            let noImageText = document.querySelector(".no-image-text");

            if (file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = "block";
                    noImageText.style.display = "none"; // 「商品画像」のテキストを非表示
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = "";
                preview.style.display = "none";
                noImageText.style.display = "block"; // 画像が選択されていない場合、テキストを表示
            }
        });


        // カテゴリボタンのクリックイベント処理
        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', function() {
                // すべてのボタンの選択状態をリセット
                document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('selected'));

                // クリックされたボタンに「選択済み」のクラスを追加
                this.classList.add('selected');

                // 隠し入力フィールドに選択されたカテゴリIDをセット
                const categoryId = this.getAttribute('data-category-id');
                document.getElementById('category-id').value = categoryId;
            });
        });
    </script>
</body>

</html>