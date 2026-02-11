<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;


class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        NotFoundHttpException::class,
        HttpException::class,
        ValidationException::class,
        QueryException::class,
        MethodNotAllowedHttpException::class,
        ModelNotFoundException::class

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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            return self::responseError('No se encontró el registro con el identificador especificado.', Response::HTTP_NOT_FOUND);
        });


        $this->renderable(function (MethodNotAllowedHttpException $e) {
            return self::responseError('El método especificado en la petición no es válido.', Response::HTTP_METHOD_NOT_ALLOWED);
        });


        $this->renderable(function (QueryException $e, $request) {
            if (config('app.debug')) {
                if ($e->getCode() == 1) {
                    $errorMessage = $e->getMessage();
                    preg_match('/\(([^)]+)\)/', $errorMessage, $matches);
                    $columnName = $matches[1];
                    return self::responseError("Ya existe un registro con este valor para la columna $columnName", Response::HTTP_BAD_REQUEST);
                }
                if ($e->getCode() == 2292) {
                    return self::responseError('No es posible eliminar el recurso especificado porque se han creado recursos relacionados.', Response::HTTP_CONFLICT);
                }
            } else {
                return self::responseError('Ocurrió un error inesperado en el servidor. Si el problema persiste comuniquese con el administrador del sistema.', Response::HTTP_LOCKED);
            }
        });


        $this->renderable(function (ValidationException $e, $request) {
            $more_errors = $e->validator->errors()->count() - 1;
            $message = 'Error de validación: ' . $e->validator->errors()->first();
            if ($more_errors > 0) {
                $error_m = $more_errors == 1 ? ' error más)' : ' errores más)';
                $message = $message . ' (y ' . $more_errors . $error_m;
            }
            return self::responseError($message, Response::HTTP_UNPROCESSABLE_ENTITY, $e->validator->errors()->getMessages());

        });


        $this->renderable(function (NotFoundHttpException $e) {
            return self::responseError('Dirección url no encontrada.', Response::HTTP_NOT_FOUND);
        });

        $this->renderable(function (HttpException $e) {
            return self::responseError($e->getMessage().' File: '.$e->getFile().' Line: '.$e->getLine(), $e->getStatusCode(), null, $e->getHeaders());
        });


        $this->renderable(function (Throwable $e) {
            return self::responseError($e->getMessage().' File: '.$e->getFile().' Line: '.$e->getLine(), Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }


    static function responseError($message, $code, $errors = null, $headers = []): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'description' => Response::$statusTexts[$code],
            'message' => $message,
            'errors' => $errors
        ], $code, $headers);
    }

}
