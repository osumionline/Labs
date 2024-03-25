<?php declare(strict_types=1);

namespace OsumiFramework;

require_once('./core/defines.php');

use OsumiFramework\OFW\DB\OModel;
use OsumiFramework\OFW\DB\OTable;
use OsumiFramework\OFW\DB\OTableField;
use OsumiFramework\OFW\DB\ODBContainer;
use OsumiFramework\App\Model\User;

$user = new User();

/*
// Asignación de campos
$user->nombre = "prueba";
$user->apellidos = "lalala";
$user->auxiliar = "aux";
$user->otro = "otro?";

// Guardar datos
$user->save();

echo "<pre>\n";
var_dump($user);
echo "</pre>\n";
exit();
*/

// Búsqueda
try {
	$user->find(['id' => 1]);
	$user->nombre = 'Otro';
	$user->save();
}
catch (Exception $e) {
	echo "<pre>\n";
	var_dump($e);
	echo "<pre><br>\n";
}
echo "<pre>\n";
var_dump($user);
echo "</pre>\n";
exit();

/*
// Carga de un modelo completo
$user->update([
	'id' => 1,
	'nombre' => 'Iñigo',
	'apellidos' => 'Gorosabel',
	'created_at' => '1981-07-02 03:00:00',
	'updated_at' => '2023-09-14 23:12:00'
]);

echo "<pre>\n";
var_dump($user);
echo "</pre>\n";
exit();
*/

/*
// Borrado
$user->delete();
*/
