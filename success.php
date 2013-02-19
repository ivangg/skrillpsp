<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/skrillpsp.php');

$mysmarty = Context::getContext()->smarty;
$skrillpsp = new SkrillPsp();

$mycart_id = $_GET['cart_id'];
$mysmarty->assign('redirect_url', $skrillpsp->getSuccessUrl($mycart_id));
echo $mysmarty->fetch(_PS_MODULE_DIR_ . 'skrillpsp/views/templates/front/success.tpl');
