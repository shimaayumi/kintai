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

    <!-- 🛠️ ページタイトル -->
    <div class="title-links">
        <h2>おすすめ</h2>

        <!-- 🛠️ マイリストリンク -->

        @if(Auth::check())
        <a href="{{ route('mylist') }}" class="btn">マイリスト</a>
        @else
        <a href="{{ route('login') }}" class="btn">マイリスト</a>
        @endif
    </div>



    <!-- 🛠️ 商品リスト表示 -->
    <div class="item-list">
        @forelse($items as $item)
        <div class="item">
            <a href="{{ route('items.show', ['item' => $item->id]) }}" class="item-link">
                <div class="item-image">
                    @if($item->images && $item->images->isNotEmpty()) {{-- 画像が存在する場合 --}}
                    <img src="{{ asset('storage/images/' . $item->images->first()->item_image) }}" alt="{{ $item->item_name }}">
                    @else {{-- 画像がない場合 --}}
                    <div class="no-image">商品画像</div>
                    @endif

                    {{-- SOLD 表示 --}}
                    @if ($item->purchases->isNotEmpty())
                    <div class="sold-label"></div>
                    @endif
                </div>

                <h3 class="item-name">{{ $item->item_name }}</h3>
            </a>
            
        </div>
        @empty
        <p>出品された商品はありません。</p>
        @endforelse
    </div>
</body>

</html>