<?php

namespace App\Http\Controllers;

use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KioskController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index(Request $request)
    {
        $products=$this->activeProducts();
        $categories=array_values(array_filter($this->firestore->list('categories'),fn($c)=>($c['active']??true)));
        $selectedCategory=$request->string('category')->toString(); $search=trim($request->string('q')->toString());
        if($selectedCategory) $products=array_values(array_filter($products,fn($p)=>($p['category_id']??'')===$selectedCategory));
        if($search) $products=array_values(array_filter($products,fn($p)=>Str::contains(Str::lower(($p['name']??'').' '.($p['description']??'')),Str::lower($search))));
        return view('kiosk.index',compact('products','categories','selectedCategory','search'));
    }

    public function product(string $id){$product=$this->firestore->find('products',$id);abort_unless($product&&($product['status']??'active')==='active',404);$cart=$this->cartData();return view('kiosk.product',compact('product','cart'));}

    public function addToCart(Request $request)
    {
        $data=$request->validate(['product_id'=>['required','string'],'size'=>['required','string','max:20'],'color'=>['required','string','max:60'],'quantity'=>['required','integer','min:1','max:20']]);
        $product=$this->firestore->find('products',$data['product_id']); abort_unless($product&&($product['status']??'active')==='active',404);
        $variant=$this->findVariant($product,$data['size'],$data['color']);
        if(!$variant||(int)($variant['stock']??0)<1) return back()->with('error','That size/color is currently unavailable.');
        if((int)$data['quantity']>(int)$variant['stock']) return back()->with('error','Only '.$variant['stock'].' item(s) are available.');
        $cart=$this->cartData(); $key=$data['product_id'].'|'.$data['size'].'|'.$data['color']; $newQty=(int)($cart[$key]['quantity']??0)+(int)$data['quantity'];
        if($newQty>(int)$variant['stock']) return back()->with('error','You cannot add more than the available stock.');
        $cart[$key]=['product_id'=>$product['id'],'name'=>$product['name'],'image_url'=>$product['image_url']??'','price'=>(float)$product['price'],'size'=>$data['size'],'color'=>$data['color'],'quantity'=>$newQty,'stock'=>(int)$variant['stock']];
        $request->session()->put('cart',$cart); return redirect()->route('kiosk.product',$product['id'])->with('success','Added to cart.');
    }

    public function cart(){ $cart=$this->cartData(); $total=$this->cartTotal($cart); return view('kiosk.cart',compact('cart','total')); }
    public function updateCart(Request $request){$data=$request->validate(['key'=>['required','string'],'quantity'=>['required','integer','min:1','max:20']]);$cart=$this->cartData();if(!isset($cart[$data['key']]))return back();$item=&$cart[$data['key']];if((int)$data['quantity']>(int)$item['stock'])return back()->with('error','Quantity exceeds available stock.');$item['quantity']=(int)$data['quantity'];$request->session()->put('cart',$cart);return back();}
    public function removeCart(Request $request){$data=$request->validate(['key'=>['required','string']]);$cart=$this->cartData();unset($cart[$data['key']]);$request->session()->put('cart',$cart);return back();}
    public function checkout(){ $cart=$this->cartData(); if(!$cart)return redirect()->route('cart.index')->with('error','Your cart is empty.'); return view('kiosk.checkout',['cart'=>$cart,'total'=>$this->cartTotal($cart)]); }

    public function placeOrder(Request $request)
    {
        $data=$request->validate(['customer_name'=>['nullable','string','max:100']]);$cart=$this->cartData();if(!$cart)return redirect()->route('cart.index')->with('error','Your cart is empty.');
        $items=array_values($cart);$total=$this->cartTotal($cart);
        foreach($items as $item){$product=$this->firestore->find('products',$item['product_id']);$variant=$product?$this->findVariant($product,$item['size'],$item['color']):null;if(!$variant||(int)$variant['stock']<(int)$item['quantity'])return redirect()->route('cart.index')->with('error',$item['name'].' is no longer available in the requested quantity.');}
        foreach($items as $item){$product=$this->firestore->find('products',$item['product_id']);$variants=$product['variants']??[];foreach($variants as &$variant){if(($variant['size']??'')===$item['size']&&($variant['color']??'')===$item['color'])$variant['stock']=(int)$variant['stock']-(int)$item['quantity'];}unset($variant);$this->firestore->update('products',$product['id'],['variants'=>$variants]);}
        $orderNumber=$this->nextOrderNumber();
        $this->firestore->create('orders',['order_number'=>$orderNumber,'customer_name'=>$data['customer_name']??'','items'=>$items,'total'=>$total,'status'=>'pending','created_at'=>now()->toIso8601String()],'ord_'.Str::lower(Str::random(16)));
        $request->session()->forget('cart'); return redirect()->route('order.confirmation',$orderNumber);
    }

    public function confirmation(string $orderNumber){$orders=$this->firestore->findByField('orders','order_number',$orderNumber);abort_unless($orders,404);$order=$orders[0];return view('kiosk.confirmation',compact('order'));}

    private function activeProducts():array{return array_values(array_filter($this->firestore->list('products'),fn($p)=>($p['status']??'active')==='active'));}
    private function cartData():array{return session('cart',[]);}
    private function cartTotal(array $cart):float{return round(array_sum(array_map(fn($i)=>(float)$i['price']*(int)$i['quantity'],$cart)),2);}
    private function findVariant(array $product,string $size,string $color):?array{foreach(($product['variants']??[]) as $v)if(($v['size']??'')===$size&&($v['color']??'')===$color)return $v;return null;}
    private function nextOrderNumber():string{$max=0;foreach($this->firestore->list('orders') as $o)if(preg_match('/(\d+)$/',(string)($o['order_number']??''),$m))$max=max($max,(int)$m[1]);return str_pad((string)($max+1),4,'0',STR_PAD_LEFT);}
}