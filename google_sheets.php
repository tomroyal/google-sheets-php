<?php

include('./vendor/autoload.php');

// some basic google sheets code in php

// getClient is from https://developers.google.com/drive/v3/web/quickstart/php
// https://developers.google.com/sheets/api/reference/rest/
// requires client_secret.json

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
 
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Sheets API PHP Quickstart');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
    $client->setAuthConfig('client_secret.json');
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory('credentials.json');
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path)
{
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Sheets($client);
$spreadsheetId = '1rV-uYWQ3KH7RZXWIiCUyvua3q8APGbHLlOPqpi5eC7Q'; // get it from sheet url

// add a sheet 

$sheetname = 'test001';

$addsheet = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
   'requests' => array('addSheet' => array('properties' => array('title' => $sheetname )))));

$result = $service->spreadsheets->batchUpdate($spreadsheetId,$addsheet);


// write some sample data to it

for($i=1; $i<=5; $i++){
    $range = $sheetname.'!A'.$i.':E'.$i;
    
    $values = [
        ["1", "2", "3", "Monkeys",$i]
    ];
    
    $requestBody = new Google_Service_Sheets_ValueRange([
        'range' => $range,
        'majorDimension' => 'ROWS',
        'values' => $values,
    ]);
    
    $response = $service->spreadsheets_values->update($spreadsheetId, $range, $requestBody, ['valueInputOption' => 'USER_ENTERED']);
 
};

// read array of data out

$range = $sheetname.'!A1:E5';
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

foreach ($values as $row){
  print_r($row);
};
?>
