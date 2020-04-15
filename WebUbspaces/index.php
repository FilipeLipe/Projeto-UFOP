<?php

//use \Psr\Http\Message\ServerRequestInterface as Request;
//use \Psr\Http\Message\ResponseInterface as Response;

require_once('TokenAuth.php');

require 'vendor/autoload.php';

$app = new \Slim\App(['settings' => ['addContentLengthHeader' => false]]);

$app->add(new \TokenAuth());

function getConn(){
	return new PDO('mysql:host=localhost;dbname=bd_ubspace','root','',array(PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES utf8"));
}

//Adicionar objeto ao BD
$app->post('/addObject/',function($request, $response, $args){
	
	$cod = $request->getParam('codigo');
	$nome = $request->getParam('nome');
	$estado = $request->getParam('estado');
	$descricao = $request->getParam('descricao');
	$bloco = $request->getParam('bloco');
	$sala = $request->getParam('sala');
	$data_entrada = $request->getParam('data_entrada');
	$quem_recebeu = $request->getParam('recebeu');
	$nota = $request->getParam('nota');
	$unidade = $request->getParam('unidade');
	$foto = $request->getParam('foto');
	$user_id = $request->getParam('nome_usuario');
	$conservacao = $request->getParam('conservacao');
	$empenho = $request->getParam('empenho');

	$db = getConn();
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false); 

	//$stmt = getConn()->query("INSERT INTO objeto VALUES ('234789','Mesa','Normal','12','12','2018','dfkjhsdf','sdfjhsdf','sdfjhsf','234789123','ICEA','sdjfhk','null.jpg')");
	$stmt = $db->query("INSERT INTO objeto VALUES ('$cod','$nome','$estado','$data_entrada','$bloco','$sala','$quem_recebeu','$nota','$unidade','$descricao','$foto','$user_id',CURRENT_TIMESTAMP,NULL,NULL,'$conservacao','$empenho')");
	$errors = $db->errorInfo();
	print_r($errors[0]);
});

//Obter objeto do BD
$app->get('/getObject/', function($request, $response, $args){
	$getId = $request->getParam('id');
	$stmt = getConn()->query("SELECT * FROM objeto WHERE codigo=$getId");
	$objeto = $stmt->fetch(PDO::FETCH_OBJ);
	return json_encode($objeto);
});

//Deletar objeto do BD
$app->post('/delete/', function($request, $response, $args){
	$getId = $request->getParam('id');
	$getFoto = $request->getParam('foto');
	$getUser = $request->getParam('delete_user');
	$stmt = getConn()->query("UPDATE objeto SET estado = 'Excluido', op_exclusao_id = '$getUser', tempo_exclusao = CURRENT_TIMESTAMP WHERE codigo=$getId");

	//echo("teste");
	//echo ($getFoto);
	
	//unlink("photos/$getFoto");
	//unlink("photos/thumbs/$getFoto");

	print_r($stmt->rowCount());
});

$app->post('/deleteUndo/', function($request, $response, $args){
	$getId = $request->getParam('id');
	$stmt = getConn()->query("UPDATE objeto SET estado = 'Excluido' WHERE codigo=$getId");
	print_r($stmt->rowCount());
});

$app->post('/restore/', function($request, $response, $args){
	$getId = $request->getParam('id');
	$stmt = getConn()->query("UPDATE objeto SET estado = 'Alocado' WHERE codigo=$getId");
});

//Editar objeto no BD
$app->post('/edit/', function($request, $response, $args){
	$codNovo = $request->getParam('codigo');
	$codAntigo = $request->getParam('codigoAntigo');
	$nome = $request->getParam('nome');
	$estado = $request->getParam('estado');
	$descricao = $request->getParam('descricao');
	$bloco = $request->getParam('bloco');
	$sala = $request->getParam('sala');
	$data_entrada = $request->getParam('data_entrada');
	$quem_recebeu = $request->getParam('recebeu');
	$nota = $request->getParam('nota');
	$unidade = $request->getParam('unidade');
	$conservacao = $request->getParam('conservacao');
	$empenho = $request->getParam('empenho');
	$foto = $request->getParam('foto');
	$fotoAntigo = $request->getParam('fotoAntigo');
	$imgDelete = $request->getParam('imgDelete');
	$imgRename = $request->getParam('imgRename');

	$db = getConn();
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);

	$stmt = $db->query("UPDATE objeto SET codigo = '$codNovo', nome = '$nome', estado = '$estado', data_entrada = '$data_entrada', bloco = '$bloco', sala = '$sala', quem_recebeu = '$quem_recebeu', nota = '$nota', unidade = '$unidade', descricao = '$descricao', foto = '$foto', conservacao = '$conservacao', empenho = '$empenho' WHERE codigo = '$codAntigo' ");
	$errors = $db->errorInfo();
	//$stmt = getConn()->query("DELETE FROM objeto WHERE codigo=$codAntigo");
	if($errors[0] == '00000'){
		if($imgDelete === 'true'){
			unlink("photos/$fotoAntigo");
			unlink("photos/thumbs/$fotoAntigo");
		} 
		if($imgRename === 'true'){
			rename("photos/$fotoAntigo","photos/$foto");
			rename("photos/thumbs/$fotoAntigo","photos/thumbs/$foto");
		}
	}
	//$stmt = getConn()->query("INSERT INTO objeto VALUES ('$codNovo','$nome','$dia','$mes','$ano','$local','$depto','$quem_recebeu','$nota','$descricao','$foto')");
	print_r($errors[0]);
});

