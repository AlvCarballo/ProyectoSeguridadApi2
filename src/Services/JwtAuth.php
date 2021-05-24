<?php
namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth{
    public $manager;
    public $key;

    public function __construct($manager){
        $this->manager = $manager;
        //Ponemos la key que queramos
        $this->key='eJpdRBXasEmq_RZYAhokJm8Q5fhAj_FqiSuYgT2R4';
    }

    public function signup($uemail, $upassword, $gettoken = null){
        //Comprobar si el usuario existe
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'uemail' => $uemail,
            'upassword' => $upassword
        ]);
        $signup=false;
        if(is_object($user)){
            $signup=true; //Si el usuario coincide ponemos la variable en true
        }
        //Si existe, generar el token de jwt
        if($signup){
            $token=[
                'uid' => $user-> getUid(),
                'uemail' => $user-> getUemail(),
                'urole' => $user-> getUrole(),
                'unombre' => $user-> getUnombre(),
                'uapellidos' => $user-> getUapellidos(),
                'utelefono' => $user-> getUtelefono(),
                'udireccion' => $user-> getUdireccion(),
                'ucreatedAt' => $user-> getUcreatedAt(),
                'udeleteAt' => $user-> getUdeleteAt(),
                'iat' =>time(),
                'exp' =>time() + (7 * 24 * 60 * 60)
            ];
            //comprobar el flag gettoken, condicion
            $jwt = JWT::encode($token, $this->key, 'HS256');
            if(!empty($gettoken)){
                $data =$jwt;
            }else{
                $decoded = JWT::decode($jwt, $this->key, ['HS256']);
                $data=$decoded;
            }
        }else{
            $data=[
                'message' => 'error',
                'code'=> 200,
                'path' => 'Login Incorrecto'
            ];
        }
        //devolver los datos
        return  $data;
    }
    public function checkToken($jwt, $identity=false){
        $auth=false;
        try{
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        }
        catch(\UnexpectedValueException $e){ $auth=false;}
        catch(\DomainException $e){ $auth=false;}
        if(isset($decoded) && !empty($decoded) && is_object($decoded) && isset($decoded->uid)){
            $auth=true;
        }else{
            $auth=false;
        }
        if($identity != false){
            return $decoded;
        }else{
            return $auth;
        }
    }
}