<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>商品詳細 - {{ $item->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/show.css') }}" />
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
                <a href="{{ route('user.profile') }}" class="btn">マイページ</a>
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
        <!-- 左側 商品画像 -->
        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="product-image" />

        <!-- 右側 商品情報 -->
        <div class="product-info">
            <h1>{{ $item->name }}</h1>
            <p>{{ $item->description }}</p>
            <div class="price">¥{{ number_format($item->price) }}(税込)</div>
            <a href="#" class="btn">購入手続きへ</a>

            <!-- いいね・コメントセクション -->
            <div class="like-icon {{ $item->is_liked ? 'liked' : '' }}">☆</div>
            <h3>商品説明</h3>
            <div class="comments-section">
                @foreach($item->comments as $comment)
                <div class="comment-item">
                    <span class="comment-user">{{ $comment->user->name }}</span>
                    <p class="comment-text">{{ $comment->content }}</p>
                </div>
                @endforeach
            </div>
            <form class="comment-form" method="POST" action="/comment">
                @csrf
                <p>商品へのコメント</p>
                <textarea name="content" ></textarea>
                <button type="submit">コメントを送信する</button>
            </form>
        </div>
    </div>
</body>

</html>