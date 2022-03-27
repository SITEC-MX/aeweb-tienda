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
            $oa_querystring = isset($ao["querystring"]) ? $ao["querystring"] : array();

            // Componemos la / por \/
            $oa_url = str_replace("/", "\/", $oa_url);

            $oa_variables = NULL;
            preg_match_all('/\{(?<variables>\w+)\}/', $oa_url, $oa_variables);

            $variables = array();
            foreach($oa_variables["variables"] as $variable)
            {
                $variables[$variable] = array("tipo"=>FDW_DATO_STRING);
            }

            $querystring = array();
            foreach($oa_querystring as $oa_qs)
            {
                $oa_qs_nombre = $oa_qs["nombre"];
                $oa_qs_tipo = isset($oa_qs["tipo"]) ? $oa_qs["tipo"] : FDW_DATO_STRING;
                $oa_qs_default = isset($oa_qs["default"]) ? $oa_qs["default"] : NULL;

                $querystring[$oa_qs_nombre] = array("tipo"=>$oa_qs_tipo, "default"=>$oa_qs_default);
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
                        "autenticar"=>FALSE, "get"=>$querystring, "body"=>array(), "body_tipo"=>$oa_tipo, "respuesta"=>array(), "respuesta_tipo"=>"text/html"
                    )
                )
            );
        }
     }

     return $openapi;
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

 function ObtenerHreflangs(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
 {
     return ObtenerDatos("HREFLANG", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros);
 }

 function ObtenerPresentaciones(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
 {
    // Agregamos los filtos que siempre deberían proporcionarse
    if(!$filtros) // Si no se proporcionan filtros
    {
        $filtros = array();
    }

    $filtros[] = array("campo"=>"activo", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);
    $filtros[] = array("campo"=>"publicado", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);

    return ObtenerDatos("PRESENTACIONES", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros);
 }

 function ObtenerCategorias(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
 {
     // Agregamos los filtos que siempre deberían proporcionarse
     if(!$filtros) // Si no se proporcionan filtros
     {
         $filtros = array();
     }

     $filtros[] = array("campo"=>"activo", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);
     $filtros[] = array("campo"=>"publicado", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);

     return ObtenerDatos("CATEGORIAS", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros);
 }

 function ObtenerMarcas(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
 {
     // Agregamos los filtos que siempre deberían proporcionarse
    if(!$filtros) // Si no se proporcionan filtros
    {
        $filtros = array();
    }

    $filtros[] = array("campo"=>"activo", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);
    $filtros[] = array("campo"=>"publicado", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);

     return ObtenerDatos("MARCAS", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros);
 }

 function ObtenerDatos(string $entidad, array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
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
         case "MARCAS": $estado = $AEWEB->POST_InventarioMarcasQuery(NULL, NULL, $body); break;
     }

     $registros = NULL;
     if($estado["estado"] == OK) // Éxito al obtener los datos
     {
         $registros =  $estado["resultado"]["registros"];
         $total_de_registros = $estado["resultado"]["filtrados"];
     }

     return $registros;
 }

 function ObtenerURLImagenChicaPresentacion(string $tipo, ?string $presentacion_url, ?bool $imagen_en_repositorio_auxiliar):?string
 {
     global $CFG;

     $ancho = NULL;
     $alto = NULL;

     switch($tipo)
     {
         case "horizontal": $ancho = 240; $alto = 320; break;
         case "vertical": $ancho = 320; $alto = 240; break;
         case "cuadrado": $ancho = 320; $alto = 320; break;
         default:
             throw new Exception("Tipo de imagen no soportada.");
     }

     $url = NULL;

     if($presentacion_url) // Si hay imagen de presentación
     {
         $pathinfo = pathinfo($presentacion_url);

         $empresa = $CFG->aeweb_empresa;
         $dirname = $pathinfo["dirname"];
         $filename_original = $pathinfo["filename"];

         $filename_chica = str_replace('imagen-', "imagen-{$ancho}x{$alto}-", $filename_original);
         $repositorio_host = $imagen_en_repositorio_auxiliar ? "archivo-aux" : "archivo";

         $url = "https://{$repositorio_host}.aeweb.app/{$empresa}/{$dirname}/{$filename_chica}.webp";
     }

     return $url;
 }