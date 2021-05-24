<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;

use App\Entity\User;
use App\Entity\Comentario;
use App\Services\JwtAuth;

class UserController extends AbstractController
{
    private function resjson($data){
        //Serializar datos con servicio serializer
        $json=$this->get('serializer')->serialize($data, 'json');
        //Response con httpfoundation
        $response=new Response();
        //Asignar contenido a la respuesta
        $response->setContent($json);
        //Indicar formato a la respuesta
        $response->headers->set('Content-Type','application/json');
        //Devolver la respuesta
        return $response;
    }

    public function index()
    {

        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $comentario_repo = $this->getDoctrine()->getRepository(Comentario::class);

        $users = $user_repo->findAll();
        $user =  $user_repo->find(1);
        $comentarios = $comentario_repo->findAll();

        $data=[
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ];
        // foreach($users as $user){
        //     echo "<h1>{$user->getUnombre()} {$user->getUapellidos()}</h1>";
        //     foreach($user->getComentarios() as $comentario){
        //         echo "<p>{$comentario->getCotitle()} {$comentario->getCoidusuariofk()->getUemail()}</p>";
        //     }
        // }
        // die();

        return $this->json($comentarios);
    }
    public function create(Request $request){
        //Recojer los datos por post
        $json = $request->get('json', null);
        //Decodificar json
        //$params= json_decode($json, true);
        $params= json_decode($json);
        //Respuesta por defecto
        $data=[
            'message' => 'Error',
            'code'=> 200,
            'path' => 'El usuario no se ha creado'
        ];
        //Comprobar y validad datos
        if($json !=null){
            $uemail = (!empty($params->uemail)) ? $params->uemail:null;
            $upassword = (!empty($params->upassword)) ? $params->upassword:null;
            $urole = (!empty($params->urole)) ? $params->urole:null;
            $unombre = (!empty($params->unombre)) ? $params->unombre:null;
            $uapellidos = (!empty($params->uapellidos)) ? $params->uapellidos:null;
            $utelefono = (!empty($params->utelefono)) ? $params->utelefono:null;
            $udireccion = (!empty($params->udireccion)) ? $params->udireccion:null;
            $ucreatedAt = (!empty($params->ucreatedAt)) ? $params->ucreatedAt:null;
            $udeleteAt = (!empty($params->udeleteAt)) ? $params->udeleteAt:null;

            $validator = Validation::createValidator();
            $validate_email=$validator->validate($uemail, [new Email()]);

            if(!empty($uemail) 
            && count($validate_email)==0 
            && !empty($upassword)
            && !empty($urole)
            && !empty($unombre)
            && !empty($uapellidos)
            && !empty($utelefono)
            && !empty($udireccion)){
                //Si la verificacion es correcta, crear el opjeto usuario
                $user = new User ();
                $user->setUemail($uemail);
                $user->setUrole($urole);
                $user->setUnombre($unombre);
                $user->setUapellidos($uapellidos);
                $user->setUtelefono($utelefono);
                $user->setUdireccion($udireccion);
                $user->setUcreatedAt(new \Datetime('now'));
                $user->setUdeleteAt(new \Datetime('1900-01-01 00:00:00'));
                //Cifrar contraseña
                $pwd = hash('sha256', $upassword);
                $user -> setUpassword($pwd);
                //Comprobar si el usuario existe en la BD
                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();
                $user_repo = $doctrine->getRepository(User::Class);
                $isset_user = $user_repo->findBy(array(
                    'uemail'=> $uemail
                ));
                //Si no existe, Guardarlo en la BD
                if(count($isset_user)==0){
                    //Guardamos el usuario
                    $em->persist($user);
                    $em->flush();
                    $data=[
                        'message' => 'sucess',
                        'code'=> 200,
                        'path' => 'Usuario creado correctamente'
                    ];
                }else{
                    $data=[
                        'message' => 'error',
                        'code'=> 400,
                        'path' => 'El usuario ya existe'
                    ];
                }
            }else{
                //Indicamos que no paso la validacion de datos
                $data=[
                    'message' => 'sucess',
                    'code'=> 200,
                    'path' => 'Validacion Incorrecta'
                ];
            }
        }
       

        //Hacer respuesta en json
        //return $this->resjson($data);
        return new JsonResponse($data);
    }
    public function login(Request $request, JwtAuth $jwt_auth){
        //Recojer los datos por post
        $json = $request->get('json', null);
        //Descodificar json
        $params= json_decode($json);
        //Respuesta por defecto
        $data=[
            'message' => 'error',
            'code'=> 200,
            'path' => 'El usuario no se ha identificado'
        ];
        //Comprobar y validad datos
        if($json !=null){
            $uemail = (!empty($params->uemail)) ? $params->uemail:null;
            $upassword = (!empty($params->upassword)) ? $params->upassword:null;
            $gettoken = (!empty($params->gettoken)) ? $params->gettoken:null;
        }
        $validator = Validation::createValidator();
        $validate_email=$validator->validate($uemail, [new Email()]);

        if(!empty($uemail) && count($validate_email)==0  && !empty($upassword)){
            //Cifrar contraseña
            $pwd = hash('sha256', $upassword);
            //Si todo es valido, llamaremos a un servicio para identificar al usuario (jwt, token o un objeto)
            if($gettoken){
                $signup = $jwt_auth->signup($uemail, $pwd, $gettoken);
            }
            else{
                $signup = $jwt_auth->signup($uemail, $pwd);
            }
            //Hacer respuesta en json
            return new JsonResponse($signup);
        }
    }
    public function edit (Request $request, JwtAuth $jwt_auth){
        //Recoger cabecera de autentificacion
        $token = $request ->headers->get('Authorization');
        //Respuesta por defecto
        $data=[
            'message' => 'error',
            'code'=> 400,
            'path' => 'USUARIO No actualizado'
        ];
        //Crear metodo para comprobar si el token es correcto
        $authCheck = $jwt_auth->checkToken($token);
        //Si es correcto hacer la actualizacion del usuario
        if($authCheck){
        //Actualizar al usuario
            //Consegir entity manager
            $em=$this->getDoctrine()->getManager();
            //Consegir los datos del usuario identificado
            $identity=$jwt_auth->checkToken($token, true);
            //Consegir el usuario a actualizar completo
            $user_repo = $this->getDoctrine()->getRepository(User::class);
            $user = $user_repo->findOneBy([
                'uid'=> $identity->uid
            ]);
            //Recoger dato por post
            $json = $request->get('json', null);
            $params = json_decode($json);
            //comprobar y validar datos
            if(!empty($json)){
                $uemail = (!empty($params->uemail)) ? $params->uemail:null;
                $upassword = (!empty($params->upassword)) ? $params->upassword:null;
                $urole = (!empty($params->urole)) ? $params->urole:null;
                $unombre = (!empty($params->unombre)) ? $params->unombre:null;
                $uapellidos = (!empty($params->uapellidos)) ? $params->uapellidos:null;
                $utelefono = (!empty($params->utelefono)) ? $params->utelefono:null;
                $udireccion = (!empty($params->udireccion)) ? $params->udireccion:null;
                $ucreatedAt = (!empty($params->ucreatedAt)) ? $params->ucreatedAt:null;
                $udeleteAt = (!empty($params->udeleteAt)) ? $params->udeleteAt:null;
    
                $validator = Validation::createValidator();
                $validate_email=$validator->validate($uemail, [new Email()]);
    
                if(!empty($uemail) 
                && count($validate_email)==0 
                && !empty($upassword)
                && !empty($urole)
                && !empty($unombre)
                && !empty($uapellidos)
                && !empty($utelefono)
                && !empty($udireccion)){
                    //Asignar nuevos datos al objeto usuario
                    $user->setUemail($uemail);
                    $user->setUrole($urole);
                    $user->setUnombre($unombre);
                    $user->setUapellidos($uapellidos);
                    $user->setUtelefono($utelefono);
                    $user->setUdireccion($udireccion);
                   
                    //Cifrar contraseña
                    $pwd = hash('sha256', $upassword);
                    $user -> setUpassword($pwd);
                    //Comprobar duplicados
                    $isset_user = $user_repo->findBy([
                        'uemail'=> $uemail
                    ]);
                    if(count($isset_user)==0 || $identity->uemail ==$uemail){
                         //Guardar cambios en la BD
                        $em->persist($user);
                        $em->flush();
                        $data=[
                            'message' => 'sucess',
                            'code'=> 200,
                            'path' => 'Usuario actualizado correctamente',
                            'user' => $user
                        ];
                    }else{
                        $data=[
                            'message' => 'error',
                            'code'=> 400,
                            'path' => 'No puedes usar ese email'
                        ];
                    } 
                }
            }else{
                $data=[
                    'message' => 'errorj',
                    'code'=> 400,
                    'path' => 'USUARIO No actualizado'
                ];
            }
        }
        return $this->resjson($data);
    }
}
