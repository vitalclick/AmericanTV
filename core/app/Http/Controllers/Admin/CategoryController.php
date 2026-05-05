<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller {
    public function index() {
        $pageTitle  = "Categories";
        $categories = Category::searchable(['name'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.category.index', compact('pageTitle', 'categories'));
    }

    public function save(Request $request, $id = 0) {
        $request->validate([
            'name' => 'required|string|unique:categories,name,' . $id,
            'slug' => 'required|string|unique:categories,slug,' . $id,
            'icon' => 'required',
        ]);

        if ($id) {
            $category = Category::findOrFail($id);
            $notify[] = ['success', 'Category update successfully'];
        } else {
            $category = new Category();
            $notify[] = ['success', 'Category added successfully'];
        }

        $category->name = $request->name;
        $category->slug = $request->slug;
        $category->icon = $request->icon;
        $category->save();

        return back()->withNotify($notify);
    }

    public function status($id) {
        return Category::changeStatus($id);
    }

    public function checkSlug() {
        $category = Category::where('slug', request()->slug)->exists();
        return response()->json([
            'exists' => $category,
        ]);
    }
}
