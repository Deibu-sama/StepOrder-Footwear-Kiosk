<?php
namespace App\Console\Commands;
use App\Services\FirestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
class SeedFirestore extends Command
{
 protected $signature='steporder:seed {--force}'; protected $description='Seed StepOrder demo Firestore data.';
 public function handle(FirestoreService $firestore):int{
  if($firestore->list('products')&&!$this->option('force')){$this->warn('Products already exist. Use --force.');return self::SUCCESS;}
  $cats=[['name'=>'Sneakers','slug'=>'sneakers'],['name'=>'Sandals','slug'=>'sandals'],['name'=>'Slippers','slug'=>'slippers']];$ids=[];
  foreach($cats as $c){$id='cat_'.$c['slug'];$firestore->create('categories',$c+$this->base(),$id);$ids[$c['name']]=$id;}
  $products=[
   ['Classic Runner','STP-001','Sneakers',2499,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80'],
   ['Urban White','STP-002','Sneakers',2199,'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=900&q=80'],
   ['Street Court','STP-003','Sneakers',2899,'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=900&q=80'],
   ['Cloud Walk','STP-004','Slippers',899,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?auto=format&fit=crop&w=900&q=80'],
   ['Daily Slide','STP-005','Sandals',799,'https://images.unsplash.com/photo-1603487742131-4160ec999306?auto=format&fit=crop&w=900&q=80']];
  foreach($products as [$name,$sku,$cat,$price,$image]){$variants=[];foreach(['39','40','41','42','43'] as $size)$variants[]=['size'=>$size,'color'=>'Black','stock'=>5];$firestore->create('products',['name'=>$name,'sku'=>$sku,'category_id'=>$ids[$cat],'category_name'=>$cat,'price'=>$price,'description'=>'Demo footwear item for the StepOrder kiosk.','image_url'=>$image,'status'=>'active','variants'=>$variants]+$this->base(),'prod_'.Str::lower(Str::random(14)));}
  $this->info('StepOrder sample data created.');return self::SUCCESS;
 }
 private function base(){return ['created_at'=>now()->toIso8601String()];}
}