<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class CategoryController extends Controller
{
 public function __construct(private readonly FirestoreService $firestore){}
 public function index(){ $categories=$this->firestore->list('categories');usort($categories,fn($a,$b)=>strcmp($a['name']??'',$b['name']??''));return view('admin.categories.index',compact('categories')); }
 public function create(){return view('admin.categories.form',['category'=>null]);}
 public function store(Request $request){$data=$request->validate(['name'=>['required','string','max:80'],'description'=>['nullable','string','max:500'],'image_url'=>['nullable','url','max:1000']]);$data['active']=true;$data['slug']=Str::slug($data['name']);$data['created_at']=now()->toIso8601String();$this->firestore->create('categories',$data,'cat_'.Str::lower(Str::random(12)));return redirect()->route('admin.categories.index')->with('success','Category created.');}
 public function edit(string $id){$category=$this->firestore->find('categories',$id);abort_unless($category,404);return view('admin.categories.form',compact('category'));}
 public function update(Request $request,string $id){$data=$request->validate(['name'=>['required','string','max:80'],'description'=>['nullable','string','max:500'],'image_url'=>['nullable','url','max:1000']]);$data['slug']=Str::slug($data['name']);$this->firestore->update('categories',$id,$data);return redirect()->route('admin.categories.index')->with('success','Category updated.');}
 public function destroy(string $id){$this->firestore->delete('categories',$id);return redirect()->route('admin.categories.index')->with('success','Category deleted.');}
}