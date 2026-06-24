<?php

namespace App\Exceptions;

use Exception;

class AppException extends Exception
{
    /**
     * @param $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request): \Illuminate\Http\JsonResponse
    {
        return error(
            message: $this->getMessage(),
            code: $this->getCode() ?: 500
        );
    }
}
