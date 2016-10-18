<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\Room;
use app\models\Sequence;
use app\models\Admin;

class ModeController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }
    public function actionTogglemode()
    {
    	if (Yii::$app->request->isGet)
		{
	      $para=yii::$app->request->get('para');
	      $admin=Admin::find()->where(['admin_name'=>$para['Name']])->one();
	      $admin_id=$admin->admin_id;
	      $room=Room::find()->where(['adminid'=>$admin_id])->one();
          $seqNum=0;
		  $seqData=array();
		  $roomMode=$room->roomstate;
		  $maxNum=$para['MaxNum'];
		  if($roomMode=='review')
		  {
		  	$status="Failed";
		  	return $status;
		  }
		  else{
			  	$roomMode=$para['RoomMode'];
			  	$room->roomstate=$roomMode;
			  	$room->save();
			  	 if($roomMode=='ok')
			  	 {
			  	 	$sequences=Sequence::find()->where(['roomid' => $room->roomid,'state'=>'0'])->limit($maxNum)->orderBy('time asc')->all();
			  	 	foreach($sequences as $seq){
					$seqId=$seq->seqid;
					array_push($seqData,$seq->content);
	                $seq->state=1;
	                $seq->save();
					$seqNum++;
				   }
		  	     }
		  	     else if($roomMode=='show'){
		  	     	$seqs=Sequence::find()->where(['roomid'=>$room->roomid])->all();
		  	     	$seqNums=count($seqs);
					if($maxNum>$seqNums){
						$maxNum=$seqNums;
					}
					$seqs1=(array)$seqs;
					$resid=array_rand($seqs1,$maxNum);
					if(count($resid)==1){
						$restemp[0]=$resid;
						$resid=$restemp;
					}

					foreach($resid as $id){
						$seq=$seqs[$id];
						$seqId=$seq->seqid;
						array_push($seqData,$seq->content);
						$seq->state=1;
						$seq->save();
						$seqNum++;
			           }
		  	     }
		  	     //Yii::$app->response->format=Response::FORMAT_JSON;
		  	     $msgs=array('seqNum'=>$seqNum,'seqData'=>$seqData);
		         return json_encode($msgs);
		  }
	    }
    }
}