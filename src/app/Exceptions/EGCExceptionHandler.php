<?php
namespace Evergreen\Generic\App\Exceptions;

use Auth;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

/**
 * Decorator for the Laravel default exception handler.
 *
 * @author    Andrea Marco Sartori
 */
class EGCExceptionHandler extends ExceptionHandler
{
    protected $internalDontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        ModelNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
    ];

    public function render($request, Exception $e)
    {
        if ($e instanceof TokenMismatchException || get_class($e) == "Illuminate\Session\TokenMismatchException") {
            Auth::logout();
            toast()->error("Your session timed out please login again");
            return redirect("/login");
        }

        return parent::render($request, $e);
    }
}
