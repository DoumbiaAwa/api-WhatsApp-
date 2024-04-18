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
        . "1. Pour télécharger la fiche d'information\r\n"
        . "2. Pour rechercher un produit\r\n"
        . "3. Pour voir les boutiques\r\n"
        . "NB: *Le chiffre zero (0) vous permettra de revenir au menu principal*\r\n"
        . "Faites votre choix en envoyant le numéro correspondant :\r\n";
        //  $greenApi->sending->sendMessage($chatId, $message);

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
                                $result = $greenApi->sending->sendFileByUpload($chatId, 'images\fiche.jpeg');
                                
                                // sendFileByUrl($chatId, 'https://drive.google.com/file/d/1-Ah7GylCtIt11huQ3GYAUKQ5zlHT9t8e/view?usp=sharing');
                                break;
                                
                                case '2':
                                    $state = 'CATEGORIES';
                                    // Fetching all categories from the API
                                    $response = file_get_contents('https://koumi.ml/api-koumi/Categorie/allCategorie');
                                    $categories = json_decode($response, true);
                                
                                    // Preparing the message with the list of categories
                                    $categoryMessage = "Voici les différentes catégories, faites un choix en envoyant le numéro correspondant :\r\n";
                                    $listCategorieCount = 1; // Initialize the counter
                                    $listcategorie = ''; // Initialize the variable to store categories
                                    foreach ($categories as $item) {
                                        $listcategorie .= "*" . $listCategorieCount++ . "* : " . $item['libelleCategorie'] . "\r\n";
                                    }
                                    // Sending the list of categories to the user
                                    $greenApi->sending->sendMessage($chatId, $listcategorie);
                                    break;
                                
                                
                               
                                
                               case '3':

                                $state = 'MAGASIN'; // Changez l'état pour 'CATEGORIES'
                                $response = file_get_contents('https://koumi.ml/api-koumi/Magasin/getAllMagagin');
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
    if (is_numeric($receivedMessage)) {
        // Récupérer les catégories depuis l'API
        $response = file_get_contents('https://koumi.ml/api-koumi/Categorie/allCategorie');
        $categories = json_decode($response, true);

        // Convertir le numéro de catégorie reçu en index de tableau
        $categorieIndex = intval($receivedMessage) - 1;

        if ($categorieIndex >= 0 && $categorieIndex < count($categories)) {
            // Récupérer l'ID de la catégorie sélectionnée
            $selectedCategoryId = $categories[$categorieIndex]['idCategorieProduit'];

            // Récupérer les produits de la catégorie sélectionnée depuis l'API
            $productsResponse = file_get_contents('https://koumi.ml/api-koumi/Stock/categorieProduit/' . $selectedCategoryId);
            $products = json_decode($productsResponse, true);

            if (empty($products)) {
                // Aucun produit disponible dans cette catégorie
                $noProductsMessage = "Il n'y a aucun produit disponible dans cette catégorie.";
                $greenApi->sending->sendMessage($chatId, $noProductsMessage);
            } else {
                // Construire le message des produits
                $productMessage = "Voici les produits contenus dans " . $categories[$categorieIndex]['libelleCategorie'] . " :\r\n";
                foreach ($products as $product) {
                    $productMessage .= "*Nom* : " . $product['nomProduit'] . "\r\n";
                    $productMessage .= "*Forme* : " . $product['formeProduit'] . "\r\n";
                    $productMessage .= "*Date de production* : " . $product['dateProduction'] . "\r\n";
                    $productMessage .= "*Quantité en stock* : " . $product['quantiteStock'] . "\r\n";
                    $productMessage .= "*Prix* : " . $product['prix'] . "\r\n";
                    $productMessage .= "*Description* : " . $product['descriptionStock'] . "\r\n";
                    $productMessage .= "-----------------------------\r\n";
                }

                // Envoyer le message des produits à l'utilisateur
                $greenApi->sending->sendMessage($chatId, $productMessage);
            }
        } else {
            // Le numéro de catégorie saisi n'existe pas, envoyer un message d'erreur
            $errorMessage = "Le numéro de la catégorie que vous avez saisi n'est pas valide. Veuillez choisir un numéro valide ou 0 pour quitter.";
            $greenApi->sending->sendMessage($chatId, $errorMessage);
        }
    }
    break;

                                       
                      

                    case 'MAGASIN':
                        
                        if (is_numeric($receivedMessage)) {
                            $response = file_get_contents('https://koumi.ml/api-koumi/Magasin/getAllMagagin');
                            if ($response === FALSE) {
                                error_log("Erreur lors de la récupération des données de l'API");
                            }
                            $data = json_decode($response, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                error_log("Erreur lors du décodage du JSON: " . json_last_error_msg());
                            }

                            // Convertissez le numéro reçu en index de tableau
                            $magasinIndex = intval($receivedMessage) - 1;
                            // Récupérez les informations du magasin à partir de l'index
                            $magasinDetails = $data[$magasinIndex];
                    
                            if ($magasinIndex >= 0 && $magasinIndex < count($data)) {
                                $magasinDetails = $data[$magasinIndex];
                                // Construisez et envoyez le message avec les informations du magasin
                                $infoMagasinMessage = "Informations sur *" .  $magasinDetails['nomMagasin'] . "* :\r\n";
                                $infoMagasinMessage .= "*Nom* : " .  $magasinDetails['nomMagasin'] . "\r\n";
                                $infoMagasinMessage .= "*Localité* : " . $magasinDetails['localiteMagasin'] . "\r\n";
                                $infoMagasinMessage .= "*Contact* : " . $magasinDetails['contactMagasin'] . "\r\n";
                                $infoMagasinMessage .= "*Latitude* : " . $magasinDetails['latitude'] . "\r\n";
                                $infoMagasinMessage .= "*Longitude* : " . $magasinDetails['longitude'] . "\r\n";
                                $infoMagasinMessage .= "*Photo* : " . $magasinDetails['photo'] . "\r\n";
                                
                                // $infoMessage = "Veuillez choisir un numéro de boutique  ou 0 pour quitter. \r\n";

                                // ...
                                $greenApi->sending->sendMessage($chatId, $infoMagasinMessage);
                            }
                            
                            else {
                                // Le numéro de la boutique saisi n'existe pas, envoyez un message d'erreur
                                $errorMessage = "Le numéro de la boutique que vous avez saisi n'est pas valide. Veuillez choisir un numéro ou 0 pour quitter.";
                                $greenApi->sending->sendMessage($chatId, $errorMessage);
                            }
                        }
    
 
                     
                           
                        break;
                }
                
 
                
                
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
