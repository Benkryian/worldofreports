<?php

require_once("Authentication.php");

class Payments extends Connection
{

    private $conn;

    function __construct()
    {
        $this -> conn = $this -> getConnection();
        $this -> errors = array();
    }

    public function generateReference($user_id, $report_id) {
        $longref =  md5(time() . $user_id. PASSWORD_SALT . $report_id. time() .uniqid());
        $ref = strtoupper(substr(str_shuffle($longref),0,16));
        $query = "SELECT * FROM wor_transactions WHERE transaction_reference = :transaction_reference";
        try {
            $stmt = $this -> conn -> prepare($query);
            $stmt -> execute(["transaction_reference" => $ref]);
            if($stmt -> rowCount() == 0) {
                return $ref;
            } else {
                $this -> generateReference($user_id, $report_id);
            }
        } catch (PDOException $e) {
            $error = new ErrorMaster();
            $error -> reportError($e);
        }

    }

    public function updateOrder ($responseArray) {
        $query = "UPDATE wor_transactions SET status = :status, pesapal_transaction_tracking_id = :pesapal_transaction_tracking_id, payment_method = :payment_method WHERE transaction_reference = :transaction_reference";
        try {
            $stmt = $this -> conn -> prepare($query);
            //i live on the wild side
            $stmt -> execute([
                "status" => strtolower($responseArray["status"]),
                "pesapal_transaction_tracking_id" => $responseArray["pesapal_transaction_tracking_id"],
                "payment_method" => $responseArray["payment_method"],
                "transaction_reference" => $responseArray["pesapal_merchant_reference"]
                ]);
            if($stmt -> rowCount() == 1) {
                return true;
            } else {
                return false;
            }
         } catch (PDOException $e) {
            $error = new ErrorMaster();
            $error -> reportError($e);
        }
    }

    public function storeOrder($ref, $user_id, $report_id, $amount) {
        $query = "INSERT INTO wor_transactions (
            transaction_reference,
            status,
            transaction_user_id,
            transaction_report_id,
            transaction_date,
            transaction_amount
        ) VALUES (
            :transaction_reference,
            :status,
            :transaction_user_id,
            :transaction_report_id,
            :transaction_date,
            :transaction_amount
        )";

        try {
            $stmt = $this -> conn -> prepare($query);
            $stmt -> execute([
                "transaction_reference" => $ref,
                "status" => "placed",
                "transaction_user_id" => $user_id,
                "transaction_report_id" => $report_id,
                "transaction_date" => date("Y-m-d H:i:s"),
                "transaction_amount" => $amount
            ]);
            if($stmt -> rowCount() == 1) {
                return true;
            } else {
                return false;
            }

        } catch (PDOException $e) {
            $error = new ErrorMaster();
            $error -> reportError($e);
        }

    }

    public function ownsThis($reportId, $userId) {
        $query = "SELECT * FROM wor_transactions WHERE transaction_report_id = :transaction_report_id AND transaction_user_id = :transaction_user_id";
        try {
            $stmt = $this -> conn -> prepare($query);
            $stmt -> execute([
                "transaction_report_id" => $reportId,
                "transaction_user_id" => $userId
            ]);
            if($stmt -> rowCount() == 1) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            $error = new ErrorMaster();
            $error -> reportError($e);
        }
    }

}


?>
