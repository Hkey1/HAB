<?php
	trait HYD_Core{
		var $cryptKey='SDwwrSDSDSDwDcas#DJACIUSA^q;lm,PKASDASIdasdaDCBNKEVR*UBEUVcd';//Ключ шифрования. Строка, минимум 30 символов

		var $token="";
		var $appId="";

		var $url="https://api.direct.yandex.ru/live/v4/json/";
		var $locale='ru';
		var $isCp1251=false;
		var $fixBool=true;	
		
		var $retryMaxSeconds=10;
		var $retryMaxCount=1;
		
		private function multiRequest($method,$params){
			if(!self::is_json_array($params))
				$params=array($params);
			$res=[];
			foreach($params as $cur)
				$res[]=$this->{$method}($cur);
			return $res; 
		}
		function __construct($token,$opts=Array()){
			if(is_array($token)){
				$opts=$token;
				$token=false;
			}
			if(!$token && !isset($opts[$token]))
				throw new HYD_ERR('Token Must Be Set',-1001);
			$this->token=$token;
			foreach ($opts as $key => $value)
				$this->$key=$value;
			
			$this->token=$this->getToken();//зашифровка токена
		}
		
		static function byCode($code,$appId,$appSecret,$opts=[]){
			
			if(is_array($code)){
				$opts=$code;
				$code=false;
			}

			if(!$code)
				$code=$opts['code'];
			if(!$appId)
				$appId=$opts['appId'];
			if(!$appSecret)
				$appSecret=$opts['appSecret'];
				
			if(!$code||!$appId||!$appSecret)
				throw new HYD_ERR('Сode, appId and appSecret must Be Set',-1002);
				
			return new HYD("bycode|{$code}|{$appId}|{$appSecret}",$opts);
		}

		public function __call ($method , $args){
			//exit('__call '.$method);
			if(!isset($args[0]))
				return $this->request($method);
			else
				return $this->request($method,$args[0]);
		}
		function fixParamType($val,$type,$path){
			if(($type==='int'||$type==='float') && is_string($val))
				$val=1 * $val;
			else if($type==='str' && !is_string($val))
				$val=''.$val;
			else if($type==='split' && is_string($val)){
				$val=str_replace(",","\n",$val);
				$val=str_replace("\r","\n",$val);
				$arr=explode("\n",$val);
				$val=Array();
				foreach($arr as $cur)
					if(trim($cur)!=='')
						$val[]=trim($cur);
			}
			else if($type==='date' && is_int($val))
				$val=date("Y-m-d",$val);//YYYY-MM-DD
			
			$at=explode(' ',$type);
			if($at[0]==='join' && is_array($val))
				$val=join($at[1],$val);
			if($at[0]==='arr' && $val!==null){
				if(!self::is_json_array($val)) 
					$val=Array($val);
				foreach ($val as $i=>$cur){
					if($at[1]!=='obj')
						$val[$i]=$this->fixParamType($cur,$at[1],$path);
					else{
						if(is_object($cur))
							$cur=(Array) $cur;
						$val[$i]=$this->fixParamsTypes($path.'['.$i.']->', $cur);
					}
				}	
			}
			return $val;
		}
		function fixParamsTypes($prefix, $params){
			if(!is_array($params))
				return $params;
			foreach($params as $name => &$val){
				$path=$prefix.$name;
				$type=false;
				if(isset(self::$expectedTypes[$path]))
					$type=self::$expectedTypes[$path];
				elseif(isset(self::$expectedTypes[$name]))
					$type=self::$expectedTypes[$name];
				
				if(is_object($val))
					$val=(Array)$val;
				if($type)
					$val=$this->fixParamType($val,$type,$path);
				elseif(self::is_json_object($val))
					$val=$this->fixParamsTypes($path.'->',$val);
				$params[$name]=$val;
			}
			return $params;
		}
		function request($method,$params=Array()){
			$methodLC=strtolower($method);
			if(isset(self::$methodSyns[$methodLC]))
				$method=self::$methodSyns[$methodLC];
				
			if(is_array($params) && !count($params) 
			&& ($methodLC==='getclientinfo'|| $methodLC==='getclientsunits'))
				$params=Array($this->getLogin());
			
			if($methodLC==='getsubclients'){
				if($params==='Yes'||$params===true)
					$params=Array('StatusArch'=>'Yes');
				else if($params==='No'||$params===false)
					$params=Array('StatusArch'=>'No');
				if(isset($params['StatusArch'])){
					$tmp=Array();
					if(isset($params['Login']))
						$tmp['Login']=$params['Login'];
					$tmp['Filter']=Array('StatusArch'=>$params['StatusArch']);	
					$params=$tmp;
				}
				if(is_array($params) && !isset($params['Login']))
					$params['Login']=$this->getLogin();
			}
			
			if(is_object($params))
				$params=(Array)$params;
			if(!self::is_json_object($params)){
				if(isset(self::$defParamName[$method])){// [1,2]=> [defParam=>[1,2]]
					$val=$params;
					$params=Array();
					$params[self::$defParamName[$method]]=$val;
				}
				else if(isset(self::$expectedParams[$method]))
					$params=$this->fixParamType($params,self::$expectedParams[$method],$method);
			}

			
			$params=$this->fixParamsTypes($method.'->',$params);
			
			$data = array(
				'locale'    	 => 'ru',
				'method'    	 => $method,
				'param'     	 => $this->toYandexFormat($params),
				'token'			 => $this->getDecryptedToken()
			);		
			
			$startTime=microtime(true);
			$isTimeEnd=false;
			for($i=0;$i<$this->retryMaxCount;$i++){
				list($code, $res)=self::_post($this->url,$data); 
				
				if(isset(self::$retryCodesHttp[$code]))
					$retry="HTTP Code = $code";
				else if($code<200 || $code>=300){
					$err= new HYD_ERR("Bad HTTP status={$code}. method=$method", -1012);
					$err->params=$params;
					$err->method=$method;
					throw $err; 
				}else{
					$res=$this->fromYandexFormat(json_decode($res)); 
					if(isset($res->error_code) && isset(self::$retryCodes[$res->error_code * 1]))
						$retry="Yandex err Code: ".$res->error_code.' ('.$res->error_str.' | '.$res->error_detail.')';
					if(isset($res->error_code)){
						$err= new HYD_ERR($res);
						$err->params=$params;
						$err->method=$method;
						throw $err; 
					}
					else
						return $res->data;
				}
				if($startTime + $this->$retryMaxSeconds < microtime(true)){
					$isTimeEnd=true;
					break;
				}
			}
			$err= new HYD_ERR("Max Reties. method=$method, retry reason: {$retry} ", -1022);
			$err->params=$params;
			$err->method=$method;
			throw $err; 

		}
		
		function getToken(){
			$token=$this->token;
			if(strpos($token,'bycode|')===0){
				$token=explode('|',$token,4);
				
				$token=$this->getTokenByCode($token[1],$token[2],$token[3]);

				$this->token=$token;
			}
			//die('hhhhhhhhhhhhhhhhhhhh');
			return $this->encryptToken($token);
		}
	};
?>