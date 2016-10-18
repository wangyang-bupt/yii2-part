<?php

namespace app\models;

use yii\base\Model;
use app\models\Room;
use app\models\Sequence;
use app\models\Admin;
use app\models\User;
use app\models\Wechat;

class OfficialAccount extends Model{

	private $appid;
	private $account;
	private $mysql;
	private $admin;
	

	public function __construct($appid,$admin=false){
    	parent::__construct();
    	$this->appid=$appid;
		$this->admin=$admin;
		if($admin){
			$this->account=Wechat::find()->where(['admin'=>$this->admin])->one();
		}else{
			$this->account=Wechat::find()->where(['appid'=>$this->appid])->one();
		}
     }

     public function getMsgAttr(){
		$attr=array();
		if(isset($this->account)){
			$attr['appid']=$this->appid;
			$attr['token']=$this->account->access_token;
			$attr['encodingaeskey']=$this->account->encoding_aes_key;
			return $attr;
		}else{
			return false;
		}
	}
	public function setAttr($token,$encodingaeskey,$name){
		if(!$this->account){
			$this->setMsgAttr($token,$encodingaeskey,$name,$this->admin);
			return 'new';
		}else{
			$this->updateMsgAttr($token,$encodingaeskey,$name,$this->admin);
			return 'update';
		}
	}

	//建立公众号的消息属性
	public function setMsgAttr($token,$encodingaeskey,$name,$admin){
		$now=time();
        $wechat=new Wechat();
        $wechat->appid=$this->appid;
        $wechat->wechat_name=$name;
        $wechat->access_token=$token;
        $wechat->encoding_aes_key=$encodingaeskey;
        $wechat->admin=$admin;
        $wechat->create_time=$now;
        $wechat->last_update_time=$now;
        $wechat->save();
	}

	public function updateMsgAttr($token,$encodingaeskey,$name,$admin){
		$now=time();
		$wechat=Wechat::find()->where(['admin'=>$admin])->one();
		$wechat->appid=$this->appid;
        $wechat->wechat_name=$name;
        $wechat->access_token=$token;
        $wechat->encoding_aes_key=$encodingaeskey;
        $wechat->last_update_time=$now;
        $wechat->save();
	}
}