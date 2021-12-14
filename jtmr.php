<?php 
/**
 * Plugin Name: JTMR features
 * Description: A magyar jezsuitákhoz kapcsolódó honlapokban közös egyedi lehetőségek
 * Version: 0.1.3
 * Requires at least: 4.0
 * Requires PHP: 5.6
 * Author: Elek László SJ
 * Text Domain: JTMR
 */
 
require 'plugin-update-checker-4.11/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/borazslo/wp-plugin-jtmr',
	__FILE__, //Full path to the main plugin file or functions.php.
	'jezsuitak-theme'
);
//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

$modules = ['webgalamb'];
foreach($modules as $module) 
	include_once($module.'.php');

?>
  