<?php

namespace Solivellaluisaberto\PayKit\Enums;

enum PaymentType: string
{
    case API = 'api';
    case REDIRECT = 'redirect';
    CASE FORM = 'form';
}
