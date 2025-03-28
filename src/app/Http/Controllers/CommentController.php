<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
public function store(Request $request, $item_id)
{
$request->validate([
'content' => 'required|max:255',
]);

$item = Item::findOrFail($item_id);

$comment = new Comment();
$comment->content = $request->content;
$comment->user_id = Auth::id();
$comment->item_id = $item->id;
$comment->save();

return redirect()->route('items.show', $item->id);
}
}