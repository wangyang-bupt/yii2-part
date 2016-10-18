<?php

namespace app\models;

use yii\base\Model;
use app\models\Room;
use app\models\Sequence;
use app\models\Admin;

class DanmuDesktopData extends Model{

	public $valid;

	private $admin;
	private $now;
    //***********************************************************
    //private属性的访问可能有问题？
    //***********************************************************
	public function __construct($user,$hashPass){
	 	parent::__construct();
	 	$this->now=time();	
		$this->valid($user,$hashPass);
	}
    
    public function valid($user,$hashPass){
		$valid=Admin::find()->where(['admin_name'=>$user,'admin_passwdhash'=>$hashPass])->one();
		if($valid){
			$this->valid=true;
			$this->admin=$valid;
		}else{
			$this->valid=false;
		}
	}

	public function getRoomId(){
		$roomId=$this->admin->room_id;
		if($roomId!=-1){
			return $roomId;
		}else{
			return false;
		}
	}

	public function getAllUserIdNum(){
		$roomId=$this->getRoomId();
		$userIdNums=User::find()->where(['roomid'=>$roomId,'userstate'=>'sender'])->all();
		$userIdArr=array();
		foreach($userIdNums as $v){
			array_push($userIdArr,$v['id']);
		}
		$msgs=array('userIdNumData'=>$userIdArr);
		return $msgs;
	}
   
   
}