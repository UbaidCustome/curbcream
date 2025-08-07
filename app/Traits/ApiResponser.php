<?php

namespace App\Traits;

use Carbon\Carbon;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Auth;

/*
|--------------------------------------------------------------------------
| Api Responser Trait
|--------------------------------------------------------------------------
|
| This trait will be used for any response we sent to clients.
|
*/

trait ApiResponser
{
	/**
     * Return a success JSON response.
     *
     * @param  array|string  $data
     * @param  string  $message
     * @param  int|null  $code
     * @return \Illuminate\Http\JsonResponse
     */

	protected $statusCode = 200;

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }


	protected function success(string $message = null, $data = null, int $statusCode = 200)
	{

		$message = $this->changeLanguage($message);
        // $user = Auth::user();
        // if($user->language == 'es')
        // {
        //     $message = $this->changeLanguage($message);
        // }
	   
		if(!empty($data)){
			return response()->json([
				'status' => 1,
				'message' => $message,
				'data' => $data
			], $statusCode);
		}
		else{
			return response()->json([
				'status' => 1,
				'message' => $message
			], $statusCode);
		}
	}

	/**
     * Return an error JSON response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array|string|null  $data
     * @return \Illuminate\Http\JsonResponse
     */
	protected function error(string $message = null, int $statusCode, $data = null)
	{
		return response()->json([
			'status' => 0,
			'message' => $message
		], $statusCode);
	}


	protected function loginResponse($message, $bearerToken, $data, $statusCode = 200)
	{
		return response()->json([
			'status'  => 1, 
			'message' => $message,
			'bearer_token' => $bearerToken,
			'data'    => $data
		], $statusCode);
	}


	private function changeLanguage($message)
    {
    	$user = Auth::user();
        $translator = new GoogleTranslate();

            $translator->setSource('auto');
        
            $translator->setTarget($user->language ?? 'en');
            
            $first_message = $translator->translate($message);
            
            return $first_message;	
       
    }

    private function respondInternalError($errors = [], $status = false, $message = 'Internal Error!')
    {
        return $this->setStatusCode(500)->respondWithError($errors, $status, $message);
    }

    private function respondWithError($errors = [], $status = false, $message)
    {
        return $this->respond([], $errors, $status, $message);
    }

     private function respond($data = [], $errors = [], $status, $message, $headers = [])
    {
        return response()->json(
            [
                'statusCode' => $this->getStatusCode(),
                'response' => [
                    'data' => $data
                ],
                'message' => $message,
                'status' => $status,
                'errors' => $errors
            ],
            $this->getStatusCode(),
            $headers
        );
    }
}