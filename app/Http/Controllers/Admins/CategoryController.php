<?php

namespace App\Http\Controllers\Admins;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Ramsey\Uuid\Type\Integer;

class CategoryController extends Controller
{
    public function index(){

    $categorys=Category::all();

   return ApiResource::sendResponse("this is all categorys",$categorys);


    }
    public function show(Category $category){


   return ApiResource::sendResponse("category shown successfully",$category);


    }

    public function store(Request $request) {

    $categoryName = $request->validate([
        'name' => 'required|string|max:255|unique:categories,name'
    ]);

    $Category=Category::create($categoryName);

    return ApiResource::sendResponse("Category Created Successfully",$Category,201);


    }
    public function update(Request $request, Category  $category) {

    $categoryName = $request->validate([
        'name' => 'required|string|max:255|unique:categories,name'
    ]);

    $category->update($categoryName);

    return ApiResource::sendResponse("Category updated Successfully",$category,200);


    }
    public function destroy(Category $category) {


    $category->delete();


    return ApiResource::sendResponse("Category deleted Successfully",$category,200);


    }









}
