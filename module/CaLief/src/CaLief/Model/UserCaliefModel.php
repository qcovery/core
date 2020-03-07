<?
    
namespace CaLief\Model;

class UserCaliefModel
 {
     public $id;
     
     public function exchangeArray($data)
     {
         $this->id     = (!empty($data['id'])) ? $data['id'] : null;
     }
 }