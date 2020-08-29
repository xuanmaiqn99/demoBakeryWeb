<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = "products";

    public function product_type(){
        return $this->belongsTo('App\ProductType', 'is_type', 'id');  //belong to -> id la cua bang product
    }

    public function bill_detail(){
        return $this->hasMany('App\BillDeatil', 'id_product', 'id');
    }
}
