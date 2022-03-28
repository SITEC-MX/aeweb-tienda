<?php
/**
 * Sistemas Especializados e Innovación Tecnológica, SA de CV
 * Mpsoft.FDW - Framework de Desarrollo Web para PHP
 *
 * v.2.0.0.0 - 2022-03-03
 */

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/configuracion.php";

use \Mpsoft\FDW\Core\OpenAPI;

$OPENAPI = ObtenerDefinicionOpenAPI();
$OPENAPI_REQUEST = OpenAPI::ObtenerLlamadaSolicitada($OPENAPI);

// Verificamos si la llamada está disponible en cache
$CACHE_ARCHIVO_RUTA = NULL;
if($OPENAPI_REQUEST && $CFG->activar_cache) // Si el cache está activado
{
    $variable_str = http_build_query($OPENAPI_REQUEST["variable"]);
    $querystring_str = http_build_query($OPENAPI_REQUEST["get"]);

    $base = $OPENAPI_REQUEST["script_php_ruta"];
    $querystring = $querystring_str ? md5($querystring_str) : "";
    $variable = $variable_str ? md5($variable_str) : "";

    $CACHE_ARCHIVO_RUTA = __CACHE__ . "/{$base}_{$variable}_{$querystring}.html";

    if( file_exists($CACHE_ARCHIVO_RUTA) ) // Si el archivo está disponible en cache
    {
        echo file_get_contents($CACHE_ARCHIVO_RUTA);
        die;
    }
}



// Calculamos la URL actual
$URL_BASE = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"];
$URL_ACTUAL =  $URL_BASE . $_SERVER["REQUEST_URI"];

if($OPENAPI_REQUEST && count($OPENAPI_REQUEST["get"])>0) // Si hay parámetros recibidos por QueryString
{
    $querystring_str = http_build_query($OPENAPI_REQUEST["get"]);

    $URL_ACTUAL .= "?{$querystring_str}";
}



$app_php_script = NULL;
$app_codigo_error = NULL;
$AEWEB = new \Mpsoft\AEWeb\AEWeb($CFG->aeweb_empresa, $CFG->aeweb_token, "tienda");

if($OPENAPI_REQUEST) // Si es una llamada definida
{
    $variables_cargadas_correctamente = CargarVariablesDeRequest();

    if($variables_cargadas_correctamente) // Éxito al cargar las variables solicitadas
    {
        $app_php_script = $OPENAPI_REQUEST["script_php_ruta"];
    }
    else // Error al cargar las variables solicitadas
    {
        $app_codigo_error = 404;
    }
}
else // Si la llamada no está definida
{
    $app_codigo_error = 404;
}

if(!$app_codigo_error) // Si no se está procesando ningún error
{
    $php_script_ruta = __APP__ . "/{$app_php_script}.php";

    ob_start();

    require_once $php_script_ruta;

    $html = ob_get_contents();
    ob_end_clean();

    if($CFG->activar_cache) // Si el cache está activado
    {
        file_put_contents($CACHE_ARCHIVO_RUTA, $html);
    }

    echo $html;
}
else // Si se está procesando un error
{
    switch($app_codigo_error)
    {
        case 404:
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", TRUE, 404);
            break;
    }

    $php_script_ruta = __APP__ . "/{$app_codigo_error}.php";
    require_once $php_script_ruta;
}
