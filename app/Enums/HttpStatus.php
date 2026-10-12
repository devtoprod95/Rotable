<?php

namespace App\Enums;

use Symfony\Component\HttpFoundation\Response;

enum HttpStatus: int
{
    case OK                    = Response::HTTP_OK;
    case CREATED               = Response::HTTP_CREATED;
    case MOVED_PERMANENTLY     = Response::HTTP_MOVED_PERMANENTLY;
    case FOUND                 = Response::HTTP_FOUND;
    case BAD_REQUEST           = Response::HTTP_BAD_REQUEST;
    case UNAUTHORIZED          = Response::HTTP_UNAUTHORIZED;
    case FORBIDDEN             = Response::HTTP_FORBIDDEN;
    case NOT_FOUND             = Response::HTTP_NOT_FOUND;
    case METHOD_NOT_ALLOWED    = Response::HTTP_METHOD_NOT_ALLOWED;
    case UNPROCESSABLE_ENTITY  = Response::HTTP_UNPROCESSABLE_ENTITY;
    case TOO_MANY_REQUESTS     = Response::HTTP_TOO_MANY_REQUESTS;
    case INTERNAL_SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;
    case SERVICE_UNAVAILABLE   = Response::HTTP_SERVICE_UNAVAILABLE;

    public function label(): string
    {
        return Response::$statusTexts[$this->value] ?? 'Unknown Status';
    }
}
