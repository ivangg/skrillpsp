<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/skrillpsp.php');

$skrillpsp = new SkrillPsp();

$logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/status.log', "a+");
fprintf($logh, "%s %s %s\n", SkrillPsp::payment_code_qc_e, SkrillPsp::payment_code_rg_e, $_POST['PAYMENT_CODE']);
fprintf($logh, "%s\n\n\n", var_export($_POST, true));
fclose($logh);

// id_cart _ datetime _ secure_key
$cart_ids = split("_", $_POST['IDENTIFICATION_TRANSACTIONID']);

if ($_POST['PAYMENT_CODE'] == SkrillPsp::payment_code_qc_e)
    {
    if ($_POST['PROCESSING_RESULT'] == Skrillpsp::validation_result_ok_e &&
        $_POST['PROCESSING_STATUS_CODE'] == SkrillPsp::processing_status_code_ok_e &&
        $_POST['PROCESSING_REASON_CODE'] == SkrillPsp::processing_reason_code_ok_e)
        {
        $cart = new Cart((int)$cart_ids[0]);
	/*if (!$cart->id)
	    $errors = $skrillpsp->l('Your shopping cart is empty!') . '<br />';
	elseif (Order::getOrderByCartId((int)($cart_ids[0])))
	    $errors = $paypal->l('Your order has already been placed').'<br />';
	else
            $skrillpsp->validateOrder((int)$cart_ids[0], Configuration::get('PS_OS_PAYMENT'), (float)($_POST['PRESENTATION_AMOUNT']),
                    $skrillpsp->displayName, $skrillpsp->l('Skrill Transaction ID: ') . $_POST['IDENTIFICATION_TRANSACTIONID'],
                    array('transaction_id' => $_POST['IDENTIFICATION_TRANSACTIONID'],
                    'payment_status' => $_POST['PROCESSING_RETURN']), null, false, $cart_ids[2]);*/

        echo 'https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/success.php';
        exit;
        }

    echo 'https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/error.php';
    }
elseif ($_POST['PAYMENT_CODE'] == SkrillPsp::payment_code_rg_e)
    {
    if ($_POST['PROCESSING_RESULT'] == Skrillpsp::validation_result_ok_e &&
        $_POST['PROCESSING_STATUS_CODE'] == SkrillPsp::processing_status_code_ok_e &&
        $_POST['PROCESSING_REASON_CODE'] == SkrillPsp::processing_reason_code_ok_e)
        {
        $skrillpsp->preauthorizeRequest($_POST);
        $skrillpsp->cookie->ScartID = (int)$cart_ids[0];
        die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/success.php?cart_id=' . $cart_ids[0]);
        }

    die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/error.php');
    }

echo 'https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/success.php';
