<?php declare(strict_types=1);

namespace OsumiFramework\OFW\Core;

use OsumiFramework\OFW\DB\ODBContainer;

class OCore {
  public ?ODBContainer $dbContainer = null;

  public function __construct() {
    $this->dbContainer = new ODBContainer();
  }
}
