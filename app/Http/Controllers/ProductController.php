<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function show($id)
    {
        // $products = Product::all();
        //  return "Products Page";
        $product = Product::find($id);
        return $product;
    }
}
