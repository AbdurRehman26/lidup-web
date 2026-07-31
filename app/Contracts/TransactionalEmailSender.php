<?php

namespace App\Contracts;

use App\Data\TransactionalEmail;

interface TransactionalEmailSender
{
    public function send(TransactionalEmail $email): void;
}