//Obter todos os objetos do BD
$app->get('/listall/', function($request, $response, $args){
	$mode = $request->getParam('mode');
	$num_page = $request->getParam('num_page');
	$window_size = $request->getParam('window_size');
	//$stmt = getConn()->query("SELECT codigo, nome, dia, mes, ano, foto FROM objeto ORDER BY nome LIMIT $num_page,10");
	//$stmt = getConn()->query("SELECT * FROM objeto ORDER BY nome");
	if($mode === 'non_deleted'){
		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' ORDER BY data_entrada DESC LIMIT $num_page,$window_size");
		$objeto = $stmt->fetchAll(PDO::FETCH_OBJ);
		return json_encode($objeto);
	} else {
		if($mode === 'deleted'){
			$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado='Excluido'  ORDER BY data_entrada DESC LIMIT $num_page,$window_size");
			$objeto = $stmt->fetchAll(PDO::FETCH_OBJ);
			return json_encode($objeto);
		}
	}
	
});

$app->get('/filter/', function($request, $response, $args){
    $mode = $request->getParam('mode');
    $num_page = $request->getParam('num_page');
    $window_size = $request->getParam('window_size');
    $filter_params = json_decode($request->getParam('filter_params'), true);
    
    $name = $filter_params['name'];
    $unity = $filter_params['unity'];
    $bloco = $filter_params['bloco'];
    $sala = $filter_params['sala'];
    $dateStart = $filter_params['start_date'];
    $dateEnd = $filter_params['end_date'];
    $state = $filter_params['state'];

    if(!empty($unity)){
    	if(empty($bloco)){
    		$bloco = "%";
    	}
    	if(empty($sala)){
    		$sala = "%";
    	}
    }
    
    $stmt = null;

    if($mode === 'non_deleted'){
    	if(empty($name) && empty($unity) && empty($dateStart) && !empty($state)){
        	$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = '$state' LIMIT $num_page,$window_size ");
	    } else if(empty($name) && empty($unity) && !empty($dateStart) && empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
	    } else if(empty($name) && empty($unity) && !empty($dateStart) && !empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = '$state' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
	    } else if(empty($name) && !empty($unity) && empty($dateStart) && empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' AND (unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala') LIMIT $num_page,$window_size ");
	    } else if(empty($name) && !empty($unity) && empty($dateStart) && !empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = '$state' AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' LIMIT $num_page,$window_size ");
	    } else if(empty($name) && !empty($unity) && !empty($dateStart) && empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
	    } else if(empty($name) && !empty($unity) && !empty($dateStart) && !empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = '$state' AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
	    } else if(!empty($name) && empty($unity) && empty($dateStart) && empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' AND nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%' LIMIT $num_page,$window_size ");
	    } else if(!empty($name) && empty($unity) && empty($dateStart) && !empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = '$state' AND nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%' LIMIT $num_page,$window_size ");
	    } else if(!empty($name) && empty($unity) && !empty($dateStart) && empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
	    } else if(!empty($name) && empty($unity) && !empty($dateStart) && !empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = '$state' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
	    } else if(!empty($name) && !empty($unity) && empty($dateStart) && empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' LIMIT $num_page,$window_size ");
	    } else if(!empty($name) && !empty($unity) && empty($dateStart) && !empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = '$state' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' LIMIT $num_page,$window_size ");
	    } else if(!empty($name) && !empty($unity) && !empty($dateStart) && empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
	    } else if(!empty($name) && !empty($unity) && !empty($dateStart) && !empty($state)){
	        $stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = '$state' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
	    } else {
	    	$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' LIMIT $num_page,$window_size ");
	    }
	    $objeto = $stmt->fetchAll(PDO::FETCH_OBJ);
	    return json_encode($objeto);
    } else if($mode === 'deleted'){
    	if(empty($name) && empty($unity) && !empty($dateStart)){
    		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
    	} else if(empty($name) && !empty($unity) && empty($dateStart)){
    		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' AND (unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala') LIMIT $num_page,$window_size ");
    	} else if(empty($name) && !empty($unity) && !empty($dateStart)){
    		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
    	} else if(!empty($name) && empty($unity) && empty($dateStart)){
    		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' AND nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%' LIMIT $num_page,$window_size ");
    	} else if(!empty($name) && empty($unity) && !empty($dateStart)){
    		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
    	} else if(!empty($name) && !empty($unity) && empty($dateStart)){
    		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' LIMIT $num_page,$window_size ");
    	} else if(!empty($name) && !empty($unity) && !empty($dateStart)){
    		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' AND (nome LIKE '%{$name}%' OR descricao LIKE '%{$name}%') AND unidade LIKE '$unity' AND bloco LIKE '$bloco' AND sala LIKE '$sala' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_entrada DESC LIMIT $num_page,$window_size ");
    	} else {
    		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' LIMIT $num_page,$window_size ");
    	}
    	$objeto = $stmt->fetchAll(PDO::FETCH_OBJ);
	    return json_encode($objeto);
    }    
});

