<?php

require 'vendor\autoload.php';
// require_once('tcpdf/tcpdf.php');
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


$state = 'MAIN_MENU';

function onIncomingMessageReceived($body, $greenApi) {
    global $state;
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
        . "Faites votre choix en envoyant le numéro correspondant :\r\n";

    if ($messageData->typeMessage === 'textMessage') {
        if ($receivedMessage === '0') {
            $state = 'MAIN_MENU'; // Réinitialisez l'état à 'MAIN_MEaNU'
            $greenApi->sending->sendMessage($chatId, $message);
        } else {
            if (is_numeric($receivedMessage)) {
                switch ($state) {
                    case 'MAIN_MENU':
                        switch ($receivedMessage) {
                            case '1':

                                sendFileByUrl($chatId, 'https://drive.google.com/file/d/1lCuNWp_x2iZ1fHMXIqJFjojqFNvELtED/view?usp=sharing', 'Votre fiche d\'inscription est prête.');
                                break;
                                
                            case '2':
                                $state = 'CATEGORIES'; // Changez l'état pour 'CATEGORIES'
                               
                                $response = file_get_contents('http://localhost:9000/api-koumi/Categorie/allCategorie');
                                $categories = json_decode($response, true);
                                $categoryMessage = "Voici les différentes catégories, faites un choix en envoyant le numéro correspondant :\r\n";
                                foreach ($categories as $key => $category) {
                                    $categoryMessage .= ($key + 1) . ". " . $category['libelleCategorie'] . "\r\n";
                                }
                                $greenApi->sending->sendMessage($chatId, $categoryMessage);
                                break;
                                
                               case '3':

                                $state = 'MAGASIN'; // Changez l'état pour 'CATEGORIES'
                                $response = file_get_contents('http://localhost:9000/api-koumi/Magasin/getAllMagagin');
                                $data = json_decode($response, true);
                                
                                $listboutique= " Voici les différentes boutiques  *Faites un choix*:\r\n";
                                $listboutiqueCounter = 1; // Initialisez le compteur
                                foreach ($data as $item) {
                                    $listboutique .= "*" . $listboutiqueCounter++ . "* : " . $item['nomMagasin'] . "\r\n";
                                }
                                $greenApi->sending->sendMessage($chatId,$listboutique);

                                break;
                            
                        }


                        break;
                    case 'CATEGORIES':

                        break;

                    case 'MAGASIN':
                        
                        if (is_numeric($receivedMessage)) {
                            $response = file_get_contents('http://localhost:9000/api-koumi/Magasin/getAllMagagin');
                            $data = json_decode($response, true);
                            // Convertissez le numéro reçu en index de tableau
                            $magasinIndex = intval($receivedMessage) - 1;
                            // Récupérez les informations du magasin à partir de l'index
                            $magasinDetails = $data[$magasinIndex];
                    
                            if ($magasinIndex >= 0 && $magasinIndex < count($data)) {
                                $magasinDetails = $data[$magasinIndex];
                                // Construisez et envoyez le message avec les informations du magasin
                                $infoMagasinMessage = "Informations sur le magasin sélectionné :\r\n";
                                $infoMagasinMessage .= "*Nom* : " .  $magasinDetails['nomMagasin'] . "\r\n";
                                $infoMagasinMessage .= "*Localité* : " . $magasinDetails['localiteMagasin'] . "\r\n";
                                $infoMagasinMessage .= "*Contact* : " . $magasinDetails['contactMagasin'] . "\r\n";
                                // ...
                                $greenApi->sending->sendMessage($chatId, $infoMagasinMessage);
                            } else {
                                // Le numéro de la boutique saisi n'existe pas, envoyez un message d'erreur
                                $errorMessage = "Le numéro de la boutique que vous avez saisi n'est pas valide. Veuillez choisir un numéro ou 0 pour quitter.";
                                $greenApi->sending->sendMessage($chatId, $errorMessage);
                            }
                        } else {
                            // Si le message reçu n'est pas un numéro, renvoyez l'utilisateur au menu principal
                            $state = 'MAIN_MENU';
                            $greenApi->sending->sendMessage($chatId, $message);
                        }
    
 
                     
                           
                        break;
                }
                
                        
                    // default:
                    //     break;
                
            } else {
                $state = 'MAIN_MENU';
                $greenApi->sending->sendMessage($chatId, $message);
            }
        }
    }
}







function sendFileByUrl(
    string $chatId,
    string $urlFile,
    string $fileName = null,
    string $caption = null,
    string $quotedMessageId = null,
    bool $archiveChat = false
): void {
    global $greenApi;

    if (!$fileName) {
        $fileName = basename($urlFile);
    }

    $result = $greenApi->sending->sendFileByUrl(
        $chatId,
        $urlFile,
        $fileName,
        $caption,
        $quotedMessageId,
        $archiveChat
    );
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