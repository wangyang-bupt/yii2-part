<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\Wechat;
use app\models\DanmuDesktopData;

class ClientController extends Controller
{
    public $user;
    public $hashPass;
    public $para;
    public $dataObj;

	/*public function init(){
      parent::init();
      
    } */
    //*******************************************************
    //同类内部调用action能否用runAction??
    //*******************************************************
    public function actionHalt($errMark){
        $controlError=array(
      '0'=>array('loginErr','noUserOrPasswd'),
      '1'=>array('funcErr','noSetFunction'),
      '2'=>array('validErr','validError'),
      '3'=>array('funcErr','noFunction'),
      '4'=>array('setAppErr','paraErr'),  
      '5'=>array('getQrErr','paraErr'),
      '6'=>array('replyErr','noReply'),
      '7'=>array('toggleShowErr','paraErr'),
      '8'=>array('seqNumErr','paraErr'),
      );
      $errType=$controlError[$errMark][0];
      $errName=$controlError[$errMark][1];
      $reply=array('errType'=>$errType,'errMsg'=>$errName,'errMark'=>$errMark);
      $this->actionReply($reply);
    }
    public function actionValid(){
        global $dataObj;
        if(!$this->user&&!$this->hashPass&&!$this->para){
            @$this->user=yii::$app->request->get('user');
            @$this->hashPass=yii::$app->request->get('hashPass');
            @$this->para=yii::$app->request->get('para');
            if(!isset($this->user)||!isset($this->hashPass)){
               $this->actionHalt(0);
            }
         }
        $this->dataObj=new DanmuDesktopData($this->user,$this->hashPass);
        if($this->dataObj->valid){
            return true;
        }else{
            $this->actionHalt(2);
        }
    }
    public function actionReply($reply){
        if(isset($reply)){
            echo $replyJson=json_encode($reply,JSON_UNESCAPED_SLASHES);
        }else{
            $this->actionHalt(6);
        }
        exit();
    }
//*************************桌面端直接访问的api**********************
    public function actionCheckusr(){
        $this->actionReply($this->actionValid());
    }
    /*public function actionGetSeq(){
        $this->actionValid();
        if(isset($this->para)){
            $seq=$dataObj->getSeq($this->para);
        }else{
            $seq=$dataObj->getSeq();
        }
        $this->actionReply($seq);
    }*/
    public function actionGetroomid(){
        $this->actionValid();
        $ret=$this->dataObj->getRoomId();
        $this->actionReply($ret);
    }
    public function actionGetalluseridnum(){
        $this->actionValid();
        $ret=$this->dataObj->getAllUserIdNum();
        $this->actionReply($ret);
    }

  	public function actionBinding()
  	{
  		if (Yii::$app->request->isGet)
  		{
  	      $para=yii::$app->request->get('para');
  	      if(!Wechat::find()->where(['admin' => $para['Name']])->one()){
  		      $wechat = new Wechat;
  		      $wechat->admin=$para['Name'];
  		      $wechat->appid=$para['SubscriptionID'];
  		      $wechat->wechat_name=$para['SubscriptionName'];
  		      $wechat->access_token=$para['tokenNumber'];
  		      $wechat->encoding_aes_key=$para['DecryptionKey'];
  		      $wechat->create_time=time();
  		      $wechat->save();
  		      $status="New";
  		      return $status;
  		  }
            else{
            	$wechat=Wechat::find()->where(['admin' => $para['Name']])->one();
            	$wechat->appid=$para['SubscriptionID'];
      		    $wechat->wechat_name=$para['SubscriptionName'];
      		    $wechat->access_token=$para['tokenNumber'];
      		    $wechat->encoding_aes_key=$para['DecryptionKey'];
      		    $wechat->last_update_time=time();
      		    $wechat->save();
      		    $status="Update";
      		    return $status;
                }
              }
              else{
              	$status="Failed";
              	return $status;
              }
  	}
}