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
            $oa_tipo_body = isset($ao["tipo_body"]) ? $ao["tipo_body"] : "application/json";
            $oa_tipo_respuesta = isset($ao["tipo_respuesta"]) ? $ao["tipo_respuesta"] : "text/html";
            $oa_querystring = isset($ao["querystring"]) ? $ao["querystring"] : array();
            $oa_body = isset($ao["body"]) ? $ao["body"] : array();
            $oa_metodo = isset($ao["metodo"]) ? $ao["metodo"] : "GET";

            // Componemos la / por \/
            $oa_url = str_replace("/", "\/", $oa_url);

            // Variables de URL
            $oa_variables = NULL;
            preg_match_all('/\{(?<variables>\w+)\}/', $oa_url, $oa_variables);
            $oa_url_remplazada = preg_replace('/\{(\w+)\}/', '(?<$1>[a-zA-Z0-9+_.-]+)', $oa_url);
            $url_corregida = "/^{$oa_url_remplazada}$/U";

            $variables = array();
            foreach($oa_variables["variables"] as $variable)
            {
                $variables[$variable] = array("tipo"=>FDW_DATO_STRING);
            }

            // Query-string
            $querystring = array();
            foreach($oa_querystring as $oa_qs)
            {
                $oa_qs_nombre = $oa_qs["nombre"];
                $oa_qs_tipo = isset($oa_qs["tipo"]) ? $oa_qs["tipo"] : FDW_DATO_STRING;
                $oa_qs_default = isset($oa_qs["default"]) ? $oa_qs["default"] : NULL;

                $querystring[$oa_qs_nombre] = array("tipo"=>$oa_qs_tipo, "default"=>$oa_qs_default);
            }

            // Body
            $body = array();
            foreach($oa_body as $oa_b)
            {
                $oa_b_nombre = $oa_b["nombre"];
                $oa_b_tipo = isset($oa_b["tipo"]) ? $oa_b["tipo"] : FDW_DATO_STRING;
                $oa_b_default = isset($oa_b["default"]) ? $oa_b["default"] : NULL;

                $body[$oa_b_nombre] = array("tipo"=>$oa_b_tipo, "default"=>$oa_b_default);
            }

            $oam_definicion = array("autenticar"=>FALSE, "get"=>$querystring, "body"=>$body, "body_tipo"=>$oa_tipo_body, "respuesta"=>array(), "respuesta_tipo"=>$oa_tipo_respuesta);

            if( !isset($openapi[$url_corregida]) ) // Si la llamada no está definida
            {
                $openapi[$url_corregida] = array
                (
                    "script_php_ruta"=>$oa_php,
                    "variables"=>$variables,
                    "metodos"=>array
                    (
                        "{$oa_metodo}" => $oam_definicion
                    )
                );
            }
            else // Si la llamada ya existe
            {
                $openapi[$url_corregida][$oa_metodo] = $oam_definicion;
            }
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
                    array("campo"=>"url", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>$variable_valor)
                );

                $exito_variable = CargarVariableDeRequest("HREFLANG", array("id", "nombre"), $filtros);
                break;

            case "presentacion":
                $filtros = array
                (
                    array("campo"=>"url", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>$variable_valor)
                );

                $exito_variable = CargarVariableDeRequest("PRESENTACION", array("id", "codigo", "producto_nombre", "nombre", "producto_resumen", "producto_informacion", "imagenprincipal_url", "marca_nombre", "marca_url"), $filtros);
                break;

            case "categoria":
                $filtros = array
                (
                    array("campo"=>"url", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>$variable_valor)
                );

                $exito_variable = CargarVariableDeRequest("CATEGORIA", array("id","nombre"), $filtros);
                break;

            case "marca":
                $filtros = array
                (
                    array("campo"=>"url", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>$variable_valor)
                );

                $exito_variable = CargarVariableDeRequest("MARCA", array("id","nombre"), $filtros);
                break;

            default: // Variable no soportada
                $exito_variable = CargarVariableDeRequestExterna($variable_nombre, $variable_valor);
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

     $imagen_tipo = 2; // AE_IMAGENOPTIMIZADA_TIPO_HORIZONTAL
     $imagen_tamano = 2; // AE_IMAGENOPTIMIZADA_TAMANO_GRANDE

     $registros = NULL;
     switch($global_nombre)
     {
         case "HREFLANG": $registros = ObtenerHreflangs($campos, 1, 1, $filtros); break;
         case "PRESENTACION": $registros = ObtenerPresentaciones($campos, 1, 1, $filtros, NULL, $total_registros, $imagen_tipo, $imagen_tamano); break;
         case "CATEGORIA": $registros = ObtenerCategorias($campos, 1, 1, $filtros); break;
         case "MARCA": $registros = ObtenerMarcas($campos, 1, 1, $filtros); break;
     }

     if($registros) // Éxito al obtener el registro
     {
         $GLOBALS[$global_nombre] =  $registros[0];

         $exito = TRUE;
     }

     return $exito;
 }

 function ObtenerHreflangs(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
 {
     return ObtenerDatos("HREFLANG", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros);
 }

 function ObtenerPresentaciones(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL, ?int $imagen_tipo = NULL, ?int $imagen_tamano = NULL):?array
 {
    // Agregamos los filtos que siempre deberían proporcionarse
    if(!$filtros) // Si no se proporcionan filtros
    {
        $filtros = array();
    }

    $filtros[] = array("campo"=>"activo", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);
    $filtros[] = array("campo"=>"publicado", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);

    return ObtenerDatos("PRESENTACIONES", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros, $imagen_tipo, $imagen_tamano);
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

 function ObtenerMarcas(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL, ?int $imagen_tipo = NULL, ?int $imagen_tamano = NULL):?array
 {
     // Agregamos los filtos que siempre deberían proporcionarse
    if(!$filtros) // Si no se proporcionan filtros
    {
        $filtros = array();
    }

    $filtros[] = array("campo"=>"activo", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);
    $filtros[] = array("campo"=>"publicado", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1);

    return ObtenerDatos("MARCAS", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros, $imagen_tipo, $imagen_tamano);
 }

 function ObtenerPrecios(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
 {
     return ObtenerDatos("PRECIOS", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros);
 }

 function ObtenerExistencias(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
 {
     return ObtenerDatos("EXISTENCIAS", $campos, $inicio, $numero_de_registros, $filtros, $ordenamiento, $total_de_registros);
 }

 function ObtenerPresentacionesConPrecioExistencia(array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL):?array
 {
     $presentaciones = ObtenerPresentaciones
    (
        $campos, // Campos
        $inicio, // Inicio
        $numero_de_registros, // Número de registros
        $filtros, // Filtros
        $ordenamiento, // Ordenamiento
        $total_de_registros // Conteo de registros
    );

     // Obtenemos los precios
     $presentacion_ids = array();
     foreach($presentaciones as $presentacion)
     {
         $presentacion_ids[] = $presentacion["id"];
     }
     $presentacion_ids_str = implode($presentacion_ids, ",");

     $precios = ObtenerPrecios
     (
         array("presentacion_id", "precio"), // Campos
         1, // Inicio
         1000, // Número de registros
         array // Filtros
         (
             array("campo"=>"presentacion_id", "operador"=>FDW_DATO_BDD_OPERADOR_IN, "valor"=>$presentacion_ids_str),
             array("campo"=>"esquemaprecio_id", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1)
         ),
         NULL, // Ordenamiento
     );

     $precios_presentacion = array();
     foreach($precios as $precio) // Para cada precio
     {
         $presentacion_id = $precio["presentacion_id"];

         $precios_presentacion[$presentacion_id] = $precio["precio"];
     }

     // Obtenemos las existencias
     $existencias = ObtenerExistencias
     (
         array("presentacion_id", "existencia"), // Campos
         1, // Inicio
         1000, // Número de registros
         array // Filtros
         (
             array("campo"=>"presentacion_id", "operador"=>FDW_DATO_BDD_OPERADOR_IN, "valor"=>$presentacion_ids_str),
             array("campo"=>"almacen_id", "operador"=>FDW_DATO_BDD_OPERADOR_IGUAL, "valor"=>1)
         ),
         NULL, // Ordenamiento
     );

     $existencias_presentacion = array();
     foreach($existencias as $existencia) // Para cada precio
     {
         $presentacion_id = $existencia["presentacion_id"];

         $existencias_presentacion[$presentacion_id] = $existencia["existencia"];
     }

     // Inyectamos la información
     foreach($presentaciones as $indice=>$presentacion) // Para cada presentación
     {
         $presentacion_id = $presentacion["id"];

         $precio = isset($precios_presentacion[$presentacion_id]) ? $precios_presentacion[$presentacion_id] : NULL;
         $existencia = isset($existencias_presentacion[$presentacion_id]) ? $existencias_presentacion[$presentacion_id] : 0;

         $presentaciones[$indice]["precio"] = $precio;
         $presentaciones[$indice]["existencia"] = $existencia;
     }

     return $presentaciones;
 }

 function ObtenerDatos(string $entidad, array $campos, int $inicio, int $numero_de_registros, ?array $filtros = NULL, ?array $ordenamiento = NULL, ?int &$total_de_registros = NULL, ?int $imagen_tipo = NULL, ?int $imagen_tamano = NULL):?array
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

     if($imagen_tipo || $imagen_tamano) // Si se proporciona configuración de imágenes
     {
         $body["imagen"] = array("tipo"=>$imagen_tipo, "tamano"=>$imagen_tamano);
     }

     $estado = NULL;
     switch($entidad)
     {
         case "HREFLANGS": $estado = $AEWEB->POST_TiendaHreflangsQuery(NULL, NULL, $body); break;
         case "PRESENTACIONES": $estado = $AEWEB->POST_InventarioPresentacionesQuery(NULL, NULL, $body); break;
         case "CATEGORIAS": $estado = $AEWEB->POST_InventarioCategoriasQuery(NULL, NULL, $body); break;
         case "MARCAS": $estado = $AEWEB->POST_InventarioMarcasQuery(NULL, NULL, $body); break;
         case "PRECIOS": $estado = $AEWEB->POST_ContabilidadPrecioQuery(NULL, NULL, $body); break;
         case "EXISTENCIAS": $estado = $AEWEB->POST_InventarioExistenciaQuery(NULL, NULL, $body); break;
     }

     $registros = NULL;
     if($estado["estado"] == OK) // Éxito al obtener los datos
     {
         $registros =  $estado["resultado"]["registros"];
         $total_de_registros = $estado["resultado"]["filtrados"];
     }

     return $registros;
 }

 function ObtenerProductosConCategoria(int $categoria_id):array
 {
    global $AEWEB;

    $variables = array("id"=>$categoria_id);

    $estado = $AEWEB->GET_InventarioCategoriasProductos($variables);

    $producto_ids = array();
    if($estado["estado"] == OK) // Éxito al obtener los productos con la categoría
    {
        $producto_ids = $estado["resultado"];
    }

    return $producto_ids;
 }