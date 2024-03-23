<?php declare(strict_types=1);

namespace OsumiFramework;

require_once('defines.php');
require_once('config.php');
require_once('otable.class.php');
require_once('otable.field.class.php');
require_once('omodel.class.php');
require_once('odb.container.class.php');
require_once('odb.class.php');

use OsumiFramework\OFW\DB\OModel;
use OsumiFramework\OFW\DB\OTable;
use OsumiFramework\OFW\DB\OTableField;
use OsumiFramework\OFW\DB\ODBContainer;

$db_container = new ODBContainer();

#[OTable(name: "user")]
class User extends OModel {
	#[OTableField(
		type: OMODEL_PK,
		comment: "Id del usuario"
	)]
	protected int | null $id;

	#[OTableField(
		type: OMODEL_TEXT,
		comment: "Nombre del usuario"
	)]
	protected string | null $nombre;

	#[OTableField(
		type: OMODEL_TEXT,
		comment: "Apellidos del usuario"
	)]
	protected string | null $apellidos;

	#[OTableField(
		type: OMODEL_CREATED,
		comment: "Fecha de creación del registro"
	)]
	protected \DateTime | null $created_at;

	#[OTableField(
		type: OMODEL_UPDATED,
		comment: "Fecha de última modificación del registro"
	)]
	protected \DateTime | null $updated_at;

	public string $auxiliar;
}

$user = new User();

// Asignación de campos
$user->nombre = "prueba";
$user->apellidos = "lalala";
$user->auxiliar = "aux";
$user->otro = "otro?";

// Guardar datos
//$user->save();

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
//exit();

// Búsqueda
try {
	$user->find(['id' => 3, 'nombre' => 'pako']);
}
catch (Exception $e) {
	echo "<pre>\n";
	var_dump($e);
	echo "<pre><br>\n";
}
exit();
// Borrado
$user->delete();
