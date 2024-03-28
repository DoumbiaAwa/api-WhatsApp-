<?php

require './vendor\autoload.php';

use GreenApi\RestApi\GreenApiClient;


define("ID_INSTANCE", "7103900959");
define("API_TOKEN_INSTANCE", "26ee0f3777d543b9a7ad417409a8d887a86d08212baf49ac97");

$greenApi = new GreenApiClient(ID_INSTANCE, API_TOKEN_INSTANCE);


$greenApi->webhooks->startReceivingNotifications(function ($typeWebhook, $body) use ($greenApi) {

    if ($typeWebhook == 'incomingMessageReceived') {

        onIncomingMessageReceived($body,$greenApi);
    } 

    elseif ($typeWebhook == 'deviceInfo') {

        onDeviceInfo($body);
    } 
    elseif ($typeWebhook == 'incomingCall') {
        onIncomingCall($body);
    } 
    elseif ($typeWebhook == 'outgoingAPIMessageReceived') {
        onOutgoingAPIMessageReceived($body);
    }
     elseif ($typeWebhook == 'outgoingMessageReceived') {
        onOutgoingMessageReceived($body);
    } 
    elseif ($typeWebhook == 'outgoingMessageStatus') {
        onOutgoingMessageStatus($body);
    } 
    elseif ($typeWebhook == 'stateInstanceChanged') {
        onStateInstanceChanged($body);
    } 
    elseif ($typeWebhook == 'statusInstanceChanged') {
        onStatusInstanceChanged($body);
    }
});


function sendFileByUpload(
    string $chatId, string $path, string $fileName = null, string $caption = null, string $quotedMessageId = null
): stdClass {

    if ( ! $fileName ) {
        $fileName = basename( $path );
    }

    $requestBody = [
        'chatId' => $chatId,
        'fileName' => $fileName,
        'file' => curl_file_create( $path ),
    ];
    $requestBody['file']->mime = mime_content_type( $path );

    if ( $caption ) {
        $requestBody['caption'] = $caption;
    }
    if ( $quotedMessageId ) {
        $requestBody['quotedMessageId'] = $quotedMessageId;
    }
    $result = $greenApi->sending->uploadFile(
        'file:///C:/Users/Awa/Downloads/Document%20sans%20titre%20(5).pdf'
    );
   
}

    

function onIncomingMessageReceived($body, $greenApi)
{
    $idMessage = $body->idMessage;
    $eventDate = date('Y-m-d H:i:s', $body->timestamp);
    $senderData = $body->senderData;
    $messageData = $body->messageData;
    $senderName = $senderData->senderName;
    $chatId = $senderData->chatId;
    $receivedMessage = $messageData->textMessageData->textMessage;

    
    $message = "Bonjour *" . $senderName . "*, bienvenue sur la plateforme *Koumi*.\r\n"
    . "Veuillez choisir parmi les options suivantes :\r\n"
    . "1. Pour télécharger la fiche d'inscription\r\n"
    . "2. Pour rechercher un produit\r\n"
    . "3. Pour voir les boutiques\r\n"
    . "NB: *Le chiffre zero (0) vous permettra de revenir au menu principal*\r\n"
    . "Faite votre choix en envoyant le numéro correspondant :\r\n";

    if ($messageData->typeMessage === 'textMessage') {
      

        if ($receivedMessage != '0') {

        if (is_numeric($receivedMessage)) {
            switch ($receivedMessage) {
                case 1:
                    $greenApi->sending->uploadFile();
                    
                case 2:
                    $option2Message = "Vous avez choisi l'option 2.";
                    $greenApi->sending->sendMessage($chatId, $option2Message);
                    break;
                case 3:
                        $option3Message = "Vous avez choisi l'option 3.";
                        $greenApi->sending->sendMessage($chatId, $option3Message);
                        break;
                
                default:
                    $defaultMessage = "Je suis désolé, je ne comprends pas votre demande. Veuillez sélectionner une option valide.";
                    $greenApi->sending->sendMessage($chatId, $defaultMessage);
                    $greenApi->sending->sendMessage($chatId, $message);

                    break;
            }
           
        }else{
            $greenApi->sending->sendMessage($chatId, $message);

        }
    }
}
}








    function onIncomingCall($body)
{
    $idMessage = $body->idMessage;
    $eventDate = date('Y-m-d H:i:s', $body->timestamp);
    $fromWho = $body->from;
    print($idMessage . ': Call from ' . $fromWho . ' at ' . $eventDate) . PHP_EOL;
}

function onOutgoingAPIMessageReceived($body)
{
    $idMessage = $body->idMessage;
    $eventDate = date('Y-m-d H:i:s', $body->timestamp);
    $senderData = $body->senderData;
    $messageData =  $body->messageData;
    print($idMessage . ': At ' . $eventDate . ' Incoming from ' . json_encode($senderData, JSON_UNESCAPED_UNICODE) . ' message = ' . json_encode($messageData, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
}

function onDeviceInfo($body)
{
    $eventDate = date('Y-m-d H:i:s', $body->timestamp);
    $deviceData = $body->deviceData;
    print('At ' . $eventDate . ': ' . json_encode($deviceData, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
}

function onOutgoingMessageReceived($body)
{
    $idMessage = $body->idMessage;
    $eventDate = date('Y-m-d H:i:s', $body->timestamp);
    $senderData = $body->senderData;
    $messageData =  $body->messageData;
    print($idMessage . ': At ' . $eventDate . ' Outgoing from ' . json_encode($senderData, JSON_UNESCAPED_UNICODE) . ' message = ' . json_encode($messageData, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
}

function onOutgoingMessageStatus($body)
{
    $idMessage = $body->idMessage;
    $status = $body->status;
    $eventDate = date('Y-m-d H:i:s', $body->timestamp);
    print($idMessage . ': At ' . $eventDate . ' status = ' . $status) . PHP_EOL;
}

function onStateInstanceChanged($body)
{
    $eventDate = date('Y-m-d H:i:s', $body->timestamp);
    $stateInstance = $body->stateInstance;
    print('At ' . $eventDate . ' state instance = ' . $stateInstance) . PHP_EOL;
}

function onStatusInstanceChanged($body)
{
    $eventDate = date('Y-m-d H:i:s', $body->timestamp);
    $statusInstance = $body->stateInstance;
    print('At ' . $eventDate . ' status instance = ' . $statusInstance) . PHP_EOL;
}

?>
