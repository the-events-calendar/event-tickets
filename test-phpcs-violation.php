<?php
// Intentional phpcs violation — DO NOT MERGE.
// Violations: missing space after control keywords, old array() syntax, no space after commas.
function check_items($items,$limit,$strict) {
  $result = array();
  if($strict===true){
    foreach($items as $k=>$v){
      if(strlen($v)>$limit){
        $result[] = $v;
      }elseif(is_null($v)){
        continue;
      }
    }
  }else{
    $result = array_filter($items,function($v) use($limit){ return strlen($v)<=$limit; });
  }
  return $result;
}