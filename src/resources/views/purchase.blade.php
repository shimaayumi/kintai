<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>fleamarket</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/purchase.css') }}" />
</head>

<body>
    <header>
        <header class="header">
            <div class="header__inner">
                <a class="header__logo" href="/">
                    <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" />
                </a>
            </div>
        </header>
    </header>

    <div class="container">
        <h1>商品購入</h1>

        <!-- 商品情報表示 -->
        <div class="row">
            <div class="col-md-4">
                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="img-fluid">
            </div>
            <div class="col-md-8">
                <h3>{{ $item->name }}</h3>
                <p>{{ $item->description }}</p>
                <p><strong>価格: </strong>{{ number_format($item->price) }}円</p>
                <p><strong>現在の住所: </strong>{{ Auth::user()->profile->address }}</p>
            </div>
        </div>

        <!-- 購入確認フォーム -->
        <form action="{{ route('purchase.confirm', $item->id) }}" method="POST">
            @csrf
            <!-- 支払い方法選択 -->
            <div class="form-group">
                <label for="payment_method">支払い方法</label>
                <select name="payment_method" id="payment_method" class="form-control" required>
                    <option value="convenience_store">コンビニ支払い</option>
                    <option value="credit_card">カード支払い</option>
                </select>
            </div>

            <!-- 送付先住所変更ボタン -->
            <div class="form-group">
                <a href="{{ route('purchase.changeAddress', $item->id) }}" class="btn btn-primary">配送先住所変更</a>
            </div>


            @if(Auth::user()->profile && Auth::user()->profile->address)
            <p><strong>現在の住所: </strong>{{ Auth::user()->profile->address }}</p>
            @else
            <p><strong>現在の住所: </strong>住所が登録されていません。</p>
            @endif

            <!-- 購入確認ボタン -->
            <button type="submit" class="btn btn-success">購入する</button>
        </form>
    </div>
</body>

</html>