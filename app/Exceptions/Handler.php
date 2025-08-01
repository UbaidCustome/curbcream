<?php

// namespace App\Exceptions;
// use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
// use Illuminate\Auth\AuthenticationException;
// use Illuminate\Database\Eloquent\ModelNotFoundException;
// use Illuminate\Validation\ValidationException;
// use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
// use Throwable;
// class Handler extends ExceptionHandler
// {
//     /**
//      * The list of the inputs that are never flashed to the session on validation exceptions.
//      *
//      * @var array<int, string>
//      */
//     protected $dontFlash = [
//         'current_password',
//         'password',
//         'password_confirmation',
//     ];
//     /**
//      * Register the exception handling callbacks for the application.
//      */
//     public function register(): void
//     {
//         $this->reportable(function (Throwable $e) {
//             //
//         });
//     }
//     public function render($request, Throwable $exception)
//     {
//         if ($request->is("api/*")) {
//             if ($exception instanceof ModelNotFoundException) {
//                 return response()->json([
//                     'status' => 0,
//                     'message' => $exception->getMessage(),
//                 ], 404);
//             } elseif ($exception instanceof ValidationException) {
//                 return response()->json([
//                     'status' => 0,
//                     'message' => $exception->validator->errors()->first(),
//                 ], 400);
//             } elseif ($exception instanceof MethodNotAllowedHttpException) {
//                 return response()->json([
//                     'status' => 0,
//                     'message' => 'Wrong http method given',
//                 ], 400);
//             } elseif ($exception instanceof NotFoundHttpException) {
//                 return response()->json([
//                     'status' => 0,
//                     'message' => 'Given URL not found on server',
//                 ], 404);
//             } elseif ($exception instanceof  AuthenticationException) {
//                 return response()->json([
//                     'status' =>  0,
//                     'message' => $exception->getMessage(),
//                 ], 401);
//             } else {
//                 return response()->json([
//                     'status' => 0,
//                     'message' => env('APP_DEBUG') ? $exception->getMessage() . ' on line no ' . $exception->getLine() : "Something went wrong",
//                 ], 500);
//             }
//         }
//         return parent::render($request, $exception);
//     }
// }


namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // This is where we can register custom logic for specific exceptions.
        $this->reportable(function (Throwable $e) {
            // You can add your custom logic for exception reporting here, for example:
            // Log the error, send the exception to an external service, etc.
        });
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        // For API requests, handle exceptions and provide custom responses
        if ($request->is('api/*')) {

             // Handle specific error for undefined variable (ErrorException)
            if ($exception instanceof \ErrorException) {
                return response()->json([
                    'status' => 0,
                    'message' => 'A variable is being used without initialization. Please check the code.',
                ], 500);  // Internal Server Error
            }
            
            // Handle Model Not Found exception
            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Model not found: ' . $exception->getModel(),
                ], 404);
            }

            // Handle Validation Exception (usually thrown during form validation)
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'status' => 0,
                    'message' => $exception->validator->errors()->first(),
                ], 400);
            }

            // Handle Method Not Allowed Exception (wrong HTTP method)
            if ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Wrong HTTP method used',
                ], 405);
            }

            // Handle Not Found HTTP Exception (404 error for missing routes)
            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Given URL not found on server',
                ], 404);
            }

            // Handle Authentication Exception (Unauthorized access)
            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Authentication failed: ' . $exception->getMessage(),
                ], 401);
            }

            // For other exceptions, return a generic 500 error
            return response()->json([
                'status' => 0,
                'message' => env('APP_DEBUG') ? $exception->getMessage() . ' on line ' . $exception->getLine() : "Something went wrong.",
            ], 500);
        }

        // For non-API requests, fall back to Laravel's default exception handling
        return parent::render($request, $exception);
    }
}
