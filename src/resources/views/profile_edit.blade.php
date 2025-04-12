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
                <a href="{{ route('mypage.show') }}" class="btn">マイページ</a>
                <a href="{{ route('sell') }}" class="btn btn-outlet">出品</a>
                @else
                <a href="{{ route('auth.login') }}" class="btn">ログイン</a>
                <a href="{{ route('auth.register') }}" class="btn">会員登録</a>
                @endif
            </div>
        </div>
    </header>

    <div class="container">
        <h1>プロフィール設定</h1>
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <form action="{{ route('mypage.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            

            <div class="form-group-row">
                <div class="profile-image">

                    <img id="preview" src="{{ asset('storage/profiles/' . ($user->profile->profile_image ?? 'default.png')) }}" >


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
                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $address ? $address->postal_code : '') }}" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="address">住所</label>
                <input type="text" name="address" id="address" value="{{ $address ? $address->address : '' }}" class="form-control" required>

            </div>




            <div class="form-group">
                <label for="building">建物名</label>
                <input type="text" name="building" id="building" value="{{ old('building', $address ? $address->building : '') }}" class="form-control">
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
                    // FileReaderが読み込んだ画像データをプレビュー用の画像タグに設定
                    document.getElementById("preview").src = e.target.result;
                };
                reader.readAsDataURL(file); // 画像ファイルをBase64に変換
            }
        });
    </script>
</body>

</html>