<?php

namespace App\Enums;

enum LoginEvent: string
{
    case Login = 'LOGIN';
    case Logout = 'LOGOUT';
    case LoginFailed = 'LOGIN_FAILED';
    case PasswordChanged = 'PASSWORD_CHANGED';
    case PasswordReset = 'PASSWORD_RESET';
}