$app->get('/filterOperators/', function($request, $response, $args){
	$num_page = $request->getParam('num_page');
    $window_size = $request->getParam('window_size');
    $filter_params = json_decode($request->getParam('filter_params'), true);
    
    $name = $filter_params['name'];
    $dateStart = $filter_params['start_date'];
    $dateEnd = $filter_params['end_date'];
    $depto = $filter_params['depto'];

    $stmt = null;

    if(empty($name) && empty($dateStart) && !empty($depto)){
    	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto FROM operador WHERE admin = '0' AND depto LIKE '%{$depto}%' ORDER BY nome ASC LIMIT $num_page,$window_size ");
    } else if(empty($name) && !empty($dateStart) && empty($depto)){
    	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto FROM operador WHERE admin = '0' AND data_nasc BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_nasc DESC LIMIT $num_page,$window_size ");
    } else if(empty($name) && !empty($dateStart) && !empty($depto)){
    	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto FROM operador WHERE admin = '0' AND  depto LIKE '%{$depto}%' AND data_nasc BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_nasc DESC LIMIT $num_page,$window_size ");
    } else if(!empty($name) && empty($dateStart) && empty($depto)){
    	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto FROM operador WHERE admin = '0' AND nome LIKE '%{$name}%' ORDER BY nome ASC LIMIT $num_page,$window_size ");
    } else if(!empty($name) && empty($dateStart) && !empty($depto)){
    	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto FROM operador WHERE admin = '0' AND nome LIKE '%{$name}%' AND depto LIKE '%{$depto}%' ORDER BY nome ASC LIMIT $num_page,$window_size ");
    } else if(!empty($name) && !empty($dateStart) && empty($depto)){
    	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto FROM operador WHERE admin = '0' AND nome LIKE '%{$name}%' AND data_nasc BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_nasc DESC LIMIT $num_page,$window_size ");
    } else if(!empty($name) && !empty($dateStart) && !empty($depto)){
    	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto FROM operador WHERE admin = '0' AND nome LIKE '%{$name}%' AND depto LIKE '%{$depto}%' AND data_nasc BETWEEN '$dateStart' AND '$dateEnd' ORDER BY data_nasc DESC LIMIT $num_page,$window_size ");
    } else {
    	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto FROM operador WHERE admin = '0' LIMIT $num_page,$window_size ");
    }

    $objeto = $stmt->fetchAll(PDO::FETCH_OBJ);
    return json_encode($objeto);
});

