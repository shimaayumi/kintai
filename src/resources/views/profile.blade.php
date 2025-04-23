<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>商品一覧</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}" />
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
            <form action="{{ route('index') }}" method="GET" class="search-form">
                <input type="text" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="なにをお探しですか？" />
                <input type="hidden" name="page" value="{{ request('page', 'all') }}" />
            </form>

            <!-- 🛠️ ヘッダーメニュー -->

            <a href="{{ route('logout') }}" class="btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            <a href="{{ route('mypage') }}" class="btn">マイページ</a>
            <a href="{{ route('sell') }}" class="btn btn-outlet">
                <span class="btn-text">出品</span>
            </a>


        </div>
    </header>
    <div class="container">



        <div class="profile-card">
            <div class="profile-image">
                @if($user->profile && $user->profile->profile_image)
                <img id="preview" src="{{ asset('storage/profiles/' . ($user->profile->profile_image ?? 'default.png')) }}">

                @else
                <img src="{{ asset('images/default_profile.png') }}">
                @endif
            </div>

            <div class="profile-info">
                <div class="profile-header">
                    <h2 class="profile-name">{{ $user->name }}</h2>
                    <a href="{{ route('edit') }}" class="btn btn-primary edit-button">プロフィールを編集</a>
                </div>
            </div>
        </div>



        <!-- タブの切り替え -->
        <div class="tabs">
            <a href="{{ url('/mypage?page=sell') }}" class="btn {{ ($page ?? '') === 'sell' ? 'active' : '' }}">出品した商品</a>

            <a href="{{ url('/mypage?page=buy') }}" class="btn {{ ($page ?? '') === 'buy' ? 'active' : '' }}">購入した商品</a>
        </div>

        <div class="item-list">
            @if(($page ?? '') === 'sell')
            @forelse(($page === 'sell' ? $sellItems : $purchasedItems) as $item)
            <div class="item">
                <a href="{{ route('item.show', ['item_id' => $item->id]) }}" class="item-link">
                    <div class="item-image">
                        @php
                        $imagePath = optional($item->images->first())->item_image;
                        @endphp

                        @if($imagePath && Storage::exists('public/images/' . $imagePath))
                        <img src="{{ asset('storage/images/' . $imagePath) }}" alt="{{ $item->item_name }}">
                        @else
                        <span>商品画像</span>
                        @endif
                    </div>
                    <h3 class="item-name">{{ $item->item_name }}</h3>
                </a>
            </div>
            @empty
            <p>出品した商品がありません。</p>
            @endforelse
            @elseif(($page ?? '') === 'buy')
            @forelse($purchasedItems ?? [] as $item)
            <div class="item">
                <a href="{{ route('item.show', ['item_id' => $item->id]) }}" class="item-link">
                    <div class="item-image">
                        @php
                        $imagePath = optional($item->images->first())->item_image;
                        @endphp

                        @if($imagePath && Storage::exists('public/images/' . $imagePath))
                        <img src="{{ asset('storage/images/' . $imagePath) }}" alt="{{ $item->item_name }}">
                        @else
                        <span>商品画像</span>
                        @endif

                        @if ($item->purchases->isNotEmpty())
                        <div class="sold-label"></div>
                        @endif
                    </div>
                    <h3 class="item-name">{{ $item->item_name }}</h3>
                </a>
            </div>

            @empty
            <p>購入した商品はありません。</p>
            @endforelse
            @endif
        </div>

</body>

</html>