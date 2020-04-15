<?php

class TokenAuth{

	function getConn(){
		return new PDO('mysql:host=localhost;dbname=bd_ubspace','root','',array(PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES utf8"));
	}

	public function deny_access($request, $response){
		//$res = $this->app->response();
		return $response->withStatus(401);
	}

	public function __invoke($request, $response, $next){

		$tokenAuth = $request->getHeader('Authorization');
		if(!empty($tokenAuth[0])){
			$auth = $tokenAuth[0];
			if($auth === '1gdh87efuhwi'){
				$response = $next($request, $response);
				return $response;
			}else{
				$stmt = getConn()->query("SELECT * FROM operador WHERE token = '$auth'");
				$operador = $stmt->fetch();
				if($operador != FALSE){
					$response = $next($request, $response);
					return $response;
				}else{
					echo "3";
					return $this->deny_access($request, $response);
				}
			}
		}else{
			return $this->deny_access($request,$response);
		}
	}
}

?>