<?php
/**
 * Sistemas Especializados e Innovación Tecnológica, SA de CV
 * SiTEC AE - Administrador de Empresas Web
 *
 * v.1.0.0.0 - 2022-03-03
 */

 function ObtenerDefinicionOpenAPI():array
 {
     $openapi = array();

     $app_openapi_ruta = __APP__ . "/openapi.json";
     $app_openapi_str = file_get_contents($app_openapi_ruta);
     $app_openapi = json_decode($app_openapi_str, TRUE);

     if($app_openapi) // Si hay OpenAPI de APP
     {
        foreach($app_openapi as $ao) // Para cada OpenAPI de APP
        {
            $oa_url = $ao["url"];
            $oa_php = $ao["php"];
            $oa_tipo = isset($ao["tipo"]) ? $ao["tipo"] : "text/html";

            $oa_variables = NULL;
            preg_match_all('/\{(?<variables>\w+)\}/', $oa_url, $oa_variables);

            $variables = array();
            foreach($oa_variables["variables"] as $variable)
            {
                $variables[$variable] = 6;
            }

            $oa_url_remplazada = preg_replace('/\{(\w+)\}/', '(?<$1>[a-zA-Z0-9+_.-]+)', $oa_url);
            $url_corregida = "/^{$oa_url_remplazada}$/U";

            $openapi[$url_corregida] = array
            (
                "script_php_ruta"=>$oa_php,
                "variables"=>$variables,
                "metodos"=>array
                (
                    "GET"=>array
                    (
                        "autenticar"=>FALSE, "get"=>array(), "body"=>array(), "body_tipo"=>$oa_tipo, "respuesta"=>array(), "respuesta_tipo"=>"text/html"
                    )
                )
            );
        }
     }

     return $openapi;
 }

 function ObtenerURLActual():string
 {
     $LLAMADASOLICITADA = substr($_SERVER["REQUEST_URI"], 1); // Sin / al inicio
     $INDICE_PARAMETRO = strpos($LLAMADASOLICITADA, "?"); // Quitamos los parámetros recibidos por $_GET
     if($INDICE_PARAMETRO !== FALSE) // Si hay parámetros
     {
         $LLAMADASOLICITADA = substr($LLAMADASOLICITADA, 0, $INDICE_PARAMETRO);
     }

     return $LLAMADASOLICITADA;
 }

 function CargarVariablesDeRequest():bool
 {
     global $OPENAPI_REQUEST;

     $exito = TRUE;

     foreach($OPENAPI_REQUEST["variable"] as $variable_nombre=>$variable_valor) // Para cada variable de la URL
     {
        switch($variable_nombre)
        {
            case "hreflang":
                $filtros = array
                (
                    array("campo"=>"activo", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1),
                    array("campo"=>"url", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>$variable_valor)
                );

                $exito_variable = CargarVariableDeRequest("HREFLANG", "id,nombre", $filtros);
                break;

            default: // Variable no soportada
                $exito_variable = FALSE;
        }

        if(!$exito_variable) // Error al cargar la variable
        {
            $exito = FALSE;
            break;
        }
     }

     return $exito;
 }

 function CargarVariableDeRequest(string $global_nombre, string $campos, array $filtros):bool
 {
     global $AEWEB;

     $exito = FALSE;

     $body = array();
     $body["inicio"] = 1;
     $body["registros"] = 1;
     $body["campos"] = $campos;
     $body["filtro"] = $filtros;

     $estado = NULL;
     switch($global_nombre)
     {
         case "HREFLANG": $estado = $AEWEB->POST_TiendaHreflangsQuery(NULL, NULL, $body); break;
     }

     if($estado["estado"] == OK && $estado["resultado"]["filtrados"] == 1)
     {
         $GLOBALS[$global_nombre] =  $estado["resultado"]["registros"][0];

         $exito = TRUE;
     }

     return $exito;
 }