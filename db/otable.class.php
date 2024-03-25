<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

use Attribute;

#[Attribute]
class OTable {
	public function __construct(public string $name) {}
}
