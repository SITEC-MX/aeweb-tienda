<?php
/**
 * Sistemas Especializados e Innovación Tecnológica, SA de CV
 * SiTEC AE - Administrador de Empresas Web
 *
 * v.1.0.0.0 - 2022-03-03
 */

date_default_timezone_set('America/Mexico_City');

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(["AEWEB_EMPRESA", "AEWEB_TOKEN"])->notEmpty();

define("__APP__", __DIR__ . "/app");
define("__CACHE__", __DIR__ . "/.cache");

$CFG = new stdClass();
$CFG->aeweb_empresa = $_ENV["AEWEB_EMPRESA"];
$CFG->aeweb_token = $_ENV["AEWEB_TOKEN"];

$CFG->activar_cache = strtolower($_ENV["ACTIVAR_CACHE"]) == "true";
