<?php

declare(strict_types=1);

namespace App\Validator;

class EmailValidator
{
    public function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getDomain(string $email): ?string
    {
        if (!$this->isValid($email)) {
            return null;
        }

        $parts = explode('@', $email);

        return $parts[1] ?? null;
    }

    public function isDisposable(string $email): bool
    {
        $disposableDomains = [
            'tempmail.com',
            'throwaway.email',
            'guerrillamail.com',
            'mailinator.com',
        ];

        $domain = $this->getDomain($email);

        if ($domain === null) {
            return false;
        }

        return in_array(strtolower($domain), $disposableDomains, true);
    }

    public function normalize(string $email): string
    {
        $email = trim($email);
        $email = strtolower($email);

        if (!$this->isValid($email)) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        // Remove dots from gmail local parts
        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = str_replace('.', '', $local);
            $plusPos = strpos($local, '+');

            if ($plusPos !== false) {
                $local = substr($local, 0, $plusPos);
            }
        }

        return $local . '@' . $domain;
    }
}
