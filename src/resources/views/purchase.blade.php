<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>fleamarket</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/purchase.css') }}" />
    <style>
        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
        }

        .left-column {
            width: 60%;
        }

        .right-column {
            width: 35%;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .img-fluid {
            max-width: 100%;
            height: auto;
        }

        .item img {
            width: 100%;
        }
    </style>
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
                <a href="{{ route('profile.show') }}" class="btn">マイページ</a>
                <a href="{{ route('items.create') }}" class="btn btn-outlet">出品</a>
                @else
                <a href="{{ route('auth.login') }}" class="btn">ログイン</a>
                <a href="{{ route('auth.register') }}" class="btn">会員登録</a>
                @endif
            </div>
        </div>
    </header>

    <div class="container">

        <!-- 左側の商品情報・配送先情報 -->
        <div class="left-column">
            <div class="row">
                <div class="col-md-4">
                    <div class="item">
                        <img src="{{ asset('storage/images/' . $item->item_image) }}" alt="{{ $item->name }}" class="img-fluid">
                        <p>商品名: {{ $item->name }}</p>
                        <p>{{ $item->description }}</p>
                        <p><strong>価格: </strong>{{ number_format($item->price) }}円</p>
                    </div>
                </div>
            </div>

            <!-- 支払い方法選択 -->
            <div class="form-group">
                <label for="payment_method">支払い方法</label>
                <select name="payment_method" id="payment_method" class="form-control" required onchange="displaySelectedPaymentMethod()">
                    <option value="credit_card">カード支払い</option>
                    <option value="bank_transfer">銀行振込</option>
                </select>
            </div>

            <!-- 配送先情報の表示 -->
            <div class="form-group">
                <label>配送先</label>
                <!-- 送付先住所変更ボタン -->
                <div class="form-group">
                    <a href="{{ route('address.change', $item->id) }}" class="btn btn-primary">変更する</a>
                </div>

                <p><strong>郵便番号:</strong> {{ !empty($user->address->postal_code) ? $user->address->postal_code : '未設定' }}</p>
                <p><strong>住所:</strong> {{ !empty($user->address->address) ? $user->address->address : '未設定' }}</p>
                <p><strong>建物:</strong> {{ !empty($user->address->building) ? $user->address->building : '未設定' }}</p>


            </div>
        </div>

        <!-- 右側の価格と購入ボタン -->
        <div class="right-column">
            <p><strong>商品代金 </strong>¥{{ number_format($item->price) }}</p>
            <div id="payment_method_display"></div>

            <!-- 購入確認フォーム -->
            <form action="{{ route('purchase.confirm', $item->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">購入する</button>
            </form>
        </div>

    </div>
</body>

<script>
    function displaySelectedPaymentMethod() {
        var selectedPaymentMethod = document.getElementById('payment_method').value;
        var displayArea = document.getElementById('payment_method_display');

        if (selectedPaymentMethod === 'credit_card') {
            displayArea.innerHTML = '選択された支払い方法: カード支払い';
        } else if (selectedPaymentMethod === 'bank_transfer') {
            displayArea.innerHTML = '選択された支払い方法: 銀行振込';
        }
    }

    // 送付先住所変更ボタンがクリックされたときに動作するようにする
    document.getElementById('change-address-btn').addEventListener('click', function(event) {
        event.preventDefault(); // リンクのデフォルト動作をキャンセル

        // 住所変更画面へ遷移
        window.location.href = "{{ route('address.change', $item->id) }}";
    });
</script>

</html>