<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>fleamarket</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/index.css') }}" />
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="/">
                <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" />
            </a>
        </div>
    </header>

    <main>
        <div class="container mx-auto px-4">
            <!-- 検索フォーム -->
            <form action="{{ route('index') }}" method="GET" class="mb-4">
                <input
                    type="text"
                    name="keyword"
                    value="{{ request('keyword') }}"
                    placeholder="商品名を検索"
                    class="border p-2 w-full" />
                <button type="submit" class="mt-2 bg-blue-500 text-white p-2 w-full">検索</button>
            </form>

            <!-- タブ切り替え -->
            <div class="mb-4 flex justify-center space-x-4">
                <a href="{{ route('index') }}" class="p-2 {{ request('tab') == 'mylist' ? '' : 'bg-blue-500 text-white' }}">商品一覧</a>
                @auth
                <a href="{{ route('items.mylist') }}" class="p-2 {{ request('tab') == 'mylist' ? 'bg-blue-500 text-white' : '' }}">マイリスト</a>
                @endauth
            </div>

            <!-- 商品一覧 -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @forelse ($items as $item)
                @if (Auth::id() !== $item->user_id)
                <div class="border p-2 relative">
                    <!-- 商品画像 -->
                    <img id="preview" src="{{ asset('storage/images/' . $item->image_url) }}" alt="画像プレビュー" class="w-full h-auto object-cover rounded-md" style="max-width: 100%;">

                    <!-- 商品名 -->
                    <h3 class="mt-2 text-lg font-bold">{{ $item->name }}</h3>
            

           

            <!-- SOLD表示 -->
            @if ($item->sold_flag)
            <div class="absolute top-0 left-0 bg-red-500 text-white px-2 py-1">Sold</div>
            @endif

            <!-- 商品詳細へのリンク -->
            <a href="{{ route('items.show', $item->id) }}" class="mt-2 block bg-blue-500 text-white p-2 text-center">詳細を見る</a>
        </div>
        @endif
        @empty
        <p class="col-span-2 md:col-span-4 text-center text-gray-500">商品が見つかりませんでした。</p>
        @endforelse
        </div>
        </div>
    </main>
</body>

</html>