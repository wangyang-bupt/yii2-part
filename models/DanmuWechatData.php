<?php

namespace app\models;

use yii\base\Model;
use app\models\Room;
use app\models\Sequence;
use app\models\Admin;
use app\models\User;

class DanmuWechatData extends Model{

	private $user;
	private $room;
	private $now;
	private $userId;

	public $userState;

	public function __construct($userId){
	     parent::__construct();
	     $this->overtime=60*60*24;
		 $this->now=time();
		 $this->userId=$userId;
		 $this->user=User::find()->where(['userid'=>$userId])->one();
		 if($this->user->roomid){
		 	$this->room=Room::find()->where(['roomid'=>$this->user->roomid])->one();
		 }
		 $this->checkState();
    }

    public function checkState(){
		if(!$this->user){
			$this->userState='notyet';
		}else{
			$this->userState=$this->user->userstate;
		}
	}
    
    public function initUser($appid=false){
		if($this->userState=='notyet'){
			$User=new User();
			$User->userid=$this->userId;
			$User->userstate='create';
			$User->createtime=$this->now;
            $User->save();
		}else if($this->userState=='exit'){
			$User=User::find()->where(['userid'=>this->userId])->one();
			$User->userstate='create';
			$User->save();
		}
		$this->__construct($this->userId);
		if($appid){
			if($appid!=$GLOBALS['_config']['officalAccount']['appId']){
			$adminName=Wechat::find()->where(['appid'=>$appid])->one()->admin;
			$roomId=Admin::find()->where(['admin_name'=>$adminName])->one()->room_id;
			if(isset($roomId)&&$roomId!=-1){
				$ret=$this->selectRoom($roomId);
				return $ret;
			}
		  }
		}
	}

	public function selectRoom($roomId){
		$selectedRoom=Room::find()->where(['roomid'=>$roomId])->one();
		if(!$selectedRoom){
			return false;
		}else{
			$selectedRoom->number+=1;
			$selectedRoom->save();
			$User=User::find()->where(['userid'=>this->userId])->one();
			$User->roomid=$roomId;
			$User->userstate='sender';
			$User->save();
			return true;
		}
	}

	public function sendMessage($message){
		//支持emoji
		// $tmpStr = json_encode($message); 
		// $tmpStr = preg_replace("#(\\\ue[0-9a-f]{3})#ie","addslashes('\\1')",$tmpStr); 
		// $message = json_decode($tmpStr);

		//使用emoji库
		// $message = emoji_docomo_to_unified($message); 
		$message = str_replace(array("\r\n", "\r", "\n"), '', $message);
		if($message=='【收到不支持的消息类型，暂无法显示】'){
			return '发送消息类型不受支持';
		}else{
			$retMsg='弹幕"'.$message.'"发送成功';
		}
		$roomState=Room::find()->where(['roomid'=>$this->user->roomid])->one();
		$sequence=new Sequence();
		$sequence->userid=$this->userId;
		$sequence->time=$this->now;
		$sequence->roomid=$this->user->roomid;
		$sequence->content=$message;
		if($roomState->roomstate=='review'){
			$sequence->state=2;
		}
		$sequence->save();
		yii::$app->runAction('site/getByChannel','seqid'=>"$sequence->seqid",'rid'=>"$sequence->roomid");
        $roomState->postnum+=1;
        $roomState->save();
        $User=User::find()->where(['userid'=>$this->userId])->one();
        $User->sendnum+=1;
        $User->save();
		return $retMsg;
	}

	//用户退出
	public function userExit(){
		if($this->user->userstate]=='sender'){
			$Room=Room::find()->where(['roomid'=>$this->user->roomid])->one();
			$Room->number-=1;
			$Room->save();
		}
		$User=User::find()->where(['userid'=>$this->userId])->one();
		$User->userstate='exit';
		$User->save();
		return true;
	}

	//获取用户Id
	public function getUserIdNum(){
		if($this->user){
			return $this->user->id;
		}else{
			return false;
		}
	}

}