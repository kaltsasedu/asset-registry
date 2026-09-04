<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SavedAssetSearch extends Model { protected $fillable=['user_id','name','status','asset_category_id','location_id','department_id','without_custodian','without_serial','without_invoice','without_location']; protected $casts=['without_custodian'=>'boolean','without_serial'=>'boolean','without_invoice'=>'boolean','without_location'=>'boolean']; public function user(): BelongsTo{return $this->belongsTo(User::class);} public function category(): BelongsTo{return $this->belongsTo(AssetCategory::class,'asset_category_id');} public function location(): BelongsTo{return $this->belongsTo(Location::class);} public function department(): BelongsTo{return $this->belongsTo(Department::class);} }
