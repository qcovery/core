<?
    
namespace CaLief\Model;

class CaliefAdminModel
 {
     public $id;
     
     public function exchangeArray($data)
     {
         $this->id     = (!empty($data['id'])) ? $data['id'] : null;
     }
 }