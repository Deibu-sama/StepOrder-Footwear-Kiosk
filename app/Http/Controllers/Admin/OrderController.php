<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
class OrderController extends Controller
{
 public function __construct(private readonly FirestoreService $firestore){}
 public function index(){$orders=$this->firestore->list('orders');usort($orders,fn($a,$b)=>strcmp((string)($b['created_at']??''),(string)($a['created_at']??'')));return view('admin.orders.index',compact('orders'));}
 public function show(string $id){$order=$this->firestore->find('orders',$id);abort_unless($order,404);return view('admin.orders.show',compact('order'));}
 public function status(string $id){$order=$this->firestore->find('orders',$id);abort_unless($order,404);$newStatus=request('status');abort_unless(in_array($newStatus,['pending','paid','completed','cancelled'],true),422);if(($order['status']??'')!=='cancelled'&&$newStatus==='cancelled'){foreach(($order['items']??[]) as $item){$product=$this->firestore->find('products',$item['product_id']);if(!$product)continue;$variants=$product['variants']??[];foreach($variants as &$variant)if(($variant['size']??'')===($item['size']??'')&&($variant['color']??'')===($item['color']??''))$variant['stock']=(int)($variant['stock']??0)+(int)($item['quantity']??0);unset($variant);$this->firestore->update('products',$product['id'],['variants'=>$variants]);}}$this->firestore->update('orders',$id,['status'=>$newStatus,'updated_at'=>now()->toIso8601String()]);return back()->with('success','Order status updated.');}
}