<?php
		define('APP_ID','');
		define('APP_SECRET','');
	
	set_time_limit(120);
	include('HYD/main.php');

	/* Вызываем необходимую функцию */
	if(isset($_POST['method'])){
		$method=strtolower($_POST['method']);
		$a=[];
		foreach($_POST as $key=>$val){
			$t=json_decode($val);
			if($t===null && $val!=='null')
				$t=$val;
			if(is_string($t))             // TO    FROM
				$t=mb_convert_encoding($t,'cp1251','utf8');
				
			$a[strtolower($key)]=$t;
		}
		$out=[
			'data'=>NULL,
			'err'=>NULL,
		];
		
		if($method==='loaduser')
			$res=LoadUser($a['code']);
		else if($method==='getcampslist')
			$res=GetCampsList($a['token'],$a['login']);
		else if($method==='clonecamp')
			$res=CloneCamp($a['token'],$a['id'], $a['count'], false);
		else 
			throw new HYD_ERR('uncknown method '.$method);
				
		$out['data']=$res;
		
		echo json_encode($out,JSON_PRETTY_PRINT);
	}
	
	/*Получает токен по коду, логин пользователя и его */
	function LoadUser($code){
		return HYD::infoByCode($code,APP_ID,APP_SECRET);
	}
	
	/* Возвращает список кампаний пользователя */
	function GetCampsList($token,$login=false){
		return (new HYD($token))->GetCamps($login ? $login : Array());
	}
	
	/* Созжает два клона кампании с шахматкой  */
	function CloneCamp($token,$id, $count){
		$YD=new HYD($token);
		$base=$YD->GetCamps($id)[0];
		
		unset($base->Status);
		unset($base->StartDate);
		
		$ads=$YD->GetAds(['CampaignIDS'=>$id]);
		
		for($i=0;$i<$count;$i++)
			makeCampClone($YD,$count,$i,$base,$ads);

		return 'ok';
	}
	function makeCampClone($YD,$count,$i,$base,$ads){
		$names=['A','B','C'];

		$base=HYD::clone_r($base);//Послкольку объекты в PHP5 передаются по ссылке их необходимо клонировать в памяти. 
		
		$base->CampaignID=0;
		$base->Name.=' _ab_'.$names[$i];
		
		//Шахматка
		$DaysHours=[];
		for($j=0;$j<7;$j++)
			$DaysHours[]=['Days'=>[$j+1]];
		foreach($base->TimeTarget->DaysHours as $Crit){
			foreach($Crit->Days as $day){
				$DaysHours[$day-1]['Hours']=$Crit->Hours;
				if(isset($Crit->BidCoefs) && count($Crit->BidCoefs))
					$DaysHours[$day-1]['BidCoefs']=$Crit->BidCoefs;
			}
		}	
		$dn=0;
		foreach ($DaysHours as &$day){
			$dn++;
			$hours=$day['Hours'];
			$BidCoefs=false;
			if(isset($day['BidCoefs']))
				$BidCoefs=$day['BidCoefs'];

			$j=0;
			$day['Hours']=[];
			if($BidCoefs)
				$day['BidCoefs']=[];
			foreach($hours as $k=>$hour){
				if(($j+$dn)%$count===$i){
					$day['Hours'][]=$hour;
					if($BidCoefs)
						$day['BidCoefs'][]=$BidCoefs[$k];
				}
				$j++;
			}
		}
		$base->TimeTarget->DaysHours=$DaysHours;
		
		$id=$YD->saveCamps($base)[0];
		$YD->stopCamps($id);
			
		makeAds($YD,$id,$ads);	
	}
	function makeAds($YD,$campId,$ads){
	
		//Муть с группами объявлений
		$ads=HYD::clone_r($ads);

		$first=[];//Первые (основные) объявления в группе (либо корень группы либо свободная объява)
		$other=[];//Список остальных
		$otherTree=[];//Дерево остальных
		foreach($ads as $ad){
			$ad->CampaignID=$campId;
			$ad->BannerID=0;
			$gid=$ad->AdGroupID;
			$ad->AdGroupID=0;
			if(!isset($otherTree[$gid]))
				$otherTree[$gid]=[];
			if(!isset($first[$gid]) && (isset($ad->Geo) || isset($ad->Phrases) ||  isset($ad->MinusKeywords)))
				$first[$gid]=$ad;
			else{
				$otherTree[$gid]=&$ad;
				$other[]=&$ad;
			}
		}		
		//Сохраняем главные объявления
		$res=$YD->saveAds(array_values($first));
		$bannerIds=$res;
		
		if(count($other)){
			$res=$YD->getAds([
				"BannerIDS"=>$bannerIds,
				"GetPhrases"=>'No'
			]);
			$newGids=$res;
			$oldGids=array_keys($first);
			foreach($newGids as $i=>$newGid){
				$oldGid=$oldGids[$i];
				foreach($otherTree[$oldGid] as &$ad)
					$ad->AdGroupID=$newGid;
			}
			$YD->saveAds($other);
		}
	}
?>