$app->get('/searchId/', function($request, $response, $args){
	$mode = $request->getParam('mode');
	$getId = $request->getParam('id');

	if($mode === 'non_deleted'){
		$stmt = getConn()->query("SELECT * FROM objeto WHERE codigo=$getId AND estado <> 'Excluido' ");
		$objeto = $stmt->fetch(PDO::FETCH_OBJ);
		return json_encode($objeto);
	} else if($mode === 'deleted'){
		$stmt = getConn()->query("SELECT * FROM objeto WHERE codigo=$getId AND estado = 'Excluido' ");
		$objeto = $stmt->fetch(PDO::FETCH_OBJ);
		return json_encode($objeto);
	}
});


$app->get('/searchDate/', function($request, $response, $args){
	$mode = $request->getParam('mode');
	$dateStart = $request->getParam('date_start');
	$dateEnd = $request->getParam('date_end');
	$num_page = $request->getParam('num_page');
	$window_size = $request->getParam('window_size');
	if($mode === 'non_deleted'){
		$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado <> 'Excluido' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' LIMIT $num_page,$window_size ");
		$objeto = $stmt->fetchAll(PDO::FETCH_OBJ);
		return json_encode($objeto);
	} else {
		if($mode === 'deleted'){
			$stmt = getConn()->query("SELECT codigo, nome, data_entrada, foto FROM objeto WHERE estado = 'Excluido' AND data_entrada BETWEEN '$dateStart' AND '$dateEnd' LIMIT $num_page,$window_size ");
	$objeto = $stmt->fetchAll(PDO::FETCH_OBJ);
	return json_encode($objeto);
		}
	}
	
});

//Salvar imagem do objeto no servidor
$app->post('/uploadImg/', function($request, $response, $args){
	$nome = $request->getParam('nome');
	$imagem = $request->getParam('img');
	$imagemThumb = $request->getParam('imgThumb');
	if(file_put_contents("photos/$nome", base64_decode($imagem))){
		if(file_put_contents("photos/thumbs/$nome", base64_decode($imagemThumb))){
			print_r("1");
		} else {
			print_r("0");
		}
	} else {
		print_r("0");
	}
});


/* Funcoes dos operadores */

//Adicionar operador ao BD
$app->post('/addOperator/',function($request, $response, $args){

	$nome = $request->getParam('nome');
	$email = $request->getParam('email');
	$senha = $request->getParam('senha');
	$senha_crip = password_hash($senha,PASSWORD_DEFAULT);
	$data_nasc = $request->getParam('data_nasc');
	$depto = $request->getParam('depto');

	$db = getConn();
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);

	$stmt = $db->query("INSERT INTO operador VALUES (NULL,'$nome','$email','$senha_crip','$data_nasc','$depto',NULL,'0')");
	$errors = $db->errorInfo();
	print_r($errors[0]);
});

