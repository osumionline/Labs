<?php declare(strict_types=1);

define('OMODEL_PK', 1);
define('OMODEL_PK_STR', 10);
define('OMODEL_CREATED', 2);
define('OMODEL_UPDATED', 3);
define('OMODEL_NUM', 4);
define('OMODEL_TEXT', 5);
define('OMODEL_DATE', 6);
define('OMODEL_BOOL', 7);
define('OMODEL_LONGTEXT', 8);
define('OMODEL_FLOAT', 9);

date_default_timezone_set('Europe/Madrid');

require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/../db/otable.class.php');
require_once(dirname(__FILE__).'/../db/otable.field.class.php');
require_once(dirname(__FILE__).'/../db/omodel.class.php');
require_once(dirname(__FILE__).'/../db/odb.container.class.php');
require_once(dirname(__FILE__).'/../db/odb.class.php');
require_once(dirname(__FILE__).'/../db/omodel.field.class.php');
require_once(dirname(__FILE__).'/../db/omodel.field.bool.class.php');
require_once(dirname(__FILE__).'/../db/omodel.field.date.class.php');
require_once(dirname(__FILE__).'/../db/omodel.field.float.class.php');
require_once(dirname(__FILE__).'/../db/omodel.field.num.class.php');
require_once(dirname(__FILE__).'/../db/omodel.field.text.class.php');
require_once(dirname(__FILE__).'/../model/user.php');
require_once(dirname(__FILE__).'/ocore.class.php');

use OsumiFramework\OFW\Core\OCore;

$core = new OCore();
