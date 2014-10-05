<?php
	class HYD_ERR extends Exception {
		function __construct($mess,$code=null){
			if(is_object($mess)){
				foreach($mess as $key=>$val)
					$this->$key=$val;
				parent::__construct($mess->error_str.' | '.$mess->error_detail,$mess->error_code); 
			}
			else
				parent::__construct($mess,$code); 
		}
	};	
	abstract class HYD_Strategy{
		public static $ShowsDisabled = 'ShowsDisabled';
		public static $HighestPosition = 'HighestPosition';
		public static $LowestCost = 'LowestCost';
		public static $LowestCostPremium = 'LowestCostPremium';
		public static $LowestCostGuarantee = 'LowestCostGuarantee';
		public static $RightBlockHighest = 'RightBlockHighest';

		public static $WeeklyBudget = 'WeeklyBudget';
		public static $CPAOptimizer = 'CPAOptimizer';
		public static $AverageClickPrice = 'AverageClickPrice';
		public static $WeeklyPacketOfClicks = 'WeeklyPacketOfClicks';
		public static $AverageCPAOptimization = 'AverageCPAOptimization';

		public static $Default = 'Default';
		public static $MaximumCoverage = 'MaximumCoverage';
	}
	abstract class HYD_SpendMode{
		public static $Default = 'Default';
		public static $Stretched = 'Stretched';
	}

	trait HYD_TMP {
		static $retryCodes=Array(500=>1,503=>1,506=>1,510=>1,92=>1,254=>1,255=>1,358=>1); // Коды ошибок Диретка, при которых нужно повторять запрос 
		static $retryCodesHttp=Array(500=>1,503=>1,504=>1,507=>1,509=>1,408=>1,409=>1);   // Коды статуса HTTP, при которых нужно повторять запрос
		
		static $defParamName=Array(
			'ArchiveCampaign'=>'CampaignID',
			'DeleteCampaign'=>'CampaignID',
			'GetCampaignParams'=>'CampaignID',
			'GetCampaignsParams'=>'CampaignIDS',
			'ResumeCampaign'=>'CampaignID',
			'StopCampaign'=>'CampaignID',
			'GetBanners'=>'BannerIDS',
			'GetBannerPhrasesFilter'=>'BannerIDS',
			'CreateNewWordstatReport'=>'Phrases',
			'GetKeywordsSuggestion'=>'Keywords',
			'CreateNewForecast'=>'Phrases',
			'GetBannersTags'=>'BannerIDS',
			'GetCampaignsTags'=>'CampaignIDS',
			'GetCampaignsTags'=>'CampaignIDS',
			'GetRetargetingGoals'=>'Logins',
			'EnableSharedAccount'=>'Login',
			'GetSubClients'=>'Login',
			'GetStatGoals'=>'CampaignID'
		);
		static $expectedParams=Array(
			'GetCampaignsList'=>'arr str',
			'CreateOrUpdateBanners'=>'arr obj',
			'GetBannerPhrases'=>'arr int',
			'GetBalance'=>'int',
			'DeleteReport'=>'int',
			'DeleteWordstatReport'=>'int',
			'GetWordstatReport'=>'int',
			'DeleteForecastReport'=>'int',
			'GetForecast'=>'int',
			'UpdateBannersTags'=>'arr obj',
			'UpdateCampaignsTags'=>'arr obj',
			'UpdatePrices'=>'arr obj',
			'GetClientInfo'=>'arr str',
			'GetClientsUnits'=>'arr str',
			'UpdateClientInfo'=>'arr obj',	
		);
		static $expectedTypes=Array(
			'CampaignID'=>'int',
			'CampaignIDS'=>'arr int',

			'Login'=>'str',
			'Logins'=>'arr str',

			'BannerID'=>'int',
			'BannerIDS'=>'arr int',
			
			'FieldsNames'=>'arr str',

			'KeywordID'=>'int',
			'KeywordIDS'=>'arr int',

			'PhraseID'=>'int',
			'PhraseIDS'=>'arr int',

			'TagID'=>'int',
			'TagIDS'=>'arr int',

			'AdditionalMetrikaCounters'=>'arr int',
			'MinusKeywords'=>'split',//arr str
			'RelevantPhrasesBudgetLimit'=>'int',
				
			'Days'=>'arr int',
			
			'StartDate'=>'date',
			'EndDate'=>'date',
			'OrderBy'=>'arr str',
			'GroupByColumns'=>'arr str',
			
			'GetKeywordsSuggestion->Keywords'=>'arr str',
			'CreateNewForecast->Phrases'=>'arr str',
			'CreateNewForecast->GeoID'=>'arr int',
			
			'DisabledDomains'=>'join ,',
			'DisabledIps'=>'join ,',
			
			'RelevantPhrasesBudgetLimit'=>'int',
			
			'CreateNewReport->CompressReport'=>'boolInt',//0 не сжимать, 1 — сжимать gzip
			
			'ContextLimitSum'=>'int',
			'HolidayShowFrom'=>'int',
			'HolidayShowTo'=>'int',
			
			'WarnPlaceInterval'=>'int',
			'MoneyWarningValue'=>'int',
			'GoalID'=>'int',
			'ContextPricePercent'=>'int',
			'ClicksPerWeek'=>'int',
			//'ContextLimit'=>'int',
			
			'MaxPrice'=>'float',
			'AveragePrice'=>'float',
			'AverageCPA'=>'float',
			'WeeklySumLimit'=>'float', 
			
			'GetCampaignsListFilter->Filter->StatusModerate'=>'arr str',
			'GetCampaignsListFilter->Filter->IsActive'=>'arr str',
			'GetCampaignsListFilter->Filter->StatusArchive'=>'arr str',
			'GetCampaignsListFilter->Filter->StatusActivating'=>'arr str',
			'GetCampaignsListFilter->Filter->StatusShow'=>'arr str'
		);

		static $filterParams=Array(
			'GetCampaignsListFilter'=>['StatusModerate','IsActive','StatusArchive','StatusActivating','StatusShow']
		);
		
		static $methodSyns=Array(
			'getcampaignslistfilter'=>'GetCampaignsListFilter',
			'getcamplistfilter'=>'GetCampaignsListFilter',
			
		);
		//NotBoolParams
			//Title
			//Text
			//Name
			//Login
			//CompanyName
			//Phrase
			//Param1
			//Param2
			//FIO
			//MinusKeywords
		/* GetCampaignsListFilter
		
		      "Filter": {
				"StatusModerate": [
					(string)
					...
				],
         "IsActive": [
            (string)
            ...
         ],
         "StatusArchive": [
            (string)
            ...
         ],
         "StatusActivating": [
            (string)
            ...
         ],
         "StatusShow": [
            (string)
            ...
         ]
		*/
		static function is_json_array($val){
			if(!is_array($val))
				return false;
			if(self::isAssocArray($val) && count($val))
				return false;
			return true;		
		}
		static function is_json_object($val){
			if(is_array($val) && !self::is_json_array($val))
				return true;		
			return is_object($val);
		}	
		function toYandexFormat($data){
			if($data === null)
				return null;	
			if(is_object($data))
				$data=(array) $data;
			if(!is_array($data))
			{
				if(is_numeric($data))
					return $data;
				elseif(is_string($data))			                 // TO      FROM
					return $this->isCp1251 ? mb_convert_encoding($data,'utf8','cp1251') : $data;
				elseif($data===true && $this->fixBool)
					return 'Yes';
				elseif($data===false && $this->fixBool)
					return 'No';
				else	
					throw new Exception('Invalid data type '.$data, -2001);
			}		
			
		    foreach($data as $key => $value)
				$data[$key]=$this->toYandexFormat($value);
					
			return $data;	
		}
		function fromYandexFormat($data){
			if($data === null)
				return null;
			if(is_array($data)){
				$res=Array();
				foreach($data as $key => $value)
					$res[$key]=$this->fromYandexFormat($value);
			}	
			else if(is_object($data)){
				$res=clone $data;
				foreach($data as $key => $value)
					$res->$key=$this->fromYandexFormat($value);
			}
			else
			{
				if($data==='Yes' && $this->fixBool)
					$res=true;
				elseif($data==='No'  && $this->fixBool)
					$res=false;
				elseif(is_string($data))							  // TO    FROM
					$res=$this->isCp1251 ? mb_convert_encoding($data,'cp1251','utf8') : $data;
				else	
					$res=$data;
			}				
			return $res;	
		}
		function getDecryptedToken(){
			return $this->decryptToken($this->getToken());
		}
		static function getTokenByCode($code,$appId,$appSecret){
			$res=self::_post("https://oauth.yandex.ru/token","grant_type=authorization_code&code={$code}&client_id={$appId}&client_secret={$appSecret}",'application/x-www-form-urlencoded');

			if($res[0]<200 || $res[0]>=3000)
				throw new HYD_ERR('getTokenByCode err: HTTP Code '.$res[0],-10001);
			
			//
			//
			$res=json_decode($res[1]);
			//print_r($res);
			//exit();
			
			if(isset($res->err))
				throw new HYD_ERR('getTokenByCode err: '.$res->err,-10002);
				
			return $res->access_token;
		} 
		static function _post($url,$data, $type='application/json'){
			if($type==='application/json')
				$str=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
			else
				$str=$data;

			$context = stream_context_create(array('http'=>array(
				'method'=>"POST",
				'content'=>$str,
				'header'=> "Content-Type: {$type}; charset=utf-8\r\n"
						  ."Content-Length: " . strlen($str) . "\r\n"
			)));		
			
			//echo "Content-Type: {$type}; charset=utf-8\r\n"
			//			  ."Content-Length: " . strlen($str) . "\r\n";
			//die($str);
			
			//echo '============================'."\n";
			//echo "\n".'url= '.$url;
			//echo "\n".'str= '.$str."\n";
			$res = file_get_contents($url, 0, $context);
			//print_r($res);
			//echo '============================'."\n";
			
			//exit();
			
					
			$code=explode(' ',$http_response_header[0]);
			$code=$code[1] * 1;
			
			return Array($code,$res);
		}
		private function formKey(){
			return substr(md5('D3453vdgs,mds6N!344'.$this->cryptKey),0,24);
		}
		private function formKeyCS(){
			$key=$this->cryptKey;
			if(!$key)
				$key='none';
			return substr(md5('3asds*czDDSD)9'.$key),0,4);
		}
		private function encryptToken($token){
			//die("xxxxxxxxsssssssssssssss");
			if(strpos($token,'crypted|')===0 || !$this->cryptKey)
				return $token;
			$td = mcrypt_module_open('tripledes', '', 'ecb', '');
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init($td,  $this->formKey(), $iv);
			$token = mcrypt_generic($td, base64_encode($token));
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			$token=base64_encode($token);
			
			return 'crypted|'.$this->formKeyCS().'|'.$token;	
		}
		private function decryptToken($token){
			if(strpos($token,'crypted|')!==0)
				return $token;
			$token=explode('|',$token,3);
			if($token[1]!==$this->formKeyCS())
				throw new Exception('cryptKey is changed', -1010);
				
			$token=base64_decode($token[2]);
			
			$td = mcrypt_module_open('tripledes', '', 'ecb', '');
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init($td,  self::formKey(), $iv);
			$token = mdecrypt_generic($td, $token);
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			return base64_decode($token);
		}
		static function clone_r($data){
			if(is_array($data)){
				$res=Array();
				foreach($data as $key=>$val)
					$res[$key]=self::clone_r($val);
				return $res;
			}
			if(is_object($data)){
				$res=clone $data;
				foreach($data as $key=>$val)
					$res->$key=self::clone_r($val);
				return $res;
			}
			return $data;
		}
		static function isAssocArray($arr){
			return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
		}
		public function getLogin(){
			static $lastToken=false;
			static $lastLogin=false;
			
			$token=$this->getDecryptedToken();
			if($token!==$lastToken || $lastLogin)
				$lastLogin=json_decode(file_get_contents('https://login.yandex.ru/info?&format=json&oauth_token='.$this->getDecryptedToken()))->login;
			$lastToken=$token;
			return $lastLogin;
		}	
	};
?>