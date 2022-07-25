<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use Exception;
use PHPUnit\Util\Json;



class Prueba extends ResourceController
{
    
    protected $format = 'json';


    //Funcion para crear un formato de respuesta (data:response, mensaje:error, code:status de peticion)
    private function genericResponse($data, $msj, $code)
    {

        if ($code == 200) {
            return $this->respond(array(
                "data" => $data,
                "code" => $code
            )); //, 404, "No hay nada"
        } else {
            return $this->respond(array(
                "msj" => $msj,
                "code" => $code
            ));
        }
    }

    //Funcion para Consultar los productos, ordena los productos en base a las ventas
    public function products()
    {
        Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
        Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
        Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

        $url="https://sandbox.ixaya.net/api/products";
        $opciones = array(
            "http" => array(
                "header" => "X-API-KEY: 0ck00sgcscwsgoso4skos0kwsk40w04cwsgccgkg",
                "method" => "GET",
            ),
        );
        # Preparar petición
        $contexto = stream_context_create($opciones);
        # Hacerla
        $resultado = file_get_contents($url, false, $contexto);
        if ($resultado === false) {
            echo "Error haciendo petición";
            exit;
        }
        $json = [];
        $json = json_decode($resultado, true);
        $productos = $json["response"]; 
        helper("array");
        array_sort_by_multiple_keys($productos, [
            'sale_count' => SORT_DESC,
        ]);
        $firstFive=[];
        for ($i=0; $i < 5; $i++) { 
            $firstFive[$i]=$productos[$i];
        }
        return $this->genericResponse($firstFive, "", 200);
    }
 
    //Funcion para Obtener las ordenes creadas con mi usuario
    public function orders()
    {
        Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
        Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
        Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

        $url="https://sandbox.ixaya.net/api/orders/orders";
        $opciones = array(
            "http" => array(
                "header" => "X-API-KEY: 0ck00sgcscwsgoso4skos0kwsk40w04cwsgccgkg",
                "method" => "GET",
            ),
        );
        # Preparar petición
        $contexto = stream_context_create($opciones);
        # Hacerla
        $resultado = file_get_contents($url, false, $contexto);
        if ($resultado === false) {
            echo "Error haciendo petición";
            exit;
        }
        $json = [];
        $json = json_decode($resultado, true);
        $ordenes = $json["response"]; 
        $totalSum=0;
        foreach ($ordenes as $orden) {
            $totalSum+=$orden["total"];
        }
        $convertido=$this->convertir($totalSum);
        $totalJson=[];
        $totalJson['ordenes']=$ordenes;
        $totalJson['total']=$totalSum;
        $totalJson['totalConvertido']=$convertido;
        return $this->genericResponse($totalJson, "", 200);
    }

    //Funcion para filtrar por fecha las ordenes
    public function ordersByDate()
    {
        Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
        Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
        Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        
        $jsonData=$this->request->getPost("fecha");
        
        $url="https://sandbox.ixaya.net/api/orders/orders";
        $opciones = array(
            "http" => array(
                "header" => "X-API-KEY: 0ck00sgcscwsgoso4skos0kwsk40w04cwsgccgkg",
                "method" => "GET",
            ),
        );
        # Preparar petición
        $contexto = stream_context_create($opciones);
        # Hacerla
        $resultado = file_get_contents($url, false, $contexto);
        if ($resultado === false) {
            echo "Error haciendo petición";
            exit;
        }
        $json = [];
        $json = json_decode($resultado, true);
        $ordenes = $json["response"]; 
        $totalSum=0;
        $ordenesFiltradas=[];
        $index=0; 
        
        foreach ($ordenes as $orden) {
            if(substr($orden['last_update'],0,10)==$jsonData){
                $ordenesFiltradas[$index]=$orden;
                $index++;
            }
        }

        $totalSum=0;
        foreach ($ordenesFiltradas as $orden2) {
            $totalSum+=$orden2["total"];
        }

        $convertido=$this->convertir($totalSum);
        $totalJson=[];
        $totalJson['ordenes']=$ordenesFiltradas;
        $totalJson['total']=$totalSum;
        $totalJson['totalConvertido']=$convertido;

        return $this->genericResponse($totalJson, "", 200);
    }

    //Funcion para crear una nueva orden
    public function create()
    {
        Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
        Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
        Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        try {
            $jsonData=$this->request->getJSON();
            $url="https://sandbox.ixaya.net/api/orders/create";
            $opciones = array(
                "http" => array(
                    "header" => "Content-type: application/json\r\n".
                                "X-API-KEY: 0ck00sgcscwsgoso4skos0kwsk40w04cwsgccgkg\r\n",
                    "method" => "POST",
                    "content" => json_encode($jsonData),
                ),
            );

            # Preparar petición
            $contexto = stream_context_create($opciones);
            # Hacerla
            $resultado = file_get_contents($url, false, $contexto);
            if ($resultado === false) {
                echo "Error haciendo petición";
                exit;
            }

            $json = [];
            $json = json_decode($resultado, true);
            $res = $json["response"]; 
            
            
            return $this->genericResponse($res, "", 200);
        } catch (Exception $th) {
            return $this->genericResponse("", $th, 500);
        }
        
    }


    //Funcion para realizar la conversion del total, a DLS, EUROS, BOLIVARES
    public function convertir($total){   
        try {
            
            $curl = curl_init();
    
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://currency-converter5.p.rapidapi.com/currency/convert?format=json&from=MXN&to=USD%2CEUR%2CVES&amount=".$total."&language=es",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "X-RapidAPI-Host: currency-converter5.p.rapidapi.com",
                    "X-RapidAPI-Key: 23ff8e2ea4mshc72a6b1f50aa543p1976cdjsnd6616db7de1b"
                ],
            ]);
    
            $response = curl_exec($curl);
            $err = curl_error($curl);
    
            curl_close($curl);
            $json = [];
            $json = json_decode($response, true);
            $res = $json["rates"]; 
            return $res;
        } catch (Exception $th) {
            return NULL;
        }   
       

        
    }

    
    //Funcion para obtener una orden por su ID
    public function order_id()
    {
        Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
        Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
        Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        try {
            $jsonData=$this->request->getJSON();
            $url="https://sandbox.ixaya.net/api/orders/detail";
            $opciones = array(
                "http" => array(
                    "header" => "Content-type: application/json\r\n".
                                "X-API-KEY: 0ck00sgcscwsgoso4skos0kwsk40w04cwsgccgkg\r\n",
                    "method" => "POST",
                    "content" => json_encode($jsonData),
                ),
            );

            # Preparar petición
            $contexto = stream_context_create($opciones);
            # Hacerla
            $resultado = file_get_contents($url, false, $contexto);
            if ($resultado === false) {
                echo "Error haciendo petición";
                exit;
            }
            
            $json = [];
            $json = json_decode($resultado, true);
            $order = $json["response"]; 
            
            return $this->genericResponse($order, "", 200);
        } catch (Exception $th) {
            return $this->genericResponse("", $th, 500);
        }
        
    }
    


}