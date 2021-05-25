<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\User;
use App\Entity\Comentario;
use App\Services\JwtAuth;

class ComentarioController extends AbstractController
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
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ComentarioController.php',
        ]);
    }
    public function create(Request $request, JwtAuth $jwt_auth){

        //Recoger el token
        $token = $request ->headers->get('Authorization');
        //Comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);
        if($authCheck){
             //Recogerdatos por post
            $json = $request->get('json', null);
            $params = json_decode($json);
        }
        
        //Recoger el objeto del usuario identificado
        $identity=$jwt_auth->checkToken($token, true);
        //Comprobar y validar datos
        
        if(!empty($json)){
            $coidusuariofk = ($identity->uid != null) ? $identity->uid:null;
            $cotitle = (!empty($params->cotitle)) ? $params->cotitle:null;
            $cocomentario = (!empty($params->cocomentario)) ? $params->cocomentario:null;
            $copagina = (!empty($params->copagina)) ? $params->copagina:null;
            
            if(!empty($coidusuariofk) && !empty($cotitle) && !empty($cocomentario) && !empty($copagina)){
               
                //Indicamos que el campo coidusuariofk es uid
                $em = $this->getDoctrine()->getManager();
                $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['uid'=>$coidusuariofk]);
                //Crear y guardar objeto
                $comentario= new Comentario();
                 //Asignar nuevos datos al objeto usuario
                 $comentario->setCoidusuariofk($user);
                 $comentario->setcotitle($cotitle);
                 $comentario->setcocomentario($cocomentario);
                 $comentario->setcopagina($copagina);
                 $comentario->setCocreatedAt(new \Datetime('now'));
                 $comentario->setCodeleteAt(new \Datetime('1900-01-01 00:00:00'));
                 //Guardar en la BD
                 $em->persist($comentario);
                 $em->flush();
                 $data=[
                     'message' => 'sucess',
                     'code'=> 200,
                     'path' => 'Comentario creado correctamente',
                     'comentario' => $comentario
                 ];
            }else{
                //Respuesta por defecto
            $data=[
                'message' => 'Error',
                'code'=> 400,
                'path' => 'El comentario no ha podido crearse'
                ];
            }
        }
        //Devolver una respuesta
        return $this->resjson($data);
    }

    public function listComentarios (Request $request, PaginatorInterface $paginator){
        $data=[
            'message' => 'Error',
            'code'=> 400,
            'path' => 'El comentario no ha podido listarse'
            ];
        $em = $this->getDoctrine()->getManager();
        //recorrer el parametro page de la url
        $page = $request->query->getInt('page', 1);
        $items_per_page= $request->query->getInt('itemspage', 5);
        $pagina=$request->query->get('pagina', null);
        //Hacer la consulta para paginar
        if($pagina !=null){
            $dql="SELECT c FROM App\Entity\Comentario c WHERE c.copagina={$pagina} ORDER BY c.cocreatedAt DESC";
            
        }else{
            $dql="SELECT c FROM App\Entity\Comentario c ORDER BY c.cocreatedAt DESC";
        }
        //Ejecutar la consulta
        $query = $em->createQuery($dql);
        
        //Invocar paginacion
        $pagination= $paginator->paginate($query, $page, $items_per_page);
        $total = $pagination->getTotalItemCount();
        //Preparar el array de datos a devolver
        $data=[
            'message' => 'success',
            'code'=> 200,
            'total_items_count' => $total,
            'page_actual' => $page,
            'items_per_page' => $items_per_page,
            'total_pages' => ceil($total / $items_per_page),
            'comentarios' => $pagination
            ];
        //Devolver una respuesta
        return $this->resjson($data);
    }
    public function listComentariosUser (Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator){
        //Recoger el token
        $token = $request ->headers->get('Authorization');
        //Comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);
        //si es valido
        if($authCheck){
            //Conseguir la identidad del usuario
            $identity=$jwt_auth->checkToken($token, true);
            $em = $this->getDoctrine()->getManager();
            //recorrer el parametro page de la url
            $page = $request->query->getInt('page', 1);
            $items_per_page= $request->query->getInt('itemspage', 5);
            $pagina=$request->query->get('pagina');
            //Hacer la consulta para paginar
            if($pagina !=null){
                $dql="SELECT c FROM App\Entity\Comentario c WHERE c.coidusuariofk={$identity->uid} AND c.copagina={$pagina} ORDER BY c.cocreatedAt DESC";
            }else{
                $dql="SELECT c FROM App\Entity\Comentario c WHERE c.coidusuariofk={$identity->uid} ORDER BY c.cocreatedAt DESC";
            }
            //Ejecutar la consulta
            $query = $em->createQuery($dql);
            //Invocar paginacion
            $pagination= $paginator->paginate($query, $page, $items_per_page);
            $total = $pagination->getTotalItemCount();
            //Preparar el array de datos a devolver
            $data=[
                'message' => 'success',
                'code'=> 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'comentarios' => $pagination,
                'uid' => $identity->uid
                ];
        }else{
            //Respuesta por defecto
        $data=[
            'message' => 'Error',
            'code'=> 400,
            'path' => 'El comentario no ha podido listarse'
            ];
        }
        //Devolver una respuesta
        return $this->resjson($data);
    }
}
