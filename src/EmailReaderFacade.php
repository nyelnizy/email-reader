<?php

namespace Hwa\EmailReader;

use Illuminate\Support\Facades\Facade;

class EmailReaderFacade extends Facade
{
    /**
     * @method static setAuthCode(string $code)
     * @method static getOauthUrl(): string
     * @method static readEmails(string $token,$user_id,callable $callback)
     */
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'email-reader';
    }
}
