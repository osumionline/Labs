<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

use Attribute;

#[Attribute]
class OTableField {
	public function __construct(
		public int           $type,
		public mixed         $default  = null,
		public bool          $incr     = true,
		public int | null    $size     = null,
		public bool          $nullable = true,
		public string | null $comment  = null,
		public string | null $ref      = null,
		public string | null $set      = null,
		public string | null $name     = null
	) {}
}
