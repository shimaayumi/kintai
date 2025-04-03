<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>商品詳細 - {{ $item->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/show.css') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                @csrf
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
                <a href="{{ route('profile.show') }}" class="btn">マイページ</a>
                <a href="{{ route('items.create') }}" class="btn btn-outlet">出品</a>
                @else
                <!-- 未ログイン時のメニュー -->
                <a href="{{ route('auth.login') }}" class="btn">ログイン</a>
                <a href="{{ route('auth.register') }}" class="btn">会員登録</a>
                @endif
            </div>
        </div>
    </header>

    <!-- 商品詳細 -->
    <div class="product-detail">
        <!-- 商品詳細ページ -->
        <div class="item-details">
            <h1>{{ $item->items_name }}</h1>
            <p>{{ $item->description }}</p>

            <h2>商品画像</h2>
            <div class="item-images">
                @foreach($images as $image)
                <img src="{{ asset('storage/images/' . $image->item_image) }}" alt="{{ $item->items_name }}" />
                @endforeach
                @if($images->isEmpty())
                <span>商品画像</span>
                @endif
            </div>

            <!-- 右側 商品情報 -->
            <div class="product-info">
                <h1>{{ $item->items_name }}</h1>
                <p class="brand-name">{{ $item->brand_name }}</p>


                <div class="price">
                    <span class="currency">¥</span>{{ number_format($item->price) }}
                    <span class="tax-included">(税込)</span>
                </div>





                <!-- いいね＆コメントセクション -->
                <div class="interaction-section">
                    <!-- いいねアイコン -->
                    <div class="like-section">
                        <span id="like-icon-{{ $item->id }}" class="like-icon" onclick="toggleLike({{ $item->id }})">
                            @auth
                            {{ auth()->user()->likes()->where('item_id', $item->id)->exists() ? '★' : '☆' }}
                            @else
                            ☆
                            @endauth
                        </span>
                        <span id="like-count-{{ $item->id }}" class="like-count">{{ $item->likes()->count() }}</span>
                    </div>

                    <!-- コメントアイコン -->
                    <div class="comment-section">
                        <span id="comment-icon-{{ $item->id }}" class="comment-icon">💬</span>
                        <span id="comment-count-{{ $item->id }}" class="icon-comment-count">{{ $item->comments()->count() }}</span>
                    </div>
                </div>




                <form action="{{ route('purchase.show', ['id' => $item->id]) }}" method="GET">
                    @csrf
                    <button type="submit" class="btn btn-primary">購入手続きへ</button>
                </form>


                <h3>商品説明</h3>
                <p class="description">{{ $item->description }}</p>

                <h3>商品の情報</h3>
                <!-- カテゴリー表示 -->
                <div class="item-container">
                    <div class="category">カテゴリー <span>{{ $item->category->category_name }}</span></div>
                    <div class="item-status">商品の状態 <span>{{ $item->status }}</span></div>
                </div>






                <div class="comments-section">
                    <h3>コメント <span class="comment-count"> ({{ count($item->comments) }})</span></h3>



                    <div class="seller-profile">
                        @auth
                        <p>ログインユーザー: {{ auth()->user()->name }}</p>
                        @endauth
                        @if($user->address && $user->profile->profile_image)
                        <img id="preview" src="{{ asset('storage/profiles/' . ($item->user->profile->profile_image ?? 'default.png')) }}" alt="画像プレビュー" style="max-width: 150px; margin-top: 10px;">

                        @else
                        <img src="{{ asset('images/default_profile.png') }}">
                        @endif
                        <p class="seller-name"><strong>{{ $item->user->name }}</strong></p>
                    </div>


                    @foreach($item->comments as $comment)
                    <div class="comment-item">
                        <span class="comment-user">{{ $comment->user->name }}</span>
                        <p class="comment-text">{{ $comment->comment_text }}</p>
                    </div>
                    @endforeach
                </div>
                @foreach($comments as $comment)
                <div class="comment">
                    <strong>{{ $comment->user->name }}</strong>
                    <p>{{ $comment->comment_text }}</p> <!-- 'content' を 'comment_text' に変更 -->
                </div>
                @endforeach



                <form action="{{ route('items.comment', $item->id) }}" method="POST">
                    @csrf
                    <h3>商品へのコメント</h3>
                    <textarea name="comment_text" required></textarea> <!-- name属性を確認 -->
                    <button type="submit">コメントを送信する</button>





                </form>
            </div>
        </div>
        <script>
            function toggleLike(itemId) {
                fetch(`/toggle-like/${itemId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Server Response:", data); // ここでサーバーのレスポンスを確認
                        if (data.message === 'Success') {
                            const likeButton = document.getElementById(`like-btn-${itemId}`);
                            const likeIcon = document.getElementById(`like-icon-${itemId}`);
                            const likeCount = document.getElementById(`like-count-${itemId}`);

                            // いいねアイコンの状態を切り替え
                            if (data.isLiked) {
                                likeIcon.innerText = '★'; // いいね状態
                            } else {
                                likeIcon.innerText = '☆'; // いいねしていない状態
                            }


                            // いいね数の更新
                            likeCount.innerText = data.likeCount;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function updateCommentCount(itemId) {
                fetch(`/item/${itemId}/comments/count`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById(`comment-count-${itemId}`).innerText = data.commentCount;
                    })
                    .catch(error => console.error('Error:', error));
            }
            // 画像プレビュー用のJavaScript
            function previewImage(event) {
                const preview = document.getElementById('preview');
                const file = event.target.files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                };

                if (file) {
                    reader.readAsDataURL(file);
                }
            }
        </script>
</body>

</html>