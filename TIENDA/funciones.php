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

            // Componemos la / por \/
            $oa_url = str_replace("/", "\/", $oa_url);

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

                $exito_variable = CargarVariableDeRequest("HREFLANG", array("id", "nombre"), $filtros);
                break;

            case "presentacion":
                $filtros = array
                (
                    array("campo"=>"activo", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1),
                    array("campo"=>"url", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>$variable_valor)
                );

                $exito_variable = CargarVariableDeRequest("PRESENTACION", array("id", "producto_nombre", "nombre"), $filtros);
                break;

            case "categoria":
                $filtros = array
                (
                    array("campo"=>"activo", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1),
                    array("campo"=>"url", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>$variable_valor)
                );

                $exito_variable = CargarVariableDeRequest("CATEGORIA", array("id","nombre"), $filtros);
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

 function CargarVariableDeRequest(string $global_nombre, array $campos, array $filtros):bool
 {
     $exito = FALSE;

     $registros = NULL;
     switch($global_nombre)
     {
         case "HREFLANG": $registros = ObtenerHreflangs($campos, 1, 1, $filtros); break;
         case "PRESENTACION": $registros = ObtenerPresentaciones($campos, 1, 1, $filtros); break;
         case "CATEGORIA": $registros = ObtenerCategorias($campos, 1, 1, $filtros); break;
     }

     if($registros) // Éxito al obtener el registro
     {
         $GLOBALS[$global_nombre] =  $registros;

         $exito = TRUE;
     }

     return $exito;
 }

 function ObtenerHreflangs(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL):?array
 {
    return ObtenerDatos("HREFLANG", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento);
 }

 function ObtenerPresentaciones(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL):?array
 {
     return ObtenerDatos("PRESENTACIONES", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento);
 }

 function ObtenerCategorias(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL):?array
 {
     return ObtenerDatos("CATEGORIAS", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento);
 }

 function ObtenerDatos(string $entidad, array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL):?array
 {
     global $AEWEB;

     $body = array();
     $body["inicio"] = $inicio;
     $body["registros"] = $numero_de_registros;
     $body["campos"] = $campos;

     if($filtros)
     {
         $body["filtro"] = $filtros;
     }

     if($ordenamiento)
     {
         $body["ordenamiento_campos"] = $ordenamiento;
     }

     $estado = NULL;
     switch($entidad)
     {
         case "HREFLANGS": $estado = $AEWEB->POST_TiendaHreflangsQuery(NULL, NULL, $body); break;
         case "PRESENTACIONES": $estado = $AEWEB->POST_InventarioPresentacionesQuery(NULL, NULL, $body); break;
         case "CATEGORIAS": $estado = $AEWEB->POST_InventarioCategoriasQuery(NULL, NULL, $body); break;
     }

     $registros = NULL;
     if($estado["estado"] == OK) // Éxito al obtener los datos
     {
         $registros =  $estado["resultado"]["registros"];
     }

     return $registros;
 }

 function ObtenerURLImagenChicaPresentacion(?string $presentacion_url):string
 {
     global $CFG;

     $url = NULL;

     if($presentacion_url) // Si hay imagen de presentación
     {
         $pathinfo = pathinfo($presentacion_url);

         $empresa = $CFG->aeweb_empresa;
         $dirname = $pathinfo["dirname"];
         $filename_original = $pathinfo["filename"];

         $filename_chica = str_replace('imagen-', 'imagen-240x320-', $filename_original);

         $url = "https://archivo.aeweb.app/{$empresa}/{$dirname}/{$filename_chica}.webp";
     }
     else // Si no hay imagen de presentación
     {
         $url = "/app/assets/img/sin-imagen.png";
     }

     return $url;
 }