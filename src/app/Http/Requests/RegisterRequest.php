<?php

namespace App\Http\Requests;

use Faker\Guesser\Name;
use Illuminate\Foundation\Http\FormRequest;


class RegisterRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判断します。
     *
     * @return bool
     */
    public function authorize()
    {
        // ここでリクエストを実行する権限があるかどうかをチェックできます
        return true;  // ここでは全てのユーザーに許可しています
    }

    /**
     * リクエストに適用するバリデーションルールを取得します。
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255', // 名前は必須、文字列、255文字以内

            'email' => 'required|email|unique:users,email', // メールアドレスは必須、メール形式、ユーザーのメールアドレスはユニーク
            'password' => 'required|string|min:8|confirmed', // パスワードは必須、文字列、8文字以上、確認用パスワードと一致
        ];
    }

    /**
     * バリデーションエラーメッセージをカスタマイズする。
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'お名前を入力してください。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスはすでに使用されています。',
            'password.required' => 'パスワードは必須です。',
            'password.min' => 'パスワードは8文字以上でなければなりません。',
            'password.confirmed' => '確認用パスワードが一致しません。',
        ];
    }

    /**
     * バリデーション後に使用するデータを取得します。
     *
     * @return array
     */
    public function validated()
    {
        return parent::validated();
    }
}
