<?php
class GCM
{
    function __construct()
    {

    }

    public function send($to, $message)
    {
        $fields = array(
            'to' => $to,
            'data' => $message
        );
        return $this->sendPushNotification($fields);
    }

    public function sendToGroup($registration_ids, $message)
    {
        $fields = array(
            'registration_ids' => $registration_ids,
            'data' => $message
        );
        return $this->sendPushNotification($fields);
    }

    public function sendMultiple($registration_ids, $message)
    {
        $fields = array(
            'registration_ids' => $registration_ids,
            'data' => $message
        );

        return $this->sendPushNotification($fields);
    }

    private function sendPushNotification($fields)
    {

        // include config
        include_once __DIR__ . '/../../include/config.php';

        // Set POST variables
        $url = 'https://gcm-http.googleapis.com/gcm/send';

        $headers = array(
            'Authorization: key=' . GCM,
            'Content-Type: application/json'
        );
        // Open connection
        $ch      = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }

        // Close connection
        curl_close($ch);

        return $result;
    }

}

?>
