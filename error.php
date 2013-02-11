<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/skrillpsp.php');

$mysmarty = Context::getContext()->smarty;
$skrillpsp = new SkrillPsp();

$mysmarty->assign('redirect_url', $skrillpsp->getErrorUrl());
echo $mysmarty->fetch(_PS_MODULE_DIR_ . '/skrillpsp/views/templates/front/error.tpl');
