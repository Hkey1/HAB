<?php
	include('core.php');
	include('tmp.php');

	class HYD {
		use HYD_TMP;
		use HYD_Core;
		
		static function infoByCode($code,$appId,$appSecret){
			$YD=HYD::byCode($code,$appId,$appSecret);
			$info=$YD->GetClientInfo()[0];

			$list=[];
			$list[$YD->getLogin()]=$info->FIO;
			
			if($info->Role==='Agency')
				foreach($YD->GetSubClients('No') as $cur)
					$list[$cur->Login]=$cur->FIO;
					
			return [
				'login'=>$YD->getLogin(),
				'token'=>$YD->getToken(),
				'info'=>$info,
				'list'=>$list,
				'count'=>count($list)
			];
		}
		
//Кампании	
		var $subClient=false;
		
		function getCamps($params=Array()){
			$method='GetCampaignsParams';
			if(isset($params['Filter'])||isset($params['Logins']))
				$method='GetCampaignsListFilter';
			else if(isset($params['CampaignIDS']))
				$method='GetCampaignsParams';
			else if(isset($params['CampaignID']))
				$params=Array('CampaignIDS'=>$params['CampaignID']);
			else if(is_array($params) && count($params) && is_numeric($params[0])){
				$params=Array('CampaignIDS'=>$params);
			}
			else if(!is_array($params) && is_numeric($params))
				$params=Array('CampaignIDS'=>Array($params));
			else
				$method='GetCampaignsList';
			return $this->request($method,$params);
		}
		function saveCamps($params=Array()){
			//var_dump($params);
			return $this->multiRequest('CreateOrUpdateCampaign',$params);
		}
		 
		//private function removeCamp($params=Array()){
		//	return $this->removeRequest('Campaign',$params);
		//}	 
	    //function removeCamps($params=Array()){
		//	return $this->multiRequest('removeCamp',$params);
		//}	 
		function delCamps($params=Array()){
			return $this->multiRequest('DeleteCampaign',$params);
		}
  	    function arhCamps($params=Array()){
			return $this->multiRequest('ArchiveCampaign',$params);
		}
		function restoreCamps($params=Array()){
			return $this->multiRequest('UnArchiveCampaign',$params);
		}
		
		function startCamps($params=Array()){
			return $this->multiRequest('ResumeCampaign',$params);
		}
		function stopCamps($params=Array()){
			return $this->multiRequest('StopCampaign',$params);
		}

//Объявления
		function getAds($params){
			return $this->request('GetBanners',$params);
		}
		function saveAds($params=Array()){
			return $this->request('CreateOrUpdateBanners',$params);
		}
		//function removeAds($params=Array()){
		//	return $this->removeRequest('Banners',$params);
		//}		
		function delAds($params=Array()){
			return $this->request('DeleteBanners',$params);
		}
  	    function arhAds($params=Array()){
			return $this->request('ArchiveBanners',$params);
		}
		function restoreAds($params=Array()){
			return $this->request('UnArchiveBanners',$params);
		}
		
//Ключевые слова
		function getKeys($params){
			$method='GetBannerPhrases';
			if(isset($params['BannerIDS']))
				$method='GetBannerPhrasesFilter';
			return $this->request($method,$params);	
		}
		private function _stopOrStartKey($params,$Action){
			if(is_json_array($params)|| is_numeric($params))
				$params=Array('KeywordIDS'=>$params);
			if(!isset($params['Action']))
				$params['Action']=$Action;
			if($this->subClient && !isset($params['Login']))
				$params['Login']=$this->subClient;
			return $this->request('Keyword',$params);
		}
  	    function stopKeys($params=Array()){
			return _stopOrStartKey($params,'Suspend');
		}
		function startKeys($params=Array()){
			return _stopOrStartKey($params,'Resume');
		}
	};
?>