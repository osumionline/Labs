<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

use Attribute;

#[Attribute]
class OTableField {
	public function __construct(
		public int     $type,
		public mixed   $default = null,
		public bool    $incr = true,
		public string | null $comment = null
	) {}
}
