<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Carbon\Carbon;
class DashboardController extends Controller
{
 public function __construct(private readonly FirestoreService $firestore){}
 public function index(){$products=$this->firestore->list('products');$orders=$this->firestore->list('orders');$today=Carbon::now(config('app.timezone'))->toDateString();$todayOrders=array_values(array_filter($orders,fn($o)=>str_starts_with((string)($o['created_at']??''),$today)));$pending=count(array_filter($orders,fn($o)=>($o['status']??'')==='pending'));$paid=count(array_filter($orders,fn($o)=>in_array(($o['status']??''),['paid','completed'],true)));$todaySales=array_sum(array_map(fn($o)=>in_array(($o['status']??''),['paid','completed'],true)?(float)($o['total']??0):0,$todayOrders));$lowStock=0;foreach($products as $p)foreach(($p['variants']??[]) as $v)if((int)($v['stock']??0)<=3)$lowStock++;usort($orders,fn($a,$b)=>strcmp((string)($b['created_at']??''),(string)($a['created_at']??'')));$recentOrders=array_slice($orders,0,8);return view('admin.dashboard.index',compact('products','orders','todayOrders','pending','paid','todaySales','lowStock','recentOrders'));}
}