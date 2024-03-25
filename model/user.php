<?php declare(strict_types=1);

namespace OsumiFramework\App\Model;

use OsumiFramework\OFW\DB\OModel;
use OsumiFramework\OFW\DB\OTable;
use OsumiFramework\OFW\DB\OTableField;

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
