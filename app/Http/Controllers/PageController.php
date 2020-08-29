<?php

namespace App\Http\Controllers;

use App\Slide;
use App\Product;
use App\ProductType;
use App\Cart;
use App\Customer;
use App\Bill;
use App\BillDetail;
use App\User;
use Session;
use Hash;
use Auth;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function getIndex()
    {
        $slide = Slide::all();
        // print_r($slide);
        // exit;
        // return view('page.trangchu',['slide' => $slide]);
        $new_product = Product::where('new', 1)->paginate(4); //hien thi 4sp tren trang thoi
        // dd($new_product);
        $sanpham_khuyenmai = Product::where('promotion_price', '<>', 0)->paginate(8);
        return view('page.trangchu', compact('slide', 'new_product', 'sanpham_khuyenmai'));
    }

    public function getLoaiSp($type)
    {
        $sp_theoloai = Product::where('id_type', $type)->get();
        $sp_khac = Product::where('id_type', '<>', $type)->paginate(3);
        $loai = ProductType::all();
        $loai_sp = ProductType::where('id', $type)->first();
        return view('page.loai_sanpham', compact('sp_theoloai', 'sp_khac', 'loai', 'loai_sp'));
    }

    public function getChitiet(Request $req)
    {
        $sanpham = Product::where('id', $req->id)->first();
        $sp_tuongtu = Product::where('id_type', $sanpham->id_type)->paginate(6);
        return view('page.chitiet_sanpham', compact('sanpham', 'sp_tuongtu'));
    }

    public function getLienhe()
    {
        return view('page.lienhe');
    }

    public function getGioithieu()
    {
        return view('page.gioithieu');
    }

    public function getAddtoCart(Request $req, $id)
    {
        $product = Product::find($id);
        $oldCart = Session('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $cart->add($product, $id);
        $req->session()->put('cart', $cart);
        return redirect()->back();
    }

    public function getDelItemCart($id)
    {
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $cart->removeItem($id);
        if (count($cart->items) > 0) {
            Session::put('cart', $cart);
        } else {
            Session::forget('cart');
        }
        // Session::put('cart',$cart);
        return redirect()->back();
    }

    public function getCheckout()
    {
        return view('page.dat_hang');
    }

    public function postCheckout(Request $req)
    {
        $cart = Session::get('cart');
        // dd($cart);
        $customer = new Customer;
        $customer->name = $req->name;
        $customer->gender = $req->gender;
        $customer->email = $req->email;
        $customer->address = $req->address;
        $customer->phone_number = $req->phone;
        $customer->note = $req->notes;
        $customer->save();

        $bill = new Bill;
        $bill->id_customer = $customer->id;
        $bill->date_order = date('Y-m-d');
        $bill->total = $cart->totalPrice;
        $bill->payment_method = $req->payment;
        $bill->note = $req->notes;
        $bill->save();

        foreach ($cart->items as $key => $value) {
            $bill_detail =  new BillDetail;
            $bill_detail->id_bill = $bill->id;
            $bill_detail->id_product  = $key;
            $bill_detail->quantity = $value['qty'];
            $bill_detail->unit_price = ($value['price'] / $value['qty']);
            $bill_detail->save();
        }
        Session::forget('cart');
        return redirect()->back()->with('thongbao', 'Dat hang thanh cong');
    }

    public function getLogin()
    {
        return view('page.dangnhap');
    }

    public function getSignup()
    {
        return view('page.dangki');
    }

    public function postSignup(Request $req)
    {
        $this->validate($req,
            [
                'email'=>'required|email|unique:users,email',
                'password'=>'required|min:6|max:20',
                'fullname'=>'required',
                're_password'=>'required|same:password'
            ],
            [
                'email.required'=>'Vui long nhap email',
                'email.email'=>'Khong dung dinh dang email',
                'email.unique'=>'Email da ton tai',
                'password.required'=>'Vui long nhap mat khau',
                're_password.same'=>'Mat khau khong giong nhau',
                'password.min'=>'Mat khau it nhat 6 ky tu',
                'password.max'=>'Mat khau khong qua 20 ky tu'
            ]);
        $user = new User();
        $user->full_name = $req->fullname;
        $user->email = $req->email;
        $user->password = Hash::make($req->password);  //ma hoa mat khau
        $user->phone = $req->phone;
        $user->address = $req->address;
        $user->save();
        return redirect()->back()->with('thanhcong','tao tai khoan thanh cong');
    }

    public function postLogin(Request $req)
    {
        $this->validate($req,
        [
        'email'=>'required|email',
        'password'=>'required|min:6|max:20'
        ],
        [
            'email.required'=>'Vui long nhap email',
            'email.email'=>'Email khong dung qui dinh',
            'password.required'=>'Vui long nhap mat khau',
            'password.min'=>'Mat khau it nhat 6 ky tu',
            'password.max'=>'Mat khau khong qua 20 ky tu'
        ]);
        $credentials = array('email'=>$req->email,'password'=>$req->password);
        if(Auth::attempt($credentials)){
            // return redirect()->back()->with('message','dang nhap thanh cong');
            return redirect()->back()->with(['flag'=>'success','message'=>'dang nhap thanh cong']);
        }
        else{
            return redirect()->back()->with(['flag'=>'danger','message'=>'dang nhap khong thanh cong']);
        }
    }

    public function getLogout()
    {
        Auth::logout();
        return redirect()->route('trangchu');
    }

    public function getSearch(Request $req){
        $product = Product::where('name','like','%'.$req->key.'%')
                            ->orwhere('unit_price',$req->key)
                            ->get();
        return view('page.search',compact('product'));
    }
        
}
