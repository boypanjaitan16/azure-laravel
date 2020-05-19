<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use TheNetworg\OAuth2\Client\Provider\Azure;

class AzureController extends Controller
{
    public function __construct()
    {
        session_start();
    }

    public function home(Request $request){
        $provider = new Azure([
            'clientId'      => '5268df1e-d8aa-4adb-8915-a8ef67c8b3eb',
            'clientSecret'  => 'jYx:uOA39DKv[wCKgNgyW=:xwsdrJL94',
            'redirectUri'   => 'http://localhost/lara/azure/public'
        ]);

        $code   = $request->query('code');
        $state  = $request->query('state');

        if (empty($code)) {

            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl();
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: '.$authUrl);
            exit;

        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($state) || ($state !== @$_SESSION['oauth2state'])) {

            unset($_SESSION['oauth2state']);
            return view('error')->with('msg', 'Invalid state, please reload');

        } else {

            // Optional: Now you have a token you can look up a users profile data
            try {

                // Try to get an access token (using the authorization code grant)
                $token = $provider->getAccessToken('authorization_code', [
                    'code'      => $code,
                    'resource'  => 'https://graph.windows.net',
                ]);

                // We got an access token, let's now get the user's details
                $me = $provider->get("me", $token);

                // Use these details to create a new profile
                dd($token);
                //dd($me);
                //return 'Hello '.$me['givenName'];

            } catch (\Exception $e) {
                return view('error')->with('msg', $e->getMessage());
            }

            // Use this to interact with an API on the users behalf
            //echo $token->getToken();
        }
    }

    public function homeEx(Request $request){
        $provider = new Azure([
            'clientId'      => '5268df1e-d8aa-4adb-8915-a8ef67c8b3eb',
            'clientSecret'  => 'jYx:uOA39DKv[wCKgNgyW=:xwsdrJL94',
            'redirectUri'   => 'http://localhost/lara/azure/public',
            'resource'      => 'https://management.azure.com/'
        ]);

        $code   = $request->query('code');
        $state  = $request->query('state');

        //return !empty($code) ? 'not empty' : 'empty';

        if (empty($code)) {

            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl();
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: '.$authUrl);
            exit;

        }
        elseif (empty($state) || ($state !== @$_SESSION['oauth2state'])) {

            unset($_SESSION['oauth2state']);
            return view('error')->with('msg', 'Invalid state, please reload');
        }
        else {
            try {
                // Try to get an access token (using the authorization code grant)
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $code
                ]);

                $accesstoken = $provider->getAccessToken('refresh_token', [
                    'refresh_token' => $token->getRefreshToken(),
                    'resource'      => 'https://management.core.windows.net/'
                ]);

                $bearertoken = "Bearer " . $accesstoken->getToken();

                $client = new Client([
                    'base_uri'  => 'https://management.azure.com/',
                    'timeout'   => 2.0,
                ]);

                $result = $client->request('GET', "/subscriptions/?api-version=2015-01-01", [
                    'headers' => [
                        'User-Agent'    => 'testing/1.0',
                        'Accept'        => 'application/json',
                        'Authorization' => $bearertoken
                    ]
                ]);

                dd($result->getBody());

            } catch (\Exception $e) {
                return view('error')->with('msg', $e->getMessage());
            }
        }
    }

    public function getter(Request $request){
        return $request->get('code');
    }

    public function error(){
        return view('error');
    }
}