//Editar operador no BD
$app->post('/editOperator/',function($request, $response, $args){
	
	$nome = $request->getParam('nome');
	$emailAntigo = $request->getParam('emailAntigo');
	$emailNovo = $request->getParam('email');
	$senha = $request->getParam('senha');
	$senha_crip = password_hash($senha,PASSWORD_DEFAULT);
	$data_nasc = $request->getParam('data_nasc');
	$depto = $request->getParam('depto');
	$alterarSenha = $request->getParam('alterarSenha');

	//$stmt = getConn()->query("INSERT INTO operador WHERE email=$emailAntigo");

	$db = getConn();
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);

	if($alterarSenha == 'true'){
		$stmt = $db->query("UPDATE operador SET nome = '$nome', email = '$emailNovo', senha = '$senha_crip', data_nasc = '$data_nasc', depto = '$depto' WHERE email = '$emailAntigo' ");
	} else if($alterarSenha == 'false'){
		$stmt = $db->query("UPDATE operador SET nome = '$nome', email = '$emailNovo', data_nasc = '$data_nasc', depto = '$depto' WHERE email = '$emailAntigo' ");
	}
	$errors = $db->errorInfo();

	print_r($errors[0]);
});

//Deletar operador do BD
$app->post('/deleteOperator/',function($request, $response, $args){
	$email = $request->getParam('email');
	$stmt = getConn()->query("DELETE FROM operador WHERE email='$email'");
	print_r($stmt->rowCount());
});

//Obter operador do BD
$app->get('/getOperator/',function($request, $response, $args){
	$id = $request->getParam('id');
	$stmt = getConn()->query("SELECT nome, email, senha, data_nasc, depto, admin FROM operador WHERE id=$id");
	$operador = $stmt->fetch(PDO::FETCH_OBJ);
	if($operador == FALSE){
		return $response->withStatus(401);
	}else{
		return json_encode($operador);
	}
});

//Listar todos os operadores
$app->get('/listOperators/',function($request, $response, $args){
	$num_page = $request->getParam('num_page');
	$window_size = $request->getParam('window_size');

	$stmt = getConn()->query("SELECT * FROM operador WHERE admin = '0' ORDER BY nome ASC LIMIT $num_page,$window_size");
	$operadores = $stmt->fetchAll(PDO::FETCH_OBJ);
	return json_encode($operadores);
});


//Validar dados de login
$app->post('/validateLogin/', function($request, $response, $args){
	$email = $request->getParam('email');
	$senha = $request->getParam('senha');

	$stmt = getConn()->query("SELECT id, email, senha FROM operador WHERE email='$email'");
	$operador = $stmt->fetch();

	if($operador != null){
		$senha_bd = $operador['senha'];
		if(password_verify($senha,$senha_bd)){

			$auth['idUser'] = $operador['id'];
			$auth['token'] = bin2hex(openssl_random_pseudo_bytes(8));
			updateToken($auth['idUser'], $auth['token']);

			return json_encode($auth);
		}else{
			return "0";
		}
	}else{
		return "0";
	}
});

$app->post('/clearUserToken/', function($request, $response, $args){
	$tokenAuth = $request->getHeader('Authorization');
	$auth = $tokenAuth[0];
	$stmt = getConn()->query("UPDATE operador SET token = '' WHERE token = '$auth' ");
	echo "OK";
});

function updateToken($userId, $token){
	$stmt = getConn()->query("UPDATE operador SET token = '$token' WHERE id = '$userId'");
}

//Obter metadados do banco
$app->get('/getMetadata/', function($request, $response, $args){
	$user_id = $request->getParam('user_id');

	//Obter numero de objetos cadastrados pelo usuario
	$stmt = getConn()->query("SELECT COUNT(codigo) FROM objeto WHERE operador_id = '$user_id'");
	$num_obj_per_user = $stmt->fetchColumn();

	//Obter numero total de objetos cadastrados no banco
	$stmt = getConn()->query("SELECT COUNT(codigo) FROM objeto");
	$num_obj = $stmt->fetchColumn();

	//Obter numero de objetos cadastrados no ultimo mes
	$stmt = getConn()->query("SELECT COUNT(codigo) FROM objeto WHERE EXTRACT(MONTH FROM tempo_cadastro) = MONTH(CURRENT_DATE) ");
	$num_obj_month = $stmt->fetchColumn();

	//echo($num_obj);

	$jsonObj = json_encode(array('num_obj_user' => $num_obj_per_user, 'num_obj' => $num_obj, 'num_obj_month' => $num_obj_month), JSON_FORCE_OBJECT);

	return $jsonObj;

});

$app->get('/idle/', function($request, $response, $args){
	
});


$app->run();
?>