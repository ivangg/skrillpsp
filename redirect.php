<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/skrillpsp.php');

$cart_id = $_GET['cart_id'];
$skrillpsp = new SkrillPsp();
$skrillpsp->fetch3DSRedirectdata($cart_id);
$skrillpsp->context->smarty->assign(array('redirecturl' => $skrillpsp->getRedirectUrl(),
                                        'redirectparams' => $skrillpsp->getRedirectParams()));
echo $skrillpsps->context->smarty->fetch(_PS_MODULE_DIR_ . '/skrillpsp/views/templates/front/redirect.tpl');
