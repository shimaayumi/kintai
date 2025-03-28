<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判断します。
     *
     * @return bool
     */
    public function authorize()
    {
        // 出品フォームのリクエストは、全てのユーザーに許可
        return true;
    }

    /**
     * リクエストに適用するバリデーションルールを取得します。
     *
     * @return array
     */
    public function rules()
    {
        return [
            'items_name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'product_image' => 'required|mimes:jpeg,png|max:2048',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|string',
            'price' => 'required|integer|min:1',
            'brand_name' => 'required|max:255',

            'product_images' => 'required|array',
            'product_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
            'items_name.required' => '商品名を入力してください。',
            'items_name.max' => '商品名は255文字以内で入力してください。',
            'description.required' => '商品の説明を入力してください。',
            'description.max' => '商品の説明は1000文字以内で入力してください。',
            'product_image.required' => '商品画像をアップロードしてください。',
            'product_image.mimes' => '商品画像はJPEGまたはPNG形式でアップロードしてください。',
            'product_image.max' => '商品画像は2MB以下にしてください。',
            'category_id.required' => 'カテゴリーを選択してください。',
            'category_id.exists' => '選択したカテゴリーが存在しません。',
            'status.required' => '商品の状態を選択してください。',
            'price.required' => '販売価格を入力してください。',
            'price.integer' => '販売価格は整数で入力してください。（例: 1000）',
            'price.min' => '販売価格は1円以上で入力してください。',
            'brand_name.required' => 'ブランド名を入力してください。',
            'brand_name.max' => 'ブランド名は255文字以内で入力してください。',
        ];
    }
}