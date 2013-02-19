<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/skrillpsp.php');

$cart_id = $_GET['cart_id'];
$skrillpsp = new SkrillPsp();
$mysmarty = Context::getContext()->smarty;
$skrillpsp->fetch3DSRedirectdata($cart_id);
$mysmarty->assign(array('redirecturl' => $skrillpsp->getRedirectUrl(),
                                        'redirectparams' => $skrillpsp->getRedirectParams()));
echo $mysmarty->fetch(_PS_MODULE_DIR_ . 'skrillpsp/views/templates/front/redirect3ds.tpl');
