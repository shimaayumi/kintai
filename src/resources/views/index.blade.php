<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>おすすめ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/index.css') }}" />
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
                <a href="{{ route('sell') }}" class="btn btn-outlet">出品</a>
                @else
                <!-- 未ログイン時のメニュー -->
                <a href="{{ route('auth.login') }}" class="btn">ログイン</a>
                <a href="{{ route('auth.register') }}" class="btn">会員登録</a>
                @endif
            </div>
        </div>
    </header>

    <!-- 🛠️ ページタイトル -->
    <div class="title-links">
        <h2>おすすめ</h2>

        <!-- 🛠️ マイリストリンク -->
        @if(Auth::check())
        <a href="{{ url('/mypage?tab=sell') }}" class="btn">マイリスト</a>
        @else
        <a href="{{ route('auth.login') }}" class="btn">ログインしてマイリストを見る</a>
        @endif
    </div>

    <!-- 🛠️ 商品リスト表示 -->
    <div class="container">
        <div class="product-list">
            @forelse($items as $item)
            <div class="product-item {{ !$item->image_url ? 'no-image' : '' }}">
                <a href="{{ route('items.show', $item->id) }}" class="product-link">
                    <div class="product-image">
                        @if($item->image_url)
                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" />
                        @else
                        <span>商品画像</span>
                        @endif
                    </div>
                    <h3>{{ $item->name }}</h3>
                    <p>{{ $item->description }}</p>
                </a>
            </div>
            @empty
            <p>{{ request('page') === 'mylist' ? 'マイリストに商品がありません。' : '表示できる商品がありません。' }}</p>
            @endforelse
        </div>
    </div>
</body>

</html>