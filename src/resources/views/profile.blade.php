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
    <div class="container">


        @php
        $user = auth()->user(); // ログインユーザーを取得
        @endphp

        <div class="profile-card">
            <div class="profile-image">
                @if($user->profile && $user->profile->profile_image)
                <img src="{{ asset('storage/' . $user->profile->profile_image) }}" alt="プロフィール画像">
                @else
                <img src="{{ asset('images/default_profile.png') }}">
                @endif
            </div>

            <div class="profile-info">
                <div class="profile-header">
                    <h2 class="profile-name">{{ $user->name }}</h2>
                    <a href="{{ route('user.editProfile') }}" class="btn btn-primary edit-button">プロフィールを編集</a>
                </div>
            </div>
        </div>

       

        <!-- タブの切り替え -->
        <div class="tabs">
            <a href="{{ route('user.profile', ['tab' => 'sell']) }}" class="btn {{ $tab === 'sell' ? 'active' : '' }}">出品した商品</a>
            <a href="{{ route('user.profile', ['tab' => 'purchased']) }}" class="btn {{ $tab === 'purchased' ? 'active' : '' }}">購入した商品</a>
        </div>

        <!-- タブに基づいて表示する内容を変更 -->
        <div class="product-list">
            @if($tab === 'sell')

            @forelse($listedItems as $item)

            <div class="product-item">
                <div class="product-image">
                    @if($item->image)
                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}">
                    @else
                    <img src="{{ asset('images/no_image_available.png') }}" alt="No Image">
                    @endif
                </div>
                <h3>{{ $item->name }}</h3>
                <p>{{ $item->description }}</p>
            </div>
            @empty
            <p>出品した商品はありません。</p>
            @endforelse

            @elseif($tab === 'purchased')
            <h2>購入した商品</h2>
            @forelse($purchasedItems as $item)
            <div class="product-item">
                <div class="product-image">
                    @if($item->image)
                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}">
                    @else
                    <img src="{{ asset('images/no_image_available.png') }}" alt="No Image">
                    @endif
                </div>
                <h3>{{ $item->name }}</h3>
                <p>{{ $item->description }}</p>
            </div>
            @empty
            <p>購入した商品はありません。</p>
            @endforelse
            @endif
            </ul>
        </div>
    </div>
</body>

</html>