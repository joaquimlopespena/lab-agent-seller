<?php

namespace App\CustomerAuth;

use DateTimeInterface;

interface CustomerMagicLinkSender
{
    public function send(string $email, string $token, DateTimeInterface $expiresAt): void;
}
