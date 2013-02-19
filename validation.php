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
        $_POST['PROCESSING_REASON_CODE'] == SkrillPsp::processing_reason_code_ok_e &&
        $skrillpsp->saveVADBTransaction($_POST))
        die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/success.php?cart_id=' . $cart_ids[0]);

    die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/error.php?cart_id=' . $cart_ids[0]);
    }
elseif ($_POST['PAYMENT_CODE'] == SkrillPsp::payment_code_rg_e)
    {
    if ($_POST['PROCESSING_RESULT'] == Skrillpsp::validation_result_ok_e &&
        $_POST['PROCESSING_STATUS_CODE'] == SkrillPsp::processing_status_code_ok_e &&
        $_POST['PROCESSING_REASON_CODE'] == SkrillPsp::processing_reason_code_ok_e &&
        $skrillpsp->preauthorizeRequest($_POST))
        {
        if ($skrillpsp->getRedirectUrl())
            die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/redirect.php?cart_id=' . $cart_ids[0]);

        die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/success.php?cart_id=' . $cart_ids[0]);
        }

    die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/error.php?cart_id=' . $cart_ids[0]);
    }
elseif ($_POST['PAYMENT_CODE'] == SkrillPsp::payment_code_pa_e ||
        $_POST['PAYMENT_CODE'] == SkrillPsp::payment_code_db_e)
    {
    die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/error.php?cart_id=' . $cart_ids[0]);
    }
else
    {
    $xml = simplexml_load_string(urldecode($_POST['response']));
    $cart_ids = explode("_", (string)current($xml->xpath('Transaction/Identification/TransactionID')));
    if ($skrillpsp->verify3DSResponse(urldecode($_POST['response'])))
        {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/success.php?cart_id=' . $cart_ids[0]);
        exit;
        }
    header('Location: https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/error.php?cart_id=' . $cart_ids[0]);
    exit;
    }

die('https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . 'skrillpsp/error.php');
    