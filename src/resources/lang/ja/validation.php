<?php

return [

    'accepted' => ':attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeには、:date以降の日付を指定してください。',
    'alpha' => ':attributeにはアルファベッドのみ使用できます。',
    'alpha_num' => ':attributeには英数字を使用してください。',
    'array' => ':attributeには配列を指定してください。',
    'before' => ':attributeには、:date以前の日付を指定してください。',
    'between' => [
        'numeric' => ':attributeには、:minから:maxまでの数字を指定してください。',
        'string' => ':attributeは:min文字から:max文字の間で入力してください。',
        'array' => ':attributeの項目は:min個から:max個にしてください。',
    ],
    'boolean' => ':attributeには、trueかfalseを指定してください。',
    'confirmed' => ':attributeと確認用が一致しません。',
    'date' => ':attributeは正しい日付ではありません。',
    'email' => ':attributeは有効なメールアドレス形式で入力してください。',
    'file' => ':attributeにはファイルを指定してください。',
    'image' => ':attributeには画像ファイルを指定してください。',
    'in' => '選択された:attributeは正しくありません。',
    'integer' => ':attributeには整数を指定してください。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
        'file' => ':attributeは:maxキロバイト以内のファイルにしてください。',
        'array' => ':attributeの項目は:max個以下にしてください。',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
        'file' => ':attributeは:minキロバイト以上のファイルにしてください。',
        'array' => ':attributeの項目は:min個以上にしてください。',
    ],
    'required' => ':attributeは必須項目です。',
    'string' => ':attributeには文字列を指定してください。',
    'unique' => 'すでに登録されている:attributeです。',

    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '名前',
        'title' => 'タイトル',
        // 必要に応じて他のフィールド名も追加してください
    ],

];
