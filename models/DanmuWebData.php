<?php

namespace app\models;

use yii\base\Model;
use app\models\Room;
use app\models\Sequence;
use app\models\Admin;

class DanmuWebData extends Model
{
    private $adminName;
	private $admin;
	private $now;
    
    public function __construct($adminName){
    	parent::__construct();
    	$this->adminName=$adminName;
    	$this->now=time();
    	$this->admin=Admin::find()->where(['admin_name'=>$adminName])->one();   
     }

    public function validAdmin($passwdHash){
    	if((isset($this->admin))&&($passwdHash==$this->admin->admin_passwdhash)){
			return true;
		}else{
			return false;
		}
    }

    public function createAdmin($passwdHash){
    	if(!$this->admin){
    		$Admin=new Admin();
    		$Admin->admin_name=$this->adminName;
    		$Admin->admin_passwdhash=$passwdHash;
    		$Admin->admin_createtime=$this->now;
			$Admin->save();
			return true;
		}else{
			return false;
		}
    }

    public function createRoom(){
    	if($this->admin->room_id==-1){
			$roomId=0;
			$existRoom=Room::find()->where(['roomstate' => 'destroied'])->orderBy('roomid')->one();
			if($existRoom){
				$roomId=$existRoom->roomid;
				$existRoom->adminid=$this->admin->admin_id;
				$existRoom->roomstate='ok';
				$existRoom->number=0;
				$existRoom->postnum=0;
				$existRoom->createtime=$this->now;
                $existRoom->lastsendtime=$this->now;
                $existRoom->save();
				$this->update('room',$updateRoomArray,"roomid=$roomId");
			}else{
				$lastRoom=Room::find()->orderBy('roomid desc')->one();
				$roomId=$lastRoom?$lastRoom->roomid+1:0;
				$room=new Room();
				$room->roomid=$roomId;
				$room->adminid=$this->admin->admin_id;
				$room->roomstate='ok';
				$room->createtime=$this->now;
                $room->lastsendtime=$this->now;
				$room->save();
			}
			$Admin=Admin::find()->where(['admin_id'=>$this->admin->admin_id])->one();
			$Admin->room_id=$roomId;
			$Admin->save();
			return true;
		}else{
			return false;
		}
    }

    public function getState(){
		if($this->admin->room_id==-1){
			return false;
		}else{
			$roomState=Room::find()->where(['roomid'=>$this->admin->room_id])->asArray()->one();
			$seqState=Sequence::find()->where(['roomid'=>$this->admin->room_id])->asArray()->all();
			$ret['roomState']=$roomState;
			$ret['roomState']['createtime']=date('Y-m-d H:i:s',$roomState['createtime']);
			$ret['seqState']=$seqState;
			foreach($seqState as $key=>$seq){
				$ret['seqState'][$key]['time']=date('Y-m-d H:i:s',$seq['time']);
			}
			return $ret;
		}
	}

	public function destoryRoom(){
		if($this->admin->room_id!=-1){
			$room=Room::find()->where(['roomid'=>$this->admin->room_id])->one();
			$room->roomstate='destoried';
			$room->save();
            $Admin=Admin::find()->where(['admin_id'=>$this->admin->admin_id])->one();	
			$Admin->room_id=-1;
			$Admin->save();
			Sequence::find()->where(['roomid'=>$this->admin->room_id])->one()->delete();
			return true;
		}else{
			return false;
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

	public function toggleMode($mode){
		$room=Room::find()->where(['roomid'=>$this->admin->room_id])->one();
		$room->roomstate=$mode;
		$room->save();	
		return $mode;
	}

	public function seqDel($seqNum){
		$haveSeq=Sequence::find()->where(['seqid'=>$seqNum,'roomid'=>$this->admin->room_id])->one();
		if(count($haveSeq)!=0){
			Sequence::find()->where(['seqid'=>$seqNum])->one()->delete();
			return true;
		}else{
			return false;
		}
	}

	public function seqReview($seqNum){
		$haveSeq=Sequence::find()->where(['seqid'=>$seqNum,'roomid'=>$this->admin->room_id])->one();
		if(count($haveSeq)!=0){
			if($haveSeq->state==2){
				$sequence=Sequence::find()->where(['seqid'=>$seqNum])->one();
				$sequence->state=0;
				$sequence->save();
				return true;
			}
		}else{
			return false;
		}
	}
}