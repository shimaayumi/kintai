<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール編集</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile_edit.css') }}">
</head>

<body>
    <header>
        <div class="header">
            <div class="header__inner">
                <a class="header__logo" href="/">
                    <img src="{{ asset('images/logo.svg') }}" alt="ロゴ">
                </a>
            </div>
            <form action="{{ route('items.index') }}" method="GET" class="search-form">
                <input type="text" name="keyword" value="{{ old('keyword', request('keyword')) }}" placeholder="なにをお探しですか？">
                <input type="hidden" name="page" value="{{ request('page', 'all') }}">
            </form>
            <div class="header__menu">
                @if(Auth::check())
                <a href="{{ route('logout') }}" class="btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <a href="{{ route('user.profile') }}" class="btn">マイページ</a>
                <a href="{{ route('items.create') }}" class="btn btn-outlet">出品</a>
                @else
                <a href="{{ route('auth.login') }}" class="btn">ログイン</a>
                <a href="{{ route('auth.register') }}" class="btn">会員登録</a>
                @endif
            </div>
        </div>
    </header>

    <div class="container">
        <h1>プロフィール設定</h1>
        <form action="{{ route('user.updateProfile') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group-row">
                <div class="profile-image">
                    <img id="preview" src="default-avatar.png">
                    <img src="{{ asset('storage/profiles/' . $user->profile_image) }}" alt="プロフィール画像">
                </div>
                <div class="file-input">
                    <label for="profile_image" class="btn">ファイルを選択</label>
                    <input type="file" name="profile_image" id="profile_image" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="name">ユーザー名</label>
                <input type="text" name="name" id="name" value="{{ $user->name }}" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="postal_code">郵便番号</label>
                <input type="text" name="postal_code" id="postal_code" value="{{ $address ? $address->postal_code : '' }}" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="address">住所</label>
                <input type="text" name="address" id="address" value="{{ $address ? $address->address : '' }}" class="form-control" required>
                @if($address)
    <p>{{ $address->address }}</p>
@else
    <p>住所情報はまだ登録されていません。</p>
@endif
            </div>
            

            <div class="form-group">
                <label for="building">建物名</label>
                <input type="text" name="building" id="building" value="{{ $address ? $address->building : '' }}" class="form-control">
            </div>

            <button type="submit" class="btn btn-success">更新する</button>
        </form>
    </div>

    <script>
        document.getElementById("profile_image").addEventListener("change", function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById("preview").src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>