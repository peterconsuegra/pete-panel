<?php

namespace App\Models;

use Log;
use Illuminate\Database\Eloquent\Model;
use App\Services\PeteOption; // <— add this

class Option extends Model
{
    protected $fillable = ['option_name', 'option_value', 'visible'];
    protected $table = 'options';

    public static function delete_meta_value($key){
        $object = static::where('option_name',$key)->first();
        if($object){
            $object->delete();
        }
    }

    public static function get_meta_value($key){
        $object = static::where('option_name',$key)->first();
        if(isset($object)){
            return $object->option_value;
        }else{
            return "";
        }
    }

    public static function create_or_update_meta($key,$value){
        $object = static::where('option_name',$key)->first();
        if(isset($object)){
            $object->option_value = $value;
            $object->save();
        }else{
            $object = new static();
            $object->option_name = $key;
            $object->option_value = $value;
            $object->save();
        }
        return $object->option_value;
    }

    public static function meta_value_toggle($key){
        $object = static::where('option_name',$key)->first();
        if(isset($object)){
            if($object->option_value == "on"){
                $object->option_value = "off";
            }else if($object->option_value == "off"){
                $object->option_value = "on";
            }
            $object->save();
        }else{
            $object = new static();
            $object->option_name = $key;
            $object->option_value = "off";
            $object->save();
        }
        return $object->option_value;
    }
}